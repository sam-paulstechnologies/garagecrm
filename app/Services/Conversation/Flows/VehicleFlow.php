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

        if (!$makeId && !$otherMake) {

            $data = $lead->conversation_data ?? [];

            if (!is_array($data)) {
                $data = [];
            }

            $data['vehicle_attempts'] = 0;
            $data['booking_started_at'] = now()->toIso8601String();

            $lead->conversation_state = 'awaiting_vehicle';
            $lead->conversation_data = $data;
            $lead->save();

            return [
                'template' => 'ask_make_model_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'action' => 'collect_vehicle',
                'context' => [],
            ];
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

        if (!$makeId && !$otherMake) {

            $data = $lead->conversation_data ?? [];

            if (!is_array($data)) {
                $data = [];
            }

            $attempts = (int) ($data['vehicle_attempts'] ?? 0);
            $attempts++;

            $data['vehicle_attempts'] = $attempts;

            $lead->conversation_data = $data;
            $lead->save();

            if ($attempts >= 3) {
                return $this->guard->escalateToManager(
                    $lead,
                    'Vehicle capture failed: ' . $text
                );
            }

            return [
                'template' => 'ask_make_model_v1',
                'placeholders' => [$lead->name ?: 'there'],
                'action' => 'retry_vehicle',
                'context' => [],
            ];
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

        $data = $lead->conversation_data ?? [];

        if (!is_array($data)) {
            $data = [];
        }

        $data['vehicle_attempts'] = 0;
        $data['vehicle_captured_at'] = now()->toIso8601String();

        $lead->conversation_data = $data;
        $lead->save();

        /*
        |--------------------------------------------------------------------------
        | Ensure client + opportunity
        |--------------------------------------------------------------------------
        */

        $this->leadConversionService->ensureClientAndOpportunity($lead->id);
        $lead->refresh();

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

                if (!$vehicle) {
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
                | This keeps the lead clean after unknown text like Audi Q5 is resolved
                | into vehicle_makes / vehicle_models / vehicles.
                |--------------------------------------------------------------------------
                */

                $lead->update([
                    'vehicle_make_id'  => $make->id,
                    'vehicle_model_id' => $model?->id,
                    'other_make'       => null,
                    'other_model'      => null,
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

        return [
            'template' => 'ask_preferred_time_v1',
            'placeholders' => [$lead->name ?: 'there'],
            'action' => 'collect_timeslot',
            'context' => [],
        ];
    }

    protected function updateState(Lead $lead, string $state): void
    {
        $data = $lead->conversation_data ?? [];

        if (!is_array($data)) {
            $data = [];
        }

        $lead->conversation_state = $state;
        $lead->conversation_data = array_merge($data, [
            'last_state_at' => now()->toIso8601String(),
        ]);

        $lead->save();
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