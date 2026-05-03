<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;
use App\Services\Leads\LeadResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebsiteLeadController extends Controller
{
    public function store(Request $request, string $token)
    {
        // IMPORTANT: bypass global scopes
        $source = LeadSource::withoutGlobalScopes()
            ->where('form_token', $token)
            ->where('status', 'active')
            ->firstOrFail();

        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'required|string|max:32',
            'email'   => 'nullable|email|max:150',
            'message' => 'nullable|string',
        ]);

        $lead = null;

        DB::transaction(function () use ($data, $source, &$lead) {

            /*
            |--------------------------------------------------------------------------
            | Create / resolve lead
            |--------------------------------------------------------------------------
            | Website controller must only capture the lead.
            | WhatsApp welcome message is handled by:
            | LeadCreated event → HandleLeadCreatedOutbound → SendWhatsAppFromTemplate
            */

            $leadResolver = app(LeadResolver::class);

            $lead = $leadResolver->resolve([
                'name'            => $data['name'],
                'phone'           => $data['phone'],
                'email'           => $data['email'] ?? null,
                'source'          => 'website',
                'external_source' => 'website',
            ], $source->company_id);

            if (!$lead) {
                throw new \Exception('Lead creation failed');
            }

            /*
            |--------------------------------------------------------------------------
            | Preserve website/source metadata
            |--------------------------------------------------------------------------
            */

            $lead->update([
                'notes'             => $data['message'] ?? $lead->notes,
                'external_source'   => 'website',
                'external_form_id'  => $source->form_token,
                'external_payload'  => $data,
                'preferred_channel' => 'phone',
            ]);

            Log::info('[WebsiteLead] Lead captured', [
                'lead_id'    => $lead->id,
                'company_id' => $lead->company_id,
                'source'     => $lead->source,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Lead captured successfully',
            'lead_id' => $lead?->id,
        ], 201);
    }
}