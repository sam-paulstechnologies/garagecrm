<?php

namespace App\Services\Conversation\Flows;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Services\Conversation\ConversationGuard;
use App\Services\Leads\LeadConversionService;
use App\Services\Vehicles\VehicleResolver;
use Illuminate\Support\Facades\Log;

class VehicleFlow
{
    public function __construct(
        protected VehicleResolver $vehicleResolver,
        protected LeadConversionService $leadConversionService,
        protected ConversationGuard $guard
    ) {}

    public function start(Lead $lead, string $text): array
    {
        [$makeId, $modelId, $otherMake, $otherModel] = $this->vehicleResolver->resolve($text);

        if (! $makeId && ! $otherMake) {
            $data = $this->conversationData($lead);

            $data['vehicle_attempts'] = 0;
            $data['booking_started_at'] = now()->toIso8601String();
            $data['last_vehicle_prompt_at'] = now()->toIso8601String();

            $lead->conversation_state = 'awaiting_vehicle';
            $lead->conversation_data = $data;
            $lead->conversation_updated_at = now();
            $lead->save();

            return $this->sessionResponse(
                template: 'ask_make_model_v1',
                action: 'collect_vehicle',
                body: $this->askVehicleBody($lead),
                placeholders: [$lead->name ?: 'there'],
                context: [
                    'event_key' => 'lead.ask_vehicle',
                    'reason' => 'vehicle_not_found_in_initial_text',
                ]
            );
        }

        return $this->handle($lead, $text);
    }

