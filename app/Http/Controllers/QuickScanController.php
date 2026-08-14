<?php

namespace App\Http\Controllers;

use App\QuickScan\QuickScanAccess;
use App\QuickScan\QuickScanAudit;
use App\QuickScan\QuickScanConversion;
use App\QuickScan\QuickScanIngestion;
use App\QuickScan\QuickScanMeta;
use App\QuickScan\QuickScanPurge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QuickScanController extends Controller
{
    public function __construct(
        private readonly QuickScanAccess $access,
        private readonly QuickScanAudit $audit,
        private readonly QuickScanIngestion $ingestion,
    ) {}

    public function show(string $token): View
    {
        $this->assertEnabled();
        $scan = $this->access->resolve($token);
        if (! $scan->opened_at) {
            $scan->forceFill(['opened_at' => now()])->save();
            $this->audit->record($scan, 'quick_scan.link_opened', context: ['status' => $scan->status]);
        }
        if (in_array($scan->status, ['report_ready', 'accepted', 'declined'], true) && ! $scan->report_viewed_at) {
            $scan->forceFill(['report_viewed_at' => now()])->save();
            $this->audit->record($scan, 'quick_scan.report_viewed', context: ['status' => $scan->status]);
        }

        return view('quick_scan.show', [
            'scan' => $scan->fresh(), 'token' => $token,
            'metaConfigurations' => [
                'business_app_onboarding' => app(\App\Services\WhatsApp\MetaEmbeddedSignupService::class)->signupConfiguration('business_app_onboarding'),
                'cloud_api' => app(\App\Services\WhatsApp\MetaEmbeddedSignupService::class)->signupConfiguration('cloud_api'),
            ],
        ]);
    }

    public function consent(Request $request, string $token): RedirectResponse
    {
        $this->assertEnabled();
        $scan = $this->access->resolve($token);
        abort_unless($scan->status === 'consent_pending', 409);
        $data = $request->validate([
            'consent' => ['accepted'],
            'connection_mode' => ['required', Rule::in(['business_app_onboarding', 'cloud_api'])],
            'whatsapp_number' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9][0-9\s().-]{6,29}$/'],
            'staff_numbers' => ['nullable', 'string', 'max:1000'],
        ]);
        $number = '+'.$this->digits($data['whatsapp_number']);
        $staffHashes = collect(preg_split('/[\r\n,;]+/', (string) ($data['staff_numbers'] ?? '')))
            ->map(fn ($value) => $this->digits($value))->filter()->unique()
            ->map(fn ($value) => $this->ingestion->identityHash($scan, $value))->values()->all();
        $scan->forceFill([
            'status' => 'connection_pending', 'consent_at' => now(),
            'consent_policy_version' => (string) config('quick_scan.consent_policy_version'),
            'connection_mode' => $data['connection_mode'], 'claimed_whatsapp_number' => $number,
            'claimed_whatsapp_hash' => $this->ingestion->identityHash($scan, $number),
            'staff_number_hashes' => $staffHashes,
        ])->save();
        $this->audit->record($scan, 'quick_scan.consent_accepted', context: [
            'status' => 'connection_pending', 'connection_mode' => $data['connection_mode'],
            'consent_policy_version' => $scan->consent_policy_version,
        ]);

        return redirect()->route('quick-scan.show', ['token' => $token]);
    }

    public function startMeta(string $token, QuickScanMeta $meta): JsonResponse
    {
        $this->assertEnabled();
        $scan = $this->access->resolve($token);

        return response()->json(['ok' => true] + $meta->start($scan));
    }

    public function completeMeta(Request $request, string $token, QuickScanMeta $meta): JsonResponse
    {
        $this->assertEnabled();
        $scan = $this->access->resolve($token);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:4096'], 'state' => ['required', 'string', 'size:64'],
            'session_event' => ['required', Rule::in(['FINISH', 'FINISH_ONLY_WABA', 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING'])],
            'business_id' => ['nullable', 'string', 'max:100', 'regex:/^[0-9]+$/'],
            'waba_id' => ['nullable', 'string', 'max:100', 'regex:/^[0-9]+$/'],
            'phone_number_id' => ['nullable', 'string', 'max:100', 'regex:/^[0-9]+$/'],
        ]);
        $meta->complete($scan, $data);

        return response()->json(['ok' => true, 'message' => 'WhatsApp verified. Supported history is being read securely.']);
    }

    public function progress(string $token): JsonResponse
    {
        $this->assertEnabled();
        $scan = $this->access->resolve($token);

        return response()->json([
            'status' => $scan->status, 'contacts_discovered' => $scan->contacts_discovered,
            'contacts_analysed' => $scan->contacts_analysed,
            'deterministic_excluded' => $scan->contacts_deterministic_excluded,
            'report_ready' => $scan->status === 'report_ready',
        ]);
    }

    public function accept(string $token, QuickScanConversion $conversion): RedirectResponse
    {
        $this->assertEnabled();
        $scan = $conversion->accept($this->access->resolve($token));

        return redirect()->route('register', ['quick_scan' => $token])
            ->with('success', 'Continue with normal SayaraForce onboarding. No payment or CRM import has occurred.');
    }

    public function decline(string $token, QuickScanPurge $purge): RedirectResponse
    {
        $this->assertEnabled();
        $purge->decline($this->access->resolve($token));

        return redirect()->route('quick-scan.show', ['token' => $token]);
    }

    private function assertEnabled(): void
    {
        abort_unless(config('quick_scan.enabled'), 404);
    }

    private function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }
}
