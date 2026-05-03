<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

class LaunchSetupController extends Controller
{
    public function edit()
    {
        $company = auth()->user()->company;

        abort_if(!$company, 404, 'Company not found.');

        return view('admin.settings.launch-setup', [
            'company' => $company,
            'workingHours' => $this->arrayValue($company->working_hours ?? []),
            'bookingRules' => $this->arrayValue($company->booking_rules ?? []),
            'serviceAreas' => $this->arrayValue($company->service_areas ?? []),
            'completion' => $this->completionScore($company),
            'checklist' => $this->checklist($company),
        ]);
    }

    public function update(Request $request)
    {
        $company = auth()->user()->company;

        abort_if(!$company, 404, 'Company not found.');

        $data = $request->validate([
            'legal_name' => ['nullable', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:50'],
            'business_email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'location_pin' => ['nullable', 'string', 'max:1000'],

            'manager_name' => ['nullable', 'string', 'max:255'],
            'manager_phone' => ['nullable', 'string', 'max:50'],
            'manager_email' => ['nullable', 'email', 'max:150'],
            'send_manager_password_reset' => ['nullable'],

            'working_hours.open_time' => ['nullable', 'string', 'max:20'],
            'working_hours.close_time' => ['nullable', 'string', 'max:20'],
            'working_hours.weekly_off' => ['nullable', 'string', 'max:50'],
            'working_hours.emergency_available' => ['nullable'],

            'booking_rules.max_bookings_per_slot' => ['nullable', 'integer', 'min:1', 'max:100'],
            'booking_rules.pickup_available' => ['nullable'],
            'booking_rules.dropoff_available' => ['nullable'],
            'booking_rules.default_slot_duration' => ['nullable', 'string', 'max:50'],

            'service_areas' => ['nullable', 'string', 'max:2000'],
        ]);

        $workingHours = [
            'open_time' => $data['working_hours']['open_time'] ?? null,
            'close_time' => $data['working_hours']['close_time'] ?? null,
            'weekly_off' => $data['working_hours']['weekly_off'] ?? null,
            'emergency_available' => $request->boolean('working_hours.emergency_available'),
        ];

        $bookingRules = [
            'max_bookings_per_slot' => $data['booking_rules']['max_bookings_per_slot'] ?? null,
            'pickup_available' => $request->boolean('booking_rules.pickup_available'),
            'dropoff_available' => $request->boolean('booking_rules.dropoff_available'),
            'default_slot_duration' => $data['booking_rules']['default_slot_duration'] ?? null,
        ];

        $serviceAreas = collect(explode("\n", (string) ($data['service_areas'] ?? '')))
            ->map(fn ($area) => trim($area))
            ->filter()
            ->values()
            ->toArray();

        $update = [
            'legal_name' => $data['legal_name'] ?? null,
            'business_phone' => $data['business_phone'] ?? null,
            'business_email' => $data['business_email'] ?? null,
            'address' => $data['address'] ?? null,
            'location_pin' => $data['location_pin'] ?? null,

            'manager_name' => $data['manager_name'] ?? null,
            'manager_phone' => $data['manager_phone'] ?? null,
            'manager_email' => $data['manager_email'] ?? null,

            'working_hours' => $workingHours,
            'booking_rules' => $bookingRules,
            'service_areas' => $serviceAreas,
        ];

        $completion = $this->completionScore((object) array_merge($company->toArray(), $update));

        if (Schema::hasColumn('companies', 'launch_setup_status')) {
            $update['launch_setup_status'] = $completion >= 100 ? 'completed' : 'pending';
        }

        if (Schema::hasColumn('companies', 'launch_setup_completed_at')) {
            $update['launch_setup_completed_at'] = $completion >= 100 ? now() : null;
        }

        $company->update($update);

        $message = 'Launch setup updated successfully.';
        $warning = null;

        if ($request->boolean('send_manager_password_reset')) {
            $managerEmail = $data['manager_email'] ?? null;

            if (!$managerEmail) {
                $warning = 'Manager password reset was not sent because manager email is missing.';
            } else {
                try {
                    $status = Password::sendResetLink([
                        'email' => $managerEmail,
                    ]);

                    if ($status === Password::RESET_LINK_SENT) {
                        $message = 'Launch setup updated successfully. Password reset link sent to manager.';
                    } else {
                        $warning = 'Launch setup saved, but password reset was not sent. Make sure the manager email exists as a user.';
                    }
                } catch (\Throwable $e) {
                    Log::warning('[LaunchSetup] Manager password reset failed', [
                        'company_id' => $company->id,
                        'manager_email' => $managerEmail,
                        'error' => $e->getMessage(),
                    ]);

                    $warning = 'Launch setup saved, but password reset failed. Please check manager user account.';
                }
            }
        }

        return redirect()
            ->route('admin.settings.launch-setup.edit')
            ->with('success', $message)
            ->with('warning', $warning);
    }

    protected function checklist($company): array
    {
        $workingHours = $this->arrayValue($company->working_hours ?? []);
        $bookingRules = $this->arrayValue($company->booking_rules ?? []);
        $serviceAreas = $this->arrayValue($company->service_areas ?? []);

        /*
        |--------------------------------------------------------------------------
        | WhatsApp readiness
        |--------------------------------------------------------------------------
        | WABA ID is not required here because current sending/receiving is working
        | with meta_phone_number_id + meta_access_token + is_whatsapp_active.
        |--------------------------------------------------------------------------
        */
        $whatsappReady =
            !empty($company->meta_phone_number_id)
            && !empty($company->meta_access_token)
            && (bool) ($company->is_whatsapp_active ?? false);

        return [
            [
                'label' => 'Business legal name added',
                'done' => filled($company->legal_name ?? null),
            ],
            [
                'label' => 'Business phone added',
                'done' => filled($company->business_phone ?? null),
            ],
            [
                'label' => 'Business email added',
                'done' => filled($company->business_email ?? null),
            ],
            [
                'label' => 'Garage address added',
                'done' => filled($company->address ?? null),
            ],
            [
                'label' => 'Google Maps location pin added',
                'done' => filled($company->location_pin ?? null),
            ],
            [
                'label' => 'Manager contact added',
                'done' => filled($company->manager_name ?? null)
                    && filled($company->manager_phone ?? null)
                    && filled($company->manager_email ?? null),
            ],
            [
                'label' => 'Working hours added',
                'done' => filled($workingHours['open_time'] ?? null)
                    && filled($workingHours['close_time'] ?? null),
            ],
            [
                'label' => 'Booking rules added',
                'done' => filled($bookingRules['max_bookings_per_slot'] ?? null),
            ],
            [
                'label' => 'Service areas added',
                'done' => count($serviceAreas) > 0,
            ],
            [
                'label' => 'WhatsApp integration active',
                'done' => $whatsappReady,
            ],
        ];
    }

    protected function completionScore($company): int
    {
        $checklist = $this->checklist($company);
        $total = count($checklist);

        if ($total === 0) {
            return 0;
        }

        $done = collect($checklist)
            ->where('done', true)
            ->count();

        return (int) round(($done / $total) * 100);
    }

    protected function arrayValue($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}