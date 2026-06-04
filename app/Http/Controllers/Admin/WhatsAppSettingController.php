<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppSettingController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    public function edit()
    {
        $settings = CompanySetting::where('company_id', $this->companyId())
            ->where('group', 'whatsapp')
            ->pluck('value', 'key')
            ->toArray();

        /*
        |--------------------------------------------------------------------------
        | Backward compatibility
        |--------------------------------------------------------------------------
        | Some older services may read dotted keys like whatsapp.manager_number,
        | while the current Blade uses simple form keys like whatsapp_manager_number.
        |--------------------------------------------------------------------------
        */
        $settings['whatsapp_manager_number'] = $settings['whatsapp_manager_number']
            ?? $settings['whatsapp.manager_number']
            ?? '';

        $settings['google_review_link'] = $settings['google_review_link']
            ?? $settings['whatsapp.google_review_link']
            ?? '';

        $settings['garage_location_link'] = $settings['garage_location_link']
            ?? $settings['whatsapp.garage_location_link']
            ?? '';

        $settings['whatsapp_active'] = $settings['whatsapp_active']
            ?? $settings['whatsapp.active']
            ?? '1';

        $settings['whatsapp_provider'] = $settings['whatsapp_provider']
            ?? $settings['whatsapp.provider']
            ?? 'meta';

        $settings['positive_feedback_threshold'] = $settings['positive_feedback_threshold']
            ?? $settings['whatsapp.positive_feedback_threshold']
            ?? '4';

        return view('admin.whatsapp.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'whatsapp_active'             => ['required', 'in:0,1'],
            'whatsapp_provider'           => ['required', 'in:meta,twilio'],
            'whatsapp_manager_number'     => ['nullable', 'string', 'max:32'],
            'google_review_link'          => ['nullable', 'url', 'max:512'],
            'garage_location_link'        => ['nullable', 'url', 'max:512'],
            'positive_feedback_threshold' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Save both new/simple keys and dotted legacy keys
        |--------------------------------------------------------------------------
        | This prevents current pages and older WhatsApp services from breaking.
        |--------------------------------------------------------------------------
        */
        $settingsToSave = [
            // Simple keys used by current Blade
            'whatsapp_active'             => $data['whatsapp_active'],
            'whatsapp_provider'           => $data['whatsapp_provider'],
            'whatsapp_manager_number'     => $data['whatsapp_manager_number'] ?? '',
            'google_review_link'          => $data['google_review_link'] ?? '',
            'garage_location_link'        => $data['garage_location_link'] ?? '',
            'positive_feedback_threshold' => (string) $data['positive_feedback_threshold'],

            // Dotted keys used by older services / escalation code
            'whatsapp.active'                      => $data['whatsapp_active'],
            'whatsapp.provider'                    => $data['whatsapp_provider'],
            'whatsapp.manager_number'              => $data['whatsapp_manager_number'] ?? '',
            'whatsapp.google_review_link'          => $data['google_review_link'] ?? '',
            'whatsapp.garage_location_link'        => $data['garage_location_link'] ?? '',
            'whatsapp.positive_feedback_threshold' => (string) $data['positive_feedback_threshold'],
        ];

        $companyId = $this->companyId();

        foreach ($settingsToSave as $key => $value) {
            CompanySetting::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'key'        => $key,
                ],
                [
                    'group' => 'whatsapp',
                    'value' => $value,
                ]
            );
        }

        return back()->with('success', 'WhatsApp settings saved successfully.');
    }

    public function resetUatByPhone(Request $request)
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'uat_phone' => ['required', 'string', 'max:32'],
            'confirm_uat_reset' => ['accepted'],
        ], [
            'confirm_uat_reset.accepted' => 'Please confirm that you want to delete this test data.',
        ]);

        $phone = $this->normalizePhone($data['uat_phone']);

        if (! $phone) {
            return back()->withErrors([
                'uat_phone' => 'Please enter a valid phone number.',
            ]);
        }

        $summary = DB::transaction(function () use ($companyId, $phone) {
            $clientIds = collect();

            if (Schema::hasTable('clients')) {
                $clientQuery = DB::table('clients')->where('company_id', $companyId);

                $clientQuery->where(function ($query) use ($phone) {
                    if (Schema::hasColumn('clients', 'phone')) {
                        $query->orWhere('phone', $phone);
                    }

                    if (Schema::hasColumn('clients', 'phone_norm')) {
                        $query->orWhere('phone_norm', $phone);
                    }

                    if (Schema::hasColumn('clients', 'whatsapp')) {
                        $query->orWhere('whatsapp', $phone);
                    }
                });

                $clientIds = $clientQuery->pluck('id');
            }

            $leadIds = collect();

            if (Schema::hasTable('leads')) {
                $leadQuery = DB::table('leads')->where('company_id', $companyId);

                $leadQuery->where(function ($query) use ($phone, $clientIds) {
                    if (Schema::hasColumn('leads', 'phone')) {
                        $query->orWhere('phone', $phone);
                    }

                    if (Schema::hasColumn('leads', 'phone_norm')) {
                        $query->orWhere('phone_norm', $phone);
                    }

                    if (Schema::hasColumn('leads', 'client_id') && $clientIds->isNotEmpty()) {
                        $query->orWhereIn('client_id', $clientIds);
                    }
                });

                $leadIds = $leadQuery->pluck('id');
            }

            $opportunityIds = collect();

            if (Schema::hasTable('opportunities')) {
                $opportunityQuery = DB::table('opportunities')->where('company_id', $companyId);

                $opportunityQuery->where(function ($query) use ($clientIds, $leadIds) {
                    if (Schema::hasColumn('opportunities', 'client_id') && $clientIds->isNotEmpty()) {
                        $query->orWhereIn('client_id', $clientIds);
                    }

                    if (Schema::hasColumn('opportunities', 'lead_id') && $leadIds->isNotEmpty()) {
                        $query->orWhereIn('lead_id', $leadIds);
                    }
                });

                $opportunityIds = $opportunityQuery->pluck('id');
            }

            $bookingIds = collect();

            if (Schema::hasTable('bookings')) {
                $bookingQuery = DB::table('bookings')->where('company_id', $companyId);

                $bookingQuery->where(function ($query) use ($clientIds, $opportunityIds) {
                    if (Schema::hasColumn('bookings', 'client_id') && $clientIds->isNotEmpty()) {
                        $query->orWhereIn('client_id', $clientIds);
                    }

                    if (Schema::hasColumn('bookings', 'opportunity_id') && $opportunityIds->isNotEmpty()) {
                        $query->orWhereIn('opportunity_id', $opportunityIds);
                    }
                });

                $bookingIds = $bookingQuery->pluck('id');
            }

            $jobIds = collect();

            if (Schema::hasTable('jobs')) {
                $jobQuery = DB::table('jobs')->where('company_id', $companyId);

                $jobQuery->where(function ($query) use ($clientIds, $bookingIds) {
                    if (Schema::hasColumn('jobs', 'client_id') && $clientIds->isNotEmpty()) {
                        $query->orWhereIn('client_id', $clientIds);
                    }

                    if (Schema::hasColumn('jobs', 'booking_id') && $bookingIds->isNotEmpty()) {
                        $query->orWhereIn('booking_id', $bookingIds);
                    }
                });

                $jobIds = $jobQuery->pluck('id');
            }

            $deleted = [
                'message_logs' => 0,
                'whatsapp_messages' => 0,
                'conversations' => 0,
                'invoices' => 0,
                'jobs' => 0,
                'bookings' => 0,
                'opportunities' => 0,
                'leads' => 0,
                'clients' => 0,
            ];

            if (Schema::hasTable('message_logs')) {
                $messageQuery = DB::table('message_logs')->where(function ($query) use ($phone, $leadIds) {
                    if (Schema::hasColumn('message_logs', 'lead_id') && $leadIds->isNotEmpty()) {
                        $query->orWhereIn('lead_id', $leadIds);
                    }

                    if (Schema::hasColumn('message_logs', 'from_number')) {
                        $query->orWhere('from_number', $phone);
                    }

                    if (Schema::hasColumn('message_logs', 'to_number')) {
                        $query->orWhere('to_number', $phone);
                    }
                });

                if (Schema::hasColumn('message_logs', 'company_id')) {
                    $messageQuery->where('company_id', $companyId);
                }

                $deleted['message_logs'] = $messageQuery->delete();
            }

            if (Schema::hasTable('whatsapp_messages')) {
                $waQuery = DB::table('whatsapp_messages')->where(function ($query) use ($phone, $leadIds) {
                    if (Schema::hasColumn('whatsapp_messages', 'lead_id') && $leadIds->isNotEmpty()) {
                        $query->orWhereIn('lead_id', $leadIds);
                    }

                    if (Schema::hasColumn('whatsapp_messages', 'to')) {
                        $query->orWhere('to', $phone)->orWhere('to', '+' . $phone);
                    }

                    if (Schema::hasColumn('whatsapp_messages', 'phone')) {
                        $query->orWhere('phone', $phone)->orWhere('phone', '+' . $phone);
                    }

                    if (Schema::hasColumn('whatsapp_messages', 'payload')) {
                        $query->orWhere('payload', 'like', '%' . $phone . '%');
                    }
                });

                if (Schema::hasColumn('whatsapp_messages', 'company_id')) {
                    $waQuery->where('company_id', $companyId);
                }

                $deleted['whatsapp_messages'] = $waQuery->delete();
            }

            if (Schema::hasTable('conversations')) {
                $conversationQuery = DB::table('conversations')->where(function ($query) use ($clientIds, $leadIds, $phone) {
                    if (Schema::hasColumn('conversations', 'lead_id') && $leadIds->isNotEmpty()) {
                        $query->orWhereIn('lead_id', $leadIds);
                    }

                    if (Schema::hasColumn('conversations', 'client_id') && $clientIds->isNotEmpty()) {
                        $query->orWhereIn('client_id', $clientIds);
                    }

                    if (Schema::hasColumn('conversations', 'phone')) {
                        $query->orWhere('phone', $phone);
                    }
                });

                if (Schema::hasColumn('conversations', 'company_id')) {
                    $conversationQuery->where('company_id', $companyId);
                }

                $deleted['conversations'] = $conversationQuery->delete();
            }

            if (Schema::hasTable('invoices')) {
                $invoiceQuery = DB::table('invoices')->where('company_id', $companyId);

                $invoiceQuery->where(function ($query) use ($clientIds, $jobIds) {
                    if (Schema::hasColumn('invoices', 'client_id') && $clientIds->isNotEmpty()) {
                        $query->orWhereIn('client_id', $clientIds);
                    }

                    if (Schema::hasColumn('invoices', 'job_id') && $jobIds->isNotEmpty()) {
                        $query->orWhereIn('job_id', $jobIds);
                    }
                });

                $deleted['invoices'] = $invoiceQuery->delete();
            }

            if (Schema::hasTable('jobs') && $jobIds->isNotEmpty()) {
                $deleted['jobs'] = DB::table('jobs')
                    ->where('company_id', $companyId)
                    ->whereIn('id', $jobIds)
                    ->delete();
            }

            if (Schema::hasTable('bookings') && $bookingIds->isNotEmpty()) {
                $deleted['bookings'] = DB::table('bookings')
                    ->where('company_id', $companyId)
                    ->whereIn('id', $bookingIds)
                    ->delete();
            }

            if (Schema::hasTable('opportunities') && $opportunityIds->isNotEmpty()) {
                $deleted['opportunities'] = DB::table('opportunities')
                    ->where('company_id', $companyId)
                    ->whereIn('id', $opportunityIds)
                    ->delete();
            }

            if (Schema::hasTable('leads') && $leadIds->isNotEmpty()) {
                $deleted['leads'] = DB::table('leads')
                    ->where('company_id', $companyId)
                    ->whereIn('id', $leadIds)
                    ->delete();
            }

            if (Schema::hasTable('clients') && $clientIds->isNotEmpty()) {
                $deleted['clients'] = DB::table('clients')
                    ->where('company_id', $companyId)
                    ->whereIn('id', $clientIds)
                    ->delete();
            }

            $summary = [
                'phone' => $phone,
                'client_ids' => $clientIds->values()->all(),
                'lead_ids' => $leadIds->values()->all(),
                'opportunity_ids' => $opportunityIds->values()->all(),
                'booking_ids' => $bookingIds->values()->all(),
                'job_ids' => $jobIds->values()->all(),
                'deleted' => $deleted,
            ];

            Log::warning('[UAT Reset] WhatsApp test data deleted', [
                'company_id' => $companyId,
                'user_id' => auth()->id(),
                'phone' => $this->maskPhone($phone),
                'deleted' => $deleted,
                'client_count' => $clientIds->count(),
                'lead_count' => $leadIds->count(),
                'opportunity_count' => $opportunityIds->count(),
                'booking_count' => $bookingIds->count(),
                'job_count' => $jobIds->count(),
            ]);

            return $summary;
        });

        return back()
            ->with('success', 'UAT test data deleted for +' . $summary['phone'])
            ->with('uat_reset_summary', $summary);
    }

    protected function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        if (str_starts_with($phone, '05')) {
            $phone = '971' . substr($phone, 1);
        }

        return preg_match('/^\d{8,20}$/', $phone) ? $phone : null;
    }

    protected function maskPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        return str_repeat('*', max(strlen($digits) - 4, 0)).substr($digits, -4);
    }
}
