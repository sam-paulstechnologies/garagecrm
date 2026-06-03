<?php

namespace Database\Seeders;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Conversation;
use App\Models\Job\Booking;
use App\Models\Job\Invoice;
use App\Models\Job\Job;
use App\Models\MessageLog;
use App\Models\System\Company;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Models\WhatsApp\WhatsAppMessage;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JuneDemoDataSeeder extends Seeder
{
    private const MARKER = 'demo_june_2026';
    private const CAMPAIGN = 'SayaraForce June Demo';

    private array $counts = [
        'leads_created' => 0,
        'leads_updated' => 0,
        'clients_created' => 0,
        'clients_updated' => 0,
        'vehicles_created' => 0,
        'vehicles_updated' => 0,
        'opportunities_created' => 0,
        'opportunities_updated' => 0,
        'bookings_created' => 0,
        'bookings_updated' => 0,
        'jobs_created' => 0,
        'jobs_updated' => 0,
        'invoices_created' => 0,
        'invoices_updated' => 0,
        'conversations_created' => 0,
        'conversations_updated' => 0,
        'messages_created' => 0,
        'messages_updated' => 0,
        'whatsapp_messages_created' => 0,
        'whatsapp_messages_updated' => 0,
        'feedback_created' => 0,
        'feedback_updated' => 0,
    ];

    public function run(): void
    {
        $this->abortIfUnsafeEnvironment();

        $company = Company::query()->find(1) ?? Company::query()->orderBy('id')->first();

        if (! $company) {
            throw new \RuntimeException('No company found. JuneDemoDataSeeder will not create cross-company data.');
        }

        $companyId = (int) $company->id;
        $staffIds = User::query()
            ->where('company_id', $companyId)
            ->whereIn('role', ['admin', 'manager', 'mechanic', 'receptionist', 'supervisor'])
            ->pluck('id')
            ->values()
            ->all();

        $vehicles = $this->ensureVehicleCatalog();
        $leadPlan = $this->leadPlan();
        $serviceTypes = $this->serviceTypes();
        $clients = [];
        $leads = [];
        $vehiclesByLead = [];

        Lead::withoutEvents(function () use ($companyId, $staffIds, $vehicles, $leadPlan, $serviceTypes, &$clients, &$leads, &$vehiclesByLead) {
            foreach ($leadPlan as $index => $source) {
                $sequence = $index + 1;
                $date = CarbonImmutable::create(2026, 6, (($index % 30) + 1), 9 + ($index % 8), ($index * 7) % 60, 0);
                $vehicle = $vehicles[$index % count($vehicles)];
                $serviceType = $serviceTypes[$index % count($serviceTypes)];
                $phone = '9715' . str_pad((string) (50000000 + $sequence), 8, '0', STR_PAD_LEFT);
                $email = 'june-demo-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . '@example.test';
                $name = $this->customerNames()[$index % count($this->customerNames())] . ' ' . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
                $status = $this->leadStatusFor($source, $index);
                $followUp = in_array($source, ['imported_recent', 'whatsapp', 'meta', 'google'], true)
                    && ! in_array($status, [Lead::STATUS_CONVERTED, Lead::STATUS_LOST], true);

                $client = Client::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'email' => $email,
                    ],
                    [
                        'name' => $name,
                        'phone' => $phone,
                        'whatsapp' => $phone,
                        'city' => ['Dubai', 'Sharjah', 'Ajman', 'Abu Dhabi'][$index % 4],
                        'country' => 'UAE',
                        'source' => $source,
                        'status' => 'active',
                        'preferred_channel' => $source === 'whatsapp' ? 'whatsapp' : 'phone',
                        'notes' => self::CAMPAIGN . ' client record (' . self::MARKER . ').',
                        'is_vip' => $index % 17 === 0,
                        'is_archived' => false,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
                $this->bump('clients', $client);

                $lead = Lead::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'external_id' => self::MARKER . '-lead-' . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT),
                    ],
                    [
                        'client_id' => $client->id,
                        'name' => $name,
                        'email' => $email,
                        'email_norm' => Lead::normalizeEmail($email),
                        'phone' => $phone,
                        'phone_norm' => Lead::normalizePhone($phone),
                        'status' => $status,
                        'source' => $source,
                        'notes' => $this->leadNotes($source, $serviceType),
                        'assigned_to' => $staffIds ? $staffIds[$index % count($staffIds)] : null,
                        'lead_score_reason' => 'June Demo source tracking and lead journey validation.',
                        'last_contacted_at' => $index % 3 === 0 ? $date->addHours(2) : null,
                        'preferred_channel' => $source === 'whatsapp' ? 'whatsapp' : 'phone',
                        'service_category' => Str::slug($serviceType, '_'),
                        'service_type' => $serviceType,
                        'vehicle_make' => $vehicle['make'],
                        'vehicle_model' => $vehicle['model'],
                        'vehicle_year' => 2018 + ($index % 7),
                        'plate_number' => 'DXB ' . chr(65 + ($index % 20)) . ' ' . str_pad((string) (1000 + $sequence), 4, '0', STR_PAD_LEFT),
                        'lead_temperature' => ['hot', 'warm', 'cold'][$index % 3],
                        'lead_priority' => ['urgent', 'high', 'medium', 'low'][$index % 4],
                        'customer_type' => ['retail', 'fleet', 'corporate'][$index % 3],
                        'follow_up_required' => $followUp,
                        'follow_up_date' => $followUp ? $date->addDays(2)->toDateString() : null,
                        'campaign_name' => self::CAMPAIGN,
                        'retention_tag' => ['service_due', 'ac_check', 'brake_follow_up', 'battery_check'][$index % 4],
                        'is_hot' => in_array($status, [Lead::STATUS_NEW, Lead::STATUS_QUALIFIED], true) && $index % 4 === 0,
                        'score' => 55 + ($index % 40),
                        'is_active' => ! in_array($status, [Lead::STATUS_CONVERTED, Lead::STATUS_LOST], true),
                        'conversation_state' => $status === Lead::STATUS_NEW ? Lead::CONVERSATION_AWAITING_INTENT : Lead::CONVERSATION_IDLE,
                        'conversation_data' => [
                            'marker' => self::MARKER,
                            'demo' => true,
                            'service_type' => $serviceType,
                            'source' => $source,
                        ],
                        'conversation_updated_at' => $date,
                        'external_source' => $source,
                        'external_form_id' => self::MARKER,
                        'external_payload' => [
                            'marker' => self::MARKER,
                            'campaign' => self::CAMPAIGN,
                            'source' => $source,
                            'sequence' => $sequence,
                        ],
                        'external_received_at' => $date,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
                $this->bump('leads', $lead);

                $vehicleRow = Vehicle::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'client_id' => $client->id,
                        'plate_number' => $lead->plate_number,
                    ],
                    [
                        'make_id' => $vehicle['make_id'],
                        'model_id' => $vehicle['model_id'],
                        'year' => (string) $lead->vehicle_year,
                        'color' => ['White', 'Black', 'Silver', 'Blue', 'Grey'][$index % 5],
                        'registration_expiry_date' => $date->addMonths(8)->toDateString(),
                        'insurance_expiry_date' => $date->addMonths(7)->toDateString(),
                        'last_inspection_date' => $date->subMonths(3)->toDateString(),
                        'inspection_expiry_date' => $date->addMonths(9)->toDateString(),
                        'current_mileage' => 32000 + ($index * 710),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]
                );
                $this->bump('vehicles', $vehicleRow);

                $clients[] = $client;
                $leads[] = $lead;
                $vehiclesByLead[$lead->id] = $vehicleRow;
            }
        });

        $opportunities = $this->seedOpportunities($companyId, $staffIds, $leads, $vehiclesByLead);
        $bookings = $this->seedBookings($companyId, $staffIds, $leads, $opportunities, $vehiclesByLead);
        $jobs = $this->seedJobs($companyId, $staffIds, $bookings);
        $this->seedInvoices($companyId, $staffIds, $jobs);
        $this->seedConversations($companyId, $staffIds, $leads);
        $this->seedFeedback($companyId, $leads, $opportunities, $bookings);

        foreach ($this->counts as $key => $value) {
            $this->command?->line(str_replace('_', ' ', $key) . ': ' . $value);
        }
    }

    private function seedOpportunities(int $companyId, array $staffIds, array $leads, array $vehiclesByLead): array
    {
        $opportunities = [];
        $stages = Opportunity::STAGES;

        foreach (array_slice($leads, 0, 22) as $index => $lead) {
            $date = CarbonImmutable::parse($lead->created_at)->addDays(1);
            $vehicle = $vehiclesByLead[$lead->id] ?? null;
            $stage = $stages[$index % count($stages)];

            $opportunity = Opportunity::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                ],
                [
                    'client_id' => $lead->client_id,
                    'vehicle_id' => $vehicle?->id,
                    'vehicle_make_id' => $vehicle?->make_id,
                    'vehicle_model_id' => $vehicle?->model_id,
                    'source' => $lead->source,
                    'assigned_to' => $staffIds ? $staffIds[$index % count($staffIds)] : null,
                    'title' => 'June Demo ' . $lead->service_type . ' Opportunity',
                    'service_type' => $lead->service_type,
                    'stage' => $stage,
                    'priority' => ['low', 'medium', 'high'][$index % 3],
                    'is_converted' => $stage === Opportunity::STAGE_CLOSED_WON,
                    'is_archived' => false,
                    'close_reason' => $stage === Opportunity::STAGE_CLOSED_LOST ? 'June Demo lost opportunity sample' : null,
                    'ai_status' => 'idle',
                    'next_follow_up' => $date->addDays(3)->toDateString(),
                    'expected_duration' => 90 + (($index % 5) * 30),
                    'score' => 50 + ($index % 45),
                    'value' => 350 + (($index % 9) * 275),
                    'expected_close_date' => $date->addDays(5)->toDateString(),
                    'notes' => self::CAMPAIGN . ' opportunity (' . self::MARKER . ').',
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
            $this->bump('opportunities', $opportunity);
            $opportunities[] = $opportunity;
        }

        return $opportunities;
    }

    private function seedBookings(int $companyId, array $staffIds, array $leads, array $opportunities, array $vehiclesByLead): array
    {
        $bookings = [];
        $statuses = [
            Booking::STATUS_PENDING,
            Booking::STATUS_SCHEDULED,
            Booking::STATUS_CONVERTED_TO_JOB,
            Booking::STATUS_LOST,
        ];

        foreach (array_slice($leads, 0, 26) as $index => $lead) {
            $date = CarbonImmutable::create(2026, 6, (($index % 30) + 1), 0, 0, 0);
            $opportunity = $opportunities[$index] ?? null;
            $vehicle = $vehiclesByLead[$lead->id] ?? null;
            $status = $statuses[$index % count($statuses)];

            $booking = Booking::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => self::MARKER . '-booking-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                ],
                [
                    'client_id' => $lead->client_id,
                    'vehicle_id' => $vehicle?->id,
                    'opportunity_id' => $opportunity?->id,
                    'priority' => ['low', 'medium', 'high'][$index % 3],
                    'expected_duration' => 90 + (($index % 4) * 30),
                    'expected_close_date' => $date->addDays(2)->toDateString(),
                    'booking_date' => $date->toDateString(),
                    'slot' => ['morning', 'afternoon', 'evening', 'full_day'][$index % 4],
                    'service_type' => $lead->service_type,
                    'assigned_to' => $staffIds ? $staffIds[$index % count($staffIds)] : null,
                    'pickup_required' => $index % 3 === 0,
                    'pickup_address' => $index % 3 === 0 ? 'June Demo pickup, Business Bay, Dubai' : null,
                    'pickup_contact_number' => $index % 3 === 0 ? $lead->phone : null,
                    'status' => $status,
                    'lost_reason' => $status === Booking::STATUS_LOST ? Booking::LOST_REASON_CUSTOMER_POSTPONED : null,
                    'is_archived' => false,
                    'notes' => self::CAMPAIGN . ' booking (' . self::MARKER . '). Pickup/dropoff demo notes.',
                    'confirmed_at' => in_array($status, [Booking::STATUS_SCHEDULED, Booking::STATUS_CONVERTED_TO_JOB], true) ? $date->subDay() : null,
                    'completed_at' => $status === Booking::STATUS_CONVERTED_TO_JOB ? $date->addHours(4) : null,
                    'cancelled_at' => $status === Booking::STATUS_LOST ? $date->addHours(2) : null,
                    'state_changed_at' => $date,
                    'state_changed_by' => $staffIds[0] ?? null,
                    'created_at' => $date->subDay(),
                    'updated_at' => $date,
                ]
            );
            $this->bump('bookings', $booking);
            $bookings[] = $booking;
        }

        return $bookings;
    }

    private function seedJobs(int $companyId, array $staffIds, array $bookings): array
    {
        $jobs = [];
        $statuses = ['pending', 'in_progress', 'completed'];

        foreach (array_slice($bookings, 0, 18) as $index => $booking) {
            $start = CarbonImmutable::parse($booking->booking_date)->setTime(9 + ($index % 5), 0);
            $end = $start->addMinutes(90 + (($index % 4) * 30));
            $status = $statuses[$index % count($statuses)];

            $job = Job::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'booking_id' => $booking->id,
                ],
                [
                    'client_id' => $booking->client_id,
                    'job_code' => 'JUNE-DEMO-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'start_time' => $start,
                    'end_time' => $status === 'completed' ? $end : null,
                    'description' => self::CAMPAIGN . ' job for ' . $booking->service_type,
                    'work_summary' => $status === 'completed' ? 'June Demo completed service summary.' : 'June Demo work in progress.',
                    'issues_found' => $index % 4 === 0 ? 'Minor wear identified during inspection.' : null,
                    'parts_used' => $index % 5 === 0 ? 'Oil filter, cabin filter' : null,
                    'total_time_minutes' => $status === 'completed' ? $start->diffInMinutes($end) : null,
                    'is_archived' => false,
                    'status' => $status,
                    'assigned_to' => $staffIds ? $staffIds[$index % count($staffIds)] : null,
                    'created_at' => $start,
                    'updated_at' => $end,
                ]
            );
            $this->bump('jobs', $job);
            $jobs[] = $job;
        }

        return $jobs;
    }

    private function seedInvoices(int $companyId, array $staffIds, array $jobs): void
    {
        foreach (array_slice($jobs, 0, 12) as $index => $job) {
            $date = CarbonImmutable::parse($job->start_time ?? '2026-06-01')->addDays(1);

            $invoice = Invoice::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'number' => 'JUNE-DEMO-INV-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                ],
                [
                    'client_id' => $job->client_id,
                    'job_id' => $job->id,
                    'source' => 'generated',
                    'file_path' => null,
                    'url' => null,
                    'file_type' => 'demo',
                    'mime' => null,
                    'size' => null,
                    'hash' => hash('sha256', self::MARKER . '-invoice-' . $index),
                    'version' => 1,
                    'uploaded_by' => $staffIds[0] ?? null,
                    'extracted_text' => self::CAMPAIGN . ' invoice (' . self::MARKER . ').',
                    'amount' => 250 + (($index % 8) * 180),
                    'status' => ['paid', 'pending', 'overdue'][$index % 3],
                    'is_primary' => true,
                    'invoice_date' => $date->toDateString(),
                    'currency' => 'AED',
                    'due_date' => $date->addDays(7)->toDateString(),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
            $this->bump('invoices', $invoice);
        }
    }

    private function seedConversations(int $companyId, array $staffIds, array $leads): void
    {
        foreach (array_slice($leads, 0, 22) as $index => $lead) {
            $date = CarbonImmutable::parse($lead->created_at)->addHours(1);

            $conversation = Conversation::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                ],
                [
                    'client_id' => $lead->client_id,
                    'customer_name' => $lead->name,
                    'customer_phone' => $lead->phone,
                    'subject' => self::CAMPAIGN . ' WhatsApp conversation',
                    'latest_message_at' => $date->addMinutes(12),
                    'last_message_at' => $date->addMinutes(12),
                    'last_message_preview' => 'June Demo WhatsApp conversation sample.',
                    'unread_count' => $index % 4,
                    'is_whatsapp_linked' => true,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
            $this->bump('conversations', $conversation);

            $inbound = MessageLog::query()->updateOrCreate(
                [
                    'provider_message_id' => self::MARKER . '-in-' . $lead->id,
                ],
                [
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                    'conversation_id' => $conversation->id,
                    'direction' => 'in',
                    'channel' => 'whatsapp',
                    'source' => 'human',
                    'to_number' => '971500000000',
                    'from_number' => $lead->phone,
                    'body' => 'Hi, I need help with ' . $lead->service_type . '. ' . self::MARKER,
                    'provider_status' => 'received',
                    'meta' => ['marker' => self::MARKER, 'demo' => true],
                    'read_at' => $index % 4 === 0 ? null : $date->addMinutes(4),
                    'is_ai' => false,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
            $this->bump('messages', $inbound);

            $outbound = MessageLog::query()->updateOrCreate(
                [
                    'provider_message_id' => self::MARKER . '-out-' . $lead->id,
                ],
                [
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                    'conversation_id' => $conversation->id,
                    'direction' => 'out',
                    'channel' => 'whatsapp',
                    'source' => $index % 2 === 0 ? 'ai' : 'human',
                    'to_number' => $lead->phone,
                    'from_number' => '971500000000',
                    'body' => 'Thanks for contacting SayaraForce. We can help with ' . $lead->service_type . '. ' . self::MARKER,
                    'provider_status' => ['sent', 'delivered', 'read'][$index % 3],
                    'meta' => ['marker' => self::MARKER, 'demo' => true],
                    'is_ai' => $index % 2 === 0,
                    'created_at' => $date->addMinutes(12),
                    'updated_at' => $date->addMinutes(12),
                ]
            );
            $this->bump('messages', $outbound);

            $wa = WhatsAppMessage::query()->updateOrCreate(
                [
                    'provider_message_id' => self::MARKER . '-wa-' . $lead->id,
                ],
                [
                    'company_id' => $companyId,
                    'to' => $lead->phone,
                    'direction' => 'out',
                    'status' => ['sent', 'delivered', 'read'][$index % 3],
                    'external_id' => self::MARKER . '-wa-ext-' . $lead->id,
                    'payload' => [
                        'marker' => self::MARKER,
                        'demo' => true,
                        'body' => 'Local demo log only. No WhatsApp send triggered.',
                    ],
                    'created_at' => $date->addMinutes(12),
                    'updated_at' => $date->addMinutes(12),
                ]
            );
            $this->bump('whatsapp_messages', $wa);
        }
    }

    private function seedFeedback(int $companyId, array $leads, array $opportunities, array $bookings): void
    {
        if (! DB::getSchemaBuilder()->hasTable('feedback')) {
            return;
        }

        foreach (array_slice($leads, 0, 10) as $index => $lead) {
            $opportunity = $opportunities[$index] ?? null;
            $booking = $bookings[$index] ?? null;
            $comment = self::CAMPAIGN . ' feedback ' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) . ' (' . self::MARKER . ')';
            $exists = DB::table('feedback')
                ->where('company_id', $companyId)
                ->where('lead_id', $lead->id)
                ->where('comment', $comment)
                ->exists();

            DB::table('feedback')->updateOrInsert(
                [
                    'company_id' => $companyId,
                    'lead_id' => $lead->id,
                    'comment' => $comment,
                ],
                [
                    'booking_id' => $booking?->id,
                    'opportunity_id' => $opportunity?->id,
                    'rating' => [5, 4, 5, 3, 2][$index % 5],
                    'sentiment' => ['positive', 'positive', 'positive', 'neutral', 'negative'][$index % 5],
                    'source' => 'admin',
                    'created_at' => CarbonImmutable::create(2026, 6, (($index % 30) + 1), 16, 0),
                    'updated_at' => now(),
                ]
            );

            $this->counts[$exists ? 'feedback_updated' : 'feedback_created']++;
        }
    }

    private function ensureVehicleCatalog(): array
    {
        $pairs = [
            ['Toyota', 'Camry'],
            ['Nissan', 'Patrol'],
            ['Jeep', 'Wrangler'],
            ['BMW', '5 Series'],
            ['Mercedes', 'C-Class'],
            ['Honda', 'Accord'],
            ['Lexus', 'RX'],
            ['Ford', 'Explorer'],
            ['Range Rover', 'Sport'],
            ['Hyundai', 'Tucson'],
        ];

        return array_map(function (array $pair): array {
            $make = VehicleMake::query()->firstOrCreate(['name' => $pair[0]], ['alias' => []]);
            $model = VehicleModel::query()->firstOrCreate(
                ['make_id' => $make->id, 'name' => $pair[1]],
                ['alias' => []]
            );

            return [
                'make' => $make->name,
                'model' => $model->name,
                'make_id' => $make->id,
                'model_id' => $model->id,
            ];
        }, $pairs);
    }

    private function leadPlan(): array
    {
        return [
            ...array_fill(0, 25, 'whatsapp'),
            ...array_fill(0, 18, 'meta'),
            ...array_fill(0, 12, 'google'),
            ...array_fill(0, 10, 'website'),
            ...array_fill(0, 8, 'tiktok'),
            ...array_fill(0, 8, 'manual'),
            ...array_fill(0, 7, 'walk_in'),
            ...array_fill(0, 6, 'referral'),
            ...array_fill(0, 4, 'imported_recent'),
            ...array_fill(0, 2, 'imported_historic'),
        ];
    }

    private function serviceTypes(): array
    {
        return [
            'Oil Change',
            'AC Repair',
            'Brake Service',
            'Battery Replacement',
            'Tyre Replacement',
            'Full Service',
            'Engine Diagnosis',
            'Detailing',
            'Car Wash',
            'Transmission Check',
        ];
    }

    private function customerNames(): array
    {
        return [
            'Omar Al Mansoori',
            'Aisha Khan',
            'Ravi Menon',
            'Fatima Al Nuaimi',
            'Bilal Sheikh',
            'Noura Haddad',
            'Ahmed Farooq',
            'Maya Joseph',
            'Zain Malik',
            'Leena Thomas',
        ];
    }

    private function leadStatusFor(string $source, int $index): string
    {
        if ($source === 'imported_historic') {
            return Lead::STATUS_LOST;
        }

        return [
            Lead::STATUS_NEW,
            Lead::STATUS_ATTEMPTING,
            Lead::STATUS_QUALIFIED,
            Lead::STATUS_CONVERTED,
            Lead::STATUS_LOST,
        ][$index % 5];
    }

    private function leadNotes(string $source, string $serviceType): string
    {
        if ($source === 'imported_recent') {
            return self::CAMPAIGN . ' imported recent lead (' . self::MARKER . '). Active follow-up required; conversation required. No WhatsApp message sent automatically.';
        }

        if ($source === 'imported_historic') {
            return self::CAMPAIGN . ' imported historic lead (' . self::MARKER . '). Historic data import; inactive history record.';
        }

        return self::CAMPAIGN . ' lead (' . self::MARKER . ') interested in ' . $serviceType . '.';
    }

    private function bump(string $base, object $model): void
    {
        $this->counts[$base . ($model->wasRecentlyCreated ? '_created' : '_updated')]++;
    }

    private function abortIfUnsafeEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('JuneDemoDataSeeder is blocked outside local/testing environments.');
        }
    }
}