    public function handle(Lead $lead, string $text): array
    {
        [$makeId, $modelId, $otherMake, $otherModel] = $this->vehicleResolver->resolve($text);

        /*
        |--------------------------------------------------------------------------
        | Failure handling
        |--------------------------------------------------------------------------
        */

        if (! $makeId && ! $otherMake) {
            $data = $this->conversationData($lead);

            $attempts = (int) ($data['vehicle_attempts'] ?? 0);
            $attempts++;

            $data['vehicle_attempts'] = $attempts;
            $data['last_vehicle_failed_text'] = $text;
            $data['last_vehicle_failed_at'] = now()->toIso8601String();

            $lead->conversation_data = $data;
            $lead->conversation_updated_at = now();
            $lead->save();

            if ($attempts >= 3) {
                return $this->guard->escalateToManager(
                    $lead,
                    'Vehicle capture failed after 3 attempts: ' . $text
                );
            }

            return $this->sessionResponse(
                template: 'ask_make_model_v1',
                action: 'retry_vehicle',
                body: "Sorry, I could not clearly identify the vehicle.\n\n" . $this->askVehicleBody($lead),
                placeholders: [$lead->name ?: 'there'],
                context: [
                    'event_key' => 'lead.ask_vehicle',
                    'vehicle_attempts' => $attempts,
                    'reason' => 'vehicle_capture_retry',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Correction handling
        |--------------------------------------------------------------------------
        */

        if ($makeId && $lead->vehicle_make_id && (int) $lead->vehicle_make_id !== (int) $makeId) {
            $lead->vehicle_model_id = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Save vehicle capture to lead
        |--------------------------------------------------------------------------
        */

        $lead->vehicle_make_id  = $makeId ?: $lead->vehicle_make_id;
        $lead->vehicle_model_id = $modelId ?: $lead->vehicle_model_id;
        $lead->other_make       = $otherMake ?: null;
        $lead->other_model      = $otherModel ?: null;

        $data = $this->conversationData($lead);

        $data['vehicle_attempts'] = 0;
        $data['vehicle_captured_at'] = now()->toIso8601String();
        $data['vehicle_captured_raw_text'] = $text;

        $lead->conversation_data = $data;
        $lead->conversation_updated_at = now();
        $lead->save();

        /*
        |--------------------------------------------------------------------------
        | Ensure client + opportunity
        |--------------------------------------------------------------------------
        */

        try {
            $this->leadConversionService->ensureClientAndOpportunity($lead->id, (int) $lead->company_id);
            $lead->refresh();
        } catch (\Throwable $e) {
            Log::warning('[VehicleFlow] Failed to ensure client/opportunity', [
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'error' => $e->getMessage(),
            ]);

            return $this->guard->escalateToManager(
                $lead,
                'Vehicle captured but client/opportunity creation failed: ' . $e->getMessage()
            );
        }

        $opportunity = $lead->opportunity;

        if ($opportunity) {
            /*
            |--------------------------------------------------------------------------
            | Resolve make/model records
            |--------------------------------------------------------------------------
            */

            $make = null;

            if ($lead->vehicle_make_id) {
                $make = VehicleMake::find($lead->vehicle_make_id);
            } elseif ($lead->other_make) {
                $make = VehicleMake::firstOrCreate([
                    'name' => $this->formatVehicleName($lead->other_make),
                ]);
            }

            $model = null;

            if ($lead->vehicle_model_id) {
                $model = VehicleModel::find($lead->vehicle_model_id);
            } elseif ($lead->other_model && $make) {
                $model = VehicleModel::firstOrCreate([
                    'make_id' => $make->id,
                    'name'    => $this->formatVehicleName($lead->other_model),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Prevent duplicate vehicles
            |--------------------------------------------------------------------------
            */

            if ($make) {
                $vehicle = Vehicle::where([
                    'company_id' => $lead->company_id,
                    'client_id'  => $lead->client_id,
                    'make_id'    => $make->id,
                    'model_id'   => $model?->id,
                ])->first();

                if (! $vehicle) {
                    $vehicle = Vehicle::create([
                        'company_id' => $lead->company_id,
                        'client_id'  => $lead->client_id,
                        'make_id'    => $make->id,
                        'model_id'   => $model?->id,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Sync resolved vehicle back to lead
                |--------------------------------------------------------------------------
                */

                $lead->update([
                    'vehicle_make_id'  => $make->id,
                    'vehicle_model_id' => $model?->id,
                    'other_make'       => null,
                    'other_model'      => null,
                    'conversation_updated_at' => now(),
                ]);

                $opportunity->update([
                    'vehicle_id'       => $vehicle->id,
                    'vehicle_make_id'  => $make->id,
                    'vehicle_model_id' => $model?->id,
                    'other_make'       => null,
                    'other_model'      => null,
                    'stage'            => Opportunity::STAGE_COLLECTING_DETAILS,
                    'notes'            => $this->appendNote(
                        $opportunity->notes,
                        'Vehicle captured via WhatsApp bot'
                    ),
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Next step
        |--------------------------------------------------------------------------
        */

        $this->updateState($lead, 'awaiting_timeslot');

        return $this->sessionResponse(
            template: 'ask_preferred_time_v1',
            action: 'collect_timeslot',
            body: $this->askPreferredTimeBody($lead),
            placeholders: [$lead->name ?: 'there'],
            context: [
                'event_key' => 'lead.ask_preferred_time',
                'vehicle_make_id' => $lead->vehicle_make_id,
                'vehicle_model_id' => $lead->vehicle_model_id,
                'reason' => 'vehicle_captured',
            ]
        );
    }

    protected function sessionResponse(
        string $template,
        string $action,
        string $body,
        array $placeholders = [],
        array $context = []
    ): array {
        return [
            /*
            |--------------------------------------------------------------------------
            | Important
            |--------------------------------------------------------------------------
            |
            | VehicleFlow is used by ProcessInboundWhatsApp.
            | This is an inbound/session flow, so body/text/message should be sent
            | from the app inside the 24-hour WhatsApp customer service window.
            |
            | template is kept only as a compatibility/logging hint.
            |
            */

            'body' => $body,
            'text' => $body,
            'message' => $body,

            'template' => $template,
            'template_hint' => $template,

            'placeholders' => $placeholders,
            'action' => $action,

            'context' => array_merge([
                'send_mode' => 'session_message',
                'template_hint' => $template,
            ], $context),
        ];
    }

    protected function askVehicleBody(Lead $lead): string
    {
        $name = $lead->name ?: 'there';

        return "Sure {$name}. Please share your vehicle make and model.\n\n"
            . "Example: Toyota Camry 2020";
    }

    protected function askPreferredTimeBody(Lead $lead): string
    {
        $name = $lead->name ?: 'there';

        return "Thanks {$name}. Please share your preferred booking date and time.\n\n"
            . "Example: Tomorrow morning or Friday 4 PM";
    }

    protected function updateState(Lead $lead, string $state): void
    {
        $data = $this->conversationData($lead);

        $lead->conversation_state = $state;
        $lead->conversation_data = array_merge($data, [
            'last_state_at' => now()->toIso8601String(),
        ]);
        $lead->conversation_updated_at = now();

        $lead->save();
    }

    protected function conversationData(Lead $lead): array
    {
        $data = $lead->conversation_data ?? [];

        return is_array($data) ? $data : [];
    }

    protected function formatVehicleName(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        if (preg_match('/[a-z]/i', $value) && preg_match('/[0-9]/', $value)) {
            return strtoupper($value);
        }

        if (strlen($value) <= 3 && strtoupper($value) === $value) {
            return $value;
        }

        return ucfirst(strtolower($value));
    }

    protected function appendNote(?string $existing, string $line): string
    {
        $existing = trim((string) $existing);
        $line = trim($line);

        if ($existing === '') {
            return $line;
        }

        if (str_contains($existing, $line)) {
            return $existing;
        }

        return $existing . "\n" . $line;
    }
}