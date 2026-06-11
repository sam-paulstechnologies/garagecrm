<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audience_segmentations')) {
            return;
        }

        foreach ($this->segments() as $segment) {
            $existing = DB::table('audience_segmentations')
                ->where('key', $segment['key'])
                ->first();

            $payload = array_merge($segment, [
                'is_system_defined' => true,
                'default_enabled' => true,
                'updated_at' => now(),
            ]);

            if ($existing) {
                DB::table('audience_segmentations')
                    ->where('id', $existing->id)
                    ->update($payload);

                continue;
            }

            DB::table('audience_segmentations')->insert(array_merge($payload, [
                'created_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('audience_segmentations')) {
            return;
        }

        DB::table('audience_segmentations')
            ->whereIn('key', array_column($this->segments(), 'key'))
            ->delete();
    }

    private function segments(): array
    {
        return [
            [
                'key' => 'insurance_renewal_due',
                'name' => 'Insurance Renewal Due',
                'category' => 'Vehicle Renewal',
                'description' => 'Customers with vehicle insurance renewal due soon or already overdue.',
                'audience_rule_description' => 'Counts unique clients with a vehicle insurance expiry that is overdue or ready for a renewal follow-up within the next 30 days.',
                'trigger_description' => 'Triggered when vehicle insurance renewal is due soon or overdue.',
                'message_description' => 'Remind the customer that insurance renewal is approaching and offer renewal support.',
                'example_message' => 'Hi {{name}}, your {{vehicle}} insurance is coming up for renewal. Would you like us to help with a quick vehicle check before renewal?',
                'trigger_event' => 'vehicle.insurance_renewal_due',
                'trigger_delay_value' => 30,
                'trigger_delay_unit' => 'days before expiry',
                'sort_order' => 41,
            ],
            [
                'key' => 'mulkia_renewal_due',
                'name' => 'Mulkia Renewal Due',
                'category' => 'Vehicle Renewal',
                'description' => 'Customers with vehicle registration or Mulkia renewal due soon or already overdue.',
                'audience_rule_description' => 'Counts unique clients with a registration/Mulkia expiry that is overdue or ready for a renewal follow-up within the next 30 days.',
                'trigger_description' => 'Triggered when registration/Mulkia renewal is due soon or overdue.',
                'message_description' => 'Remind the customer that registration renewal is approaching and offer inspection or renewal preparation support.',
                'example_message' => 'Hi {{name}}, your {{vehicle}} registration/Mulkia renewal is coming up. Would you like us to help with inspection and renewal preparation?',
                'trigger_event' => 'vehicle.mulkia_renewal_due',
                'trigger_delay_value' => 30,
                'trigger_delay_unit' => 'days before expiry',
                'sort_order' => 42,
            ],
            [
                'key' => 'inspection_renewal_due',
                'name' => 'Inspection Renewal Due',
                'category' => 'Vehicle Renewal',
                'description' => 'Customers with vehicle inspection renewal due soon or already overdue.',
                'audience_rule_description' => 'Counts unique clients with an inspection expiry that is overdue or ready for a renewal follow-up within the next 30 days.',
                'trigger_description' => 'Triggered when vehicle inspection renewal is due soon or overdue.',
                'message_description' => 'Remind the customer that inspection is approaching and offer a pre-inspection check.',
                'example_message' => 'Hi {{name}}, your {{vehicle}} inspection is coming up. Would you like us to help with a pre-inspection check?',
                'trigger_event' => 'vehicle.inspection_renewal_due',
                'trigger_delay_value' => 30,
                'trigger_delay_unit' => 'days before expiry',
                'sort_order' => 43,
            ],
        ];
    }
};
