<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Client\Client;
use App\Models\Job\Invoice;
use App\Models\IncomingInvoice;

class WhatsAppWebhookController extends Controller
{
    /** GET /api/webhooks/whatsapp (Meta verification) */
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token', env('WHATSAPP_VERIFY_TOKEN'))) {
            return response($challenge, 200);
        }
        return response('Forbidden', 403);
    }

    /** POST /api/webhooks/whatsapp (events) */
    public function receive(Request $request)
    {
        $payload = $request->all();

        // Meta sends an array of entry->changes->value->messages
        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $messages = $value['messages'] ?? [];
                foreach ($messages as $msg) {
                    $this->handleMessage($msg, $value);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    protected function handleMessage(array $msg, array $context): void
    {
        $type = $msg['type'] ?? null;
        if (!in_array($type, ['image', 'document'], true)) {
            return; // ignore non-media
        }

        $from        = $msg['from'] ?? null; // e.g. "9198xxxxxx"
        $caption     = $msg[$type]['caption'] ?? '';
        $mediaId     = $msg[$type]['id'] ?? null;
        $mime        = $msg[$type]['mime_type'] ?? null;
        $origName    = $msg['document']['filename'] ?? null;

        if (!$mediaId) return;

        // 1) Download media from WhatsApp Cloud API
        [$path, $finalMime] = $this->downloadMediaToPublicDisk($mediaId, $mime, $origName);

        // 2) Try to infer company/client from sender phone
        $client = $this->findClientByWhatsapp($from);
        $companyId = $client?->company_id;

        // 3) Try to match an existing invoice by number from caption/filename
        $detectedNumber = $this->extractInvoiceNumber($caption ?: $origName);

        if ($detectedNumber) {
            $invoice = $this->findInvoiceByNumber($detectedNumber, $companyId);
            if ($invoice) {
                // attach file to existing invoice
                $invoice->file_path = $path;
                $invoice->file_type = $finalMime;
                $invoice->save();
                return;
            }
        }

        // 4) No match → store as incoming for manual review
        IncomingInvoice::create([
            'company_id'            => $companyId,
            'client_id'             => $client?->id,
            'whatsapp_from'         => $from,
            'whatsapp_message_id'   => $msg['id'] ?? null,
            'caption'               => $caption,
            'original_filename'     => $origName,
            'detected_invoice_no'   => $detectedNumber,
            'file_path'             => $path,
            'file_type'             => $finalMime,
            'status'                => 'pending',
        ]);
    }

    protected function downloadMediaToPublicDisk(string $mediaId, ?string $hintMime, ?string $origName): array
    {
        $token = config('services.whatsapp.token', env('WHATSAPP_ACCESS_TOKEN'));
        $base  = config('services.whatsapp.graph_base', 'https://graph.facebook.com/v20.0');

        // a) resolve media URL
        $meta = Http::withToken($token)->get("$base/$mediaId")->json();
        $url  = $meta['url'] ?? null;
        if (!$url) {
            throw new \RuntimeException('WhatsApp media URL not found');
        }

        // b) fetch binary
        $bin = Http::withToken($token)->get($url)->body();

        // c) choose extension
        $ext = $origName ? pathinfo($origName, PATHINFO_EXTENSION) : null;
        if (!$ext && $hintMime) {
            $ext = match (strtolower($hintMime)) {
                'application/pdf' => 'pdf',
                'image/jpeg'      => 'jpg',
                'image/png'       => 'png',
                'image/webp'      => 'webp',
                default           => 'bin',
            };
        }
        $ext = $ext ?: 'bin';

        // d) store
        $path = 'invoices/whatsapp/'.now()->format('Y/m/d').'/'.Str::uuid().'.'.$ext;
        Storage::disk('public')->put($path, $bin);

        // Try to get final mime
        $mime = $hintMime ?: (Storage::disk('public')->mimeType($path) ?: 'application/octet-stream');

        return [$path, $mime];
    }

    protected function findClientByWhatsapp(?string $from): ?Client
    {
        if (!$from) return null;

        // Normalize: try last 10 digits match
        $last10 = substr(preg_replace('/\D+/', '', $from), -10);

        return Client::query()
            ->when($last10, fn($q) => $q->whereRaw('RIGHT(REGEXP_REPLACE(phone, "[^0-9]", ""), 10) = ?', [$last10]))
            ->orderByDesc('id')
            ->first();
    }

    protected function extractInvoiceNumber(?string $text): ?string
    {
        if (!$text) return null;

        // Common patterns: INV-1234, IV-2025-0001, 00012345 etc.
        if (preg_match('/([A-Z]{2,}-\d{2,}|\d{4,})/i', $text, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }

    protected function findInvoiceByNumber(string $number, ?int $companyId): ?Invoice
    {
        // We don't know your column name. Probe common ones.
        $cols = ['number', 'invoice_no', 'code'];

        return Invoice::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->where(function ($q) use ($cols, $number) {
                foreach ($cols as $c) {
                    $q->orWhere($c, $number);
                }
            })
            ->orderByDesc('id')
            ->first();
    }
}
