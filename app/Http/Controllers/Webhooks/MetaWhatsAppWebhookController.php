<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Services\WhatsApp\Drivers\ProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MetaWhatsAppWebhookController extends Controller
{
    public function verify(Request $r)
    {
        $verify   = (string) config('services.meta.verify_token');
        if ($r->query('hub.mode') === 'subscribe' &&
            hash_equals($verify, (string) $r->query('hub.verify_token'))) {
            return response($r->query('hub.challenge'), 200);
        }
        return response('Forbidden', 403);
    }

    public function handle(Request $r, ProviderFactory $providers)
    {
        $payload = $r->json()->all();

        foreach ((array)($payload['entry'] ?? []) as $entry) {
            foreach ((array)($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];
                $messages = (array) ($value['messages'] ?? []);
                $metadata = (array) ($value['metadata'] ?? []);
                $phoneId  = $metadata['phone_number_id'] ?? null;

                // Resolve company by phone_number_id (you likely store this against tenant)
                $companyId = $this->resolveCompanyIdByPhoneId($phoneId);
                if (! $companyId) { continue; }

                $wa = $providers->forCompany($companyId);

                foreach ($messages as $m) {
                    // Only handle text replies here
                    if (($m['type'] ?? '') !== 'text') continue;

                    $fromE164 = (string) ($m['from'] ?? '');
                    $text     = trim((string) ($m['text']['body'] ?? ''));

                    if ($fromE164 === '' || $text === '') continue;

                    // Find latest lead by phone (normalized)
                    $phoneNorm = preg_replace('/\D+/', '', $fromE164);
                    $lead = Lead::query()
                        ->where('company_id', $companyId)
                        ->where('phone_norm', $phoneNorm)
                        ->latest('id')
                        ->first();

                    if (! $lead) {
                        Log::info('[MetaWA] No lead match for incoming', ['company_id'=>$companyId,'from'=>$fromE164]);
                        continue;
                    }

                    // Parse make/model from free text — simple heuristic:
                    // e.g. "Toyota Corolla", "Nissan Pathfinder", "Honda City"
                    [$makeName, $modelName] = $this->parseMakeModel($text);

                    // Update opportunity
                    $opp = Opportunity::where('company_id', $companyId)
                        ->where('lead_id', $lead->id)
                        ->latest('id')
                        ->first();

                    if (! $opp) {
                        Log::info('[MetaWA] No opportunity for lead (yet)', ['lead_id'=>$lead->id]);
                        continue;
                    }

                    if ($makeName) {
                        $make = VehicleMake::firstOrCreate(['name' => Str::ucfirst($makeName)]);
                        $opp->vehicle_make_id = $make->id;

                        if ($modelName) {
                            $model = VehicleModel::firstOrCreate([
                                'make_id' => $make->id,
                                'name'    => Str::ucfirst($modelName),
                            ]);
                            $opp->vehicle_model_id = $model->id;
                        } else {
                            $opp->vehicle_model_id = null;
                        }

                        $opp->save();
                    } else {
                        // fall back to other_make/other_model if we couldn't parse cleanly
                        $opp->other_make  = Str::limit($text, 255);
                        $opp->other_model = null;
                        $opp->save();
                    }

                    // Send thank-you template
                    $wa->sendTemplate(
                        toE164:   $fromE164,
                        template: 'vehicle_info_thanks', // tenant-approved
                        params:   [$lead->name ?: 'there'],
                        links:    [],
                        context:  ['company_id'=>$companyId, 'lead_id'=>$lead->id]
                    );
                }
            }
        }

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }

    protected function resolveCompanyIdByPhoneId(?string $phoneId): ?int
    {
        if (! $phoneId) return null;
        // Example lookup:
        // return \DB::table('company_settings')
        //     ->where('key', 'whatsapp.meta.phone_number_id')
        //     ->where('value', $phoneId)
        //     ->value('company_id');
        return \DB::table('company_settings')
            ->where('key', 'whatsapp.meta.phone_number_id')
            ->where('value', $phoneId)
            ->value('company_id');
    }

    /**
     * VERY simple parser. If you already have a make list, you can improve by matching first token to known makes.
     */
    protected function parseMakeModel(string $text): array
    {
        // Normalize spaces
        $t = preg_replace('/\s+/', ' ', trim($text));

        // Try "Make Model" → split on first space
        if (str_contains($t, ' ')) {
            [$make, $modelRest] = explode(' ', $t, 2);
            return [Str::lower($make), trim($modelRest)];
        }

        // Single token → treat as make only
        return [$t !== '' ? Str::lower($t) : null, null];
    }
}
