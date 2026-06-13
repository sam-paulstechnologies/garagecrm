<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Job\Job;
use App\Models\Shared\Communication;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Services\Journey\ServiceJourneyIntegrityService;
use App\Services\WhatsApp\SendWhatsAppMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    protected const STATUS_PENDING = 'pending';
    protected const STATUS_SCHEDULED = 'scheduled';
    protected const STATUS_CONVERTED_TO_JOB = 'converted_to_job';
    protected const STATUS_LOST = 'lost';

    protected const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SCHEDULED,
    ];

    protected const BOOKING_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SCHEDULED,
        self::STATUS_CONVERTED_TO_JOB,
        self::STATUS_LOST,
    ];

    protected const LOST_REASONS = [
        'cancelled_by_customer',
        'rejected_by_garage',
        'no_show',
        'slot_unavailable',
        'duplicate',
        'wrong_booking',
        'price_issue',
        'customer_postponed',
        'other',
    ];

    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $bucket = trim((string) $request->get('bucket', ''));

        $bookings = Booking::with([
                'client',
                'opportunity',
                'vehicleData.make',
                'vehicleData.model',
                'assignedUser',
            ])
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            }, function ($query) {
                $query->whereIn('status', self::ACTIVE_STATUSES);
            })
            ->when($bucket !== '', function ($query) use ($bucket) {
                $this->applyBookingBucketFilter($query, $bucket);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('service_type', 'like', "%{$q}%")
                        ->orWhere('slot', 'like', "%{$q}%")
                        ->orWhere('status', 'like', "%{$q}%")
                        ->orWhere('priority', 'like', "%{$q}%")
                        ->orWhereHas('client', function ($clientQuery) use ($q) {
                            $clientQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%")
                                ->orWhere('whatsapp', 'like', "%{$q}%");
                        })
                        ->orWhereHas('vehicleData.make', function ($makeQuery) use ($q) {
                            $makeQuery->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('vehicleData.model', function ($modelQuery) use ($q) {
                            $modelQuery->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('vehicleData', function ($vehicleQuery) use ($q) {
                            $vehicleQuery->where('plate_number', 'like', "%{$q}%")
                                ->orWhere('vin', 'like', "%{$q}%");
                        });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'q' => $q,
            'status' => $status,
            'bucket' => $bucket,
            'bookingCounts' => $this->bookingCounts($companyId),
            'bucketCounts' => $this->bookingBucketCounts($companyId),
        ]);
    }

    public function archived()
    {
        $companyId = $this->companyId();

        $bookings = Booking::with([
                'client',
                'opportunity',
                'vehicleData.make',
                'vehicleData.model',
                'assignedUser',
            ])
            ->where('company_id', $companyId)
            ->where('is_archived', true)
            ->latest()
            ->paginate(20);

        return view('admin.bookings.archived', compact('bookings'));
    }

    public function create()
    {
        $companyId = $this->companyId();

        return view('admin.bookings.create', [
            'clients' => Client::where('company_id', $companyId)->orderBy('name')->get(),

            'opportunities' => Opportunity::where('company_id', $companyId)
                ->where('is_archived', false)
                ->whereIn('stage', Opportunity::ACTIVE_STAGES)
                ->with(['client:id,name,phone,whatsapp,email', 'vehicle.make', 'vehicle.model', 'vehicleMake:id,name', 'vehicleModel:id,name'])
                ->latest()
                ->get(),

            'vehicles' => Vehicle::with(['make', 'model'])
                ->where('company_id', $companyId)
                ->latest()
                ->get(),

            'users' => $this->assignableUsers($companyId),

            'vehicleMakes' => VehicleMake::orderBy('name')->get(),
            'vehicleModels' => VehicleModel::orderBy('name')->get(),

            'bookingStatuses' => self::BOOKING_STATUSES,
            'lostReasons' => self::LOST_REASONS,

            'slotUsage' => $this->slotUsage($companyId),
            'slotCapacities' => $this->slotCapacities($companyId),
        ]);
    }

    public function store(Request $request)
    {
        $companyId = $this->companyId();

        $data = $this->validatedData($request, $companyId, true);

        try {
            DB::transaction(function () use (&$data, $companyId, $request) {
                $data['client_id'] = $this->resolveClientId($data, $companyId);

                $this->preventDuplicateOpportunityBooking($data, $companyId);

                if (empty($data['vehicle_id']) && ! empty($data['opportunity_id'])) {
                    $opportunity = Opportunity::where('company_id', $companyId)
                        ->find($data['opportunity_id']);

                    if ($opportunity?->vehicle_id) {
                        $data['vehicle_id'] = $opportunity->vehicle_id;
                    }
                }

                if (empty($data['vehicle_id'])) {
                    $data['vehicle_id'] = $this->resolveVehicleId($data, $companyId);
                }

                $this->validateLinkedRecords($data, $companyId);

                if (
                    Schema::hasColumn('bookings', 'lead_id')
                    && ! empty($data['opportunity_id'])
                    && empty($data['lead_id'])
                ) {
                    $data['lead_id'] = Opportunity::where('company_id', $companyId)
                        ->where('id', $data['opportunity_id'])
                        ->value('lead_id');
                }

                $data['status'] = $data['status'] ?? self::STATUS_SCHEDULED;

                $this->validateStatusRequirements($data);

                $allowOverbooking = $request->boolean('allow_overbooking');
                $overbookingReason = trim((string) $request->input('overbooking_reason', ''));

                $this->ensureSlotAvailable(
                    companyId: $companyId,
                    bookingDate: $data['booking_date'],
                    slot: $data['slot'],
                    excludeBookingId: null,
                    allowOverbooking: $allowOverbooking,
                    overbookingReason: $overbookingReason
                );

                $data['company_id'] = $companyId;
                $data['pickup_required'] = $request->boolean('pickup_required');
                $data['is_archived'] = false;
                $data['state_changed_at'] = now();
                $data['state_changed_by'] = auth()->id();

                if ($data['status'] !== self::STATUS_LOST) {
                    $data['lost_reason'] = null;
                }

                if ($allowOverbooking) {
                    $data['notes'] = trim(($data['notes'] ?? '') . "\n\nOverbooking exception: " . $overbookingReason);
                }

                $booking = Booking::create($this->bookingPayload($data));
                $booking = app(ServiceJourneyIntegrityService::class)
                    ->ensureBookingHasUpstreamJourney($booking, ['source' => 'admin_booking_store']);

                if (
                    $booking->status === self::STATUS_SCHEDULED
                    && ! $this->bookingConfirmationAlreadySent($booking)
                ) {
                    $this->handleScheduledBooking($booking);
                }

                if ($booking->status === self::STATUS_CONVERTED_TO_JOB) {
                    $this->createJobFromBooking($booking);
                    $this->markOpportunityConvertedToJob($booking);
                }

                if ($booking->status === self::STATUS_LOST) {
                    $this->markOpportunityLostFromBooking($booking);
                }
            });
        } catch (\Throwable $e) {
            Log::error('[BookingController] Store failed', [
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['slot' => $e->getMessage() ?: 'Unable to create booking.'])
                ->withInput();
        }

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'Booking created successfully.');
    }

    public function show(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $booking->load([
            'client',
            'opportunity',
            'vehicleData.make',
            'vehicleData.model',
            'assignedUser',
        ]);

        $communications = Communication::where('company_id', $booking->company_id)
            ->where('booking_id', $booking->id)
            ->latest()
            ->paginate(10);

        return view('admin.bookings.show', compact('booking', 'communications'));
    }

    public function edit(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $companyId = $this->companyId();

        $booking->load([
            'client',
            'opportunity',
            'vehicleData.make',
            'vehicleData.model',
            'assignedUser',
        ]);

        return view('admin.bookings.edit', [
            'booking' => $booking,

            'clients' => Client::where('company_id', $companyId)->orderBy('name')->get(),

            'opportunities' => Opportunity::where('company_id', $companyId)
                ->where('is_archived', false)
                ->whereIn('stage', Opportunity::ACTIVE_STAGES)
                ->with(['client:id,name,phone,whatsapp,email', 'vehicle.make', 'vehicle.model', 'vehicleMake:id,name', 'vehicleModel:id,name'])
                ->latest()
                ->get(),

            'vehicles' => Vehicle::with(['make', 'model'])
                ->where('company_id', $companyId)
                ->latest()
                ->get(),

            'users' => $this->assignableUsers($companyId),

            'vehicleMakes' => VehicleMake::orderBy('name')->get(),
            'vehicleModels' => VehicleModel::orderBy('name')->get(),

            'bookingStatuses' => self::BOOKING_STATUSES,
            'lostReasons' => self::LOST_REASONS,

            'slotUsage' => $this->slotUsage($companyId, $booking->id),
            'slotCapacities' => $this->slotCapacities($companyId),
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        $companyId = $this->companyId();

        $data = $this->validatedData($request, $companyId, false);

        try {
            DB::transaction(function () use (&$data, $booking, $request) {
                $data['client_id'] = $booking->client_id;
                $data['opportunity_id'] = $booking->opportunity_id;

                if (! array_key_exists('name', $data) || trim((string) $data['name']) === '') {
                    $data['name'] = $booking->name;
                }

                if (empty($data['vehicle_id']) && ! empty($data['opportunity_id'])) {
                    $opportunity = Opportunity::where('company_id', $booking->company_id)
                        ->find($data['opportunity_id']);

                    if ($opportunity?->vehicle_id) {
                        $data['vehicle_id'] = $opportunity->vehicle_id;
                    }
                }

                if (empty($data['vehicle_id'])) {
                    $data['vehicle_id'] = $this->resolveVehicleId($data, $booking->company_id);
                }

                $this->validateLinkedRecords($data, $booking->company_id);

                if (
                    Schema::hasColumn('bookings', 'lead_id')
                    && ! empty($data['opportunity_id'])
                    && empty($data['lead_id'])
                ) {
                    $data['lead_id'] = Opportunity::where('company_id', $booking->company_id)
                        ->where('id', $data['opportunity_id'])
                        ->value('lead_id');
                }

                $this->validateStatusRequirements($data);

                $allowOverbooking = $request->boolean('allow_overbooking');
                $overbookingReason = trim((string) $request->input('overbooking_reason', ''));

                $this->ensureSlotAvailable(
                    companyId: $booking->company_id,
                    bookingDate: $data['booking_date'],
                    slot: $data['slot'],
                    excludeBookingId: $booking->id,
                    allowOverbooking: $allowOverbooking,
                    overbookingReason: $overbookingReason
                );

                $oldStatus = (string) $booking->status;

                $data['pickup_required'] = $request->boolean('pickup_required');
                $data['state_changed_at'] = now();
                $data['state_changed_by'] = auth()->id();

                if ($data['status'] !== self::STATUS_LOST) {
                    $data['lost_reason'] = null;
                }

                if ($allowOverbooking) {
                    $data['notes'] = trim(($data['notes'] ?? '') . "\n\nOverbooking exception: " . $overbookingReason);
                }

                $booking->update($this->bookingPayload($data));
                $booking = app(ServiceJourneyIntegrityService::class)
                    ->ensureBookingHasUpstreamJourney($booking, ['source' => 'admin_booking_update']);

                $freshBooking = $booking->fresh([
                    'client',
                    'opportunity.lead',
                    'vehicleData.make',
                    'vehicleData.model',
                    'assignedUser',
                ]);

                if (! $freshBooking) {
                    throw new \RuntimeException('Booking could not be refreshed after update.');
                }

                Log::info('[BookingController] Booking updated', [
                    'booking_id' => $freshBooking->id,
                    'old_status' => $oldStatus,
                    'new_status' => $freshBooking->status,
                    'client_id' => $freshBooking->client_id,
                    'vehicle_id' => $freshBooking->vehicle_id,
                    'opportunity_id' => $freshBooking->opportunity_id,
                ]);

                if (
                    $freshBooking->status === self::STATUS_SCHEDULED
                    && ! $this->bookingConfirmationAlreadySent($freshBooking)
                ) {
                    $this->handleScheduledBooking($freshBooking);
                }

                if ($freshBooking->status === self::STATUS_CONVERTED_TO_JOB) {
                    $job = $this->createJobFromBooking($freshBooking);
                    $this->markOpportunityConvertedToJob($freshBooking);

                    Log::info('[BookingController] Booking converted to job', [
                        'booking_id' => $freshBooking->id,
                        'job_id' => $job->id,
                        'client_id' => $freshBooking->client_id,
                    ]);
                }

                if ($freshBooking->status === self::STATUS_LOST) {
                    $this->markOpportunityLostFromBooking($freshBooking);
                }
            });
        } catch (\Throwable $e) {
            Log::error('[BookingController] Update failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['slot' => $e->getMessage() ?: 'Unable to update booking.'])
                ->withInput();
        }

        return redirect()
            ->route('admin.bookings.index')
            ->with('success', 'Booking updated.');
    }

    public function archive(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $booking->update(['is_archived' => true]);

        return back()->with('success', 'Booking archived.');
    }

    public function restore(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $booking->update(['is_archived' => false]);

        return back()->with('success', 'Booking restored.');
    }

    public function destroy(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $booking->update(['is_archived' => true]);

        return back()->with('success', 'Booking archived.');
    }

    protected function validatedData(Request $request, int $companyId, bool $isCreate): array
    {
        $rules = [
            'opportunity_id' => [
                'nullable',
                Rule::exists('opportunities', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'new_vehicle_make_id' => [
                Rule::requiredIf(fn () => $isCreate && $request->input('client_id') === 'new_client' && ! $request->filled('opportunity_id')),
                'nullable',
                Rule::exists('vehicle_makes', 'id'),
            ],

            'new_vehicle_model_id' => [
                Rule::requiredIf(fn () => $isCreate && $request->input('client_id') === 'new_client' && ! $request->filled('opportunity_id')),
                'nullable',
                Rule::exists('vehicle_models', 'id'),
            ],

            'new_vehicle_plate_number' => ['nullable', 'string', 'max:50'],
            'new_vehicle_year' => ['nullable', 'string', 'max:10'],
            'new_vehicle_vin' => ['nullable', 'string', 'max:100'],
            'new_vehicle_color' => ['nullable', 'string', 'max:50'],

            'name' => [$isCreate ? 'required' : 'nullable', 'string', 'max:255'],
            'service_type' => ['nullable', 'string', 'max:255'],

            'booking_date' => ['required', 'date'],
            'slot' => ['required', Rule::in(['morning', 'afternoon', 'evening', 'full_day'])],

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'pickup_required' => ['nullable', 'boolean'],
            'pickup_address' => ['nullable', 'string', 'max:255'],
            'pickup_contact_number' => ['nullable', 'string', 'max:20'],

            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'expected_duration' => ['nullable', 'integer', 'min:1'],
            'expected_close_date' => ['nullable', 'date'],

            'notes' => ['nullable', 'string'],

            'status' => [
                'nullable',
                Rule::in(self::BOOKING_STATUSES),
            ],

            'lost_reason' => [
                Rule::requiredIf(fn () => $request->input('status') === self::STATUS_LOST),
                'nullable',
                Rule::in(self::LOST_REASONS),
            ],

            'allow_overbooking' => ['nullable', 'boolean'],

            'overbooking_reason' => [
                Rule::requiredIf(fn () => $request->boolean('allow_overbooking')),
                'nullable',
                'string',
                'max:500',
            ],
        ];

        if ($isCreate) {
            $rules['client_id'] = [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail) use ($companyId) {
                    if ($value === null || $value === '' || $value === 'new_client') {
                        return;
                    }

                    $exists = Client::query()
                        ->where('company_id', $companyId)
                        ->where('id', $value)
                        ->exists();

                    if (! $exists) {
                        $fail('Selected client does not belong to this company.');
                    }
                },
            ];

            $rules['new_client_name'] = [
                Rule::requiredIf(fn () => $request->input('client_id') === 'new_client' && ! $request->filled('opportunity_id')),
                'nullable',
                'string',
                'max:255',
            ];

            $rules['new_client_phone'] = [
                'nullable',
                'string',
                'max:50',
            ];

            $rules['new_client_whatsapp'] = [
                'nullable',
                'string',
                'max:50',
            ];

            $rules['new_client_email'] = [
                'nullable',
                'email',
                'max:255',
            ];
        }

        return $request->validate($rules, [
            'new_client_name.required' => 'Client name is required when creating a new client.',
            'new_vehicle_make_id.required' => 'Vehicle make is required when creating a booking for a new client.',
            'new_vehicle_model_id.required' => 'Vehicle model is required when creating a booking for a new client.',
        ]);
    }

    protected function bookingPayload(array $data): array
    {
        return collect($data)
            ->except([
                'new_client_name',
                'new_client_phone',
                'new_client_whatsapp',
                'new_client_email',
                'new_vehicle_make_id',
                'new_vehicle_model_id',
                'new_vehicle_plate_number',
                'new_vehicle_year',
                'new_vehicle_vin',
                'new_vehicle_color',
                'allow_overbooking',
                'overbooking_reason',
            ])
            ->toArray();
    }

    protected function resolveClientId(array $data, int $companyId): int
    {
        if (! empty($data['opportunity_id'])) {
            $opportunity = Opportunity::where('company_id', $companyId)
                ->find($data['opportunity_id']);

            abort_if(! $opportunity?->client_id, 422, 'Selected opportunity does not have a client linked.');

            return (int) $opportunity->client_id;
        }

        if (! empty($data['client_id']) && $data['client_id'] !== 'new_client') {
            $client = Client::query()
                ->where('company_id', $companyId)
                ->where('id', $data['client_id'])
                ->first();

            abort_if(! $client, 422, 'Selected client does not belong to this company.');

            return (int) $client->id;
        }

        $phone = trim((string) ($data['new_client_phone'] ?? ''));
        $whatsapp = trim((string) ($data['new_client_whatsapp'] ?? ''));
        $email = trim((string) ($data['new_client_email'] ?? ''));
        $contactNumber = $phone !== '' ? $phone : $whatsapp;
        $normalizedContact = Client::normalizePhone($contactNumber);

        if (empty($data['new_client_name']) || $normalizedContact === null) {
            $messages = [
                'client_id' => 'Please select or create a client before creating the booking.',
            ];

            if (empty($data['new_client_name'])) {
                $messages['new_client_name'] = 'Client name is required when creating a new client.';
            }

            if ($normalizedContact === null) {
                $messages['new_client_phone'] = 'Please enter a phone or WhatsApp number for the new client.';
            }

            throw ValidationException::withMessages($messages);
        }

        if ($normalizedContact !== null || $email !== '') {
            $existingClient = Client::where('company_id', $companyId)
                ->where(function ($query) use ($phone, $whatsapp, $email, $normalizedContact) {
                    if ($normalizedContact !== null) {
                        $query->where('phone_norm', $normalizedContact)
                            ->orWhere('phone', $phone)
                            ->orWhere('phone', $whatsapp)
                            ->orWhere('whatsapp', $phone)
                            ->orWhere('whatsapp', $whatsapp);
                    }

                    if ($email !== '') {
                        $query->orWhere('email', $email);
                    }
                })
                ->first();

            if ($existingClient) {
                return (int) $existingClient->id;
            }
        }

        $client = Client::create([
            'company_id' => $companyId,
            'name' => $data['new_client_name'],
            'phone' => $contactNumber ?: null,
            'whatsapp' => ($whatsapp !== '' ? $whatsapp : $contactNumber) ?: null,
            'email' => $email ?: null,
            'source' => 'walk-in booking',
            'status' => 'active',
        ]);

        return (int) $client->id;
    }

    protected function resolveVehicleId(array $data, int $companyId): ?int
    {
        if (! empty($data['vehicle_id'])) {
            return (int) $data['vehicle_id'];
        }

        if (empty($data['new_vehicle_make_id']) && empty($data['new_vehicle_model_id'])) {
            return null;
        }

        abort_if(empty($data['client_id']), 422, 'Please select or create a client before adding a vehicle.');

        if (! empty($data['new_vehicle_model_id']) && ! empty($data['new_vehicle_make_id'])) {
            $model = VehicleModel::where('id', $data['new_vehicle_model_id'])
                ->where('make_id', $data['new_vehicle_make_id'])
                ->first();

            abort_if(! $model, 422, 'Selected vehicle model does not belong to the selected make.');
        }

        $existingVehicle = Vehicle::query()
            ->where('company_id', $companyId)
            ->where('client_id', $data['client_id'])
            ->where('make_id', $data['new_vehicle_make_id'])
            ->where('model_id', $data['new_vehicle_model_id'])
            ->when(! empty($data['new_vehicle_plate_number']), function ($query) use ($data) {
                $query->where('plate_number', $data['new_vehicle_plate_number']);
            })
            ->first();

        if ($existingVehicle) {
            return (int) $existingVehicle->id;
        }

        $vehicle = Vehicle::create([
            'company_id' => $companyId,
            'client_id' => $data['client_id'],
            'make_id' => $data['new_vehicle_make_id'] ?? null,
            'model_id' => $data['new_vehicle_model_id'] ?? null,
            'plate_number' => $data['new_vehicle_plate_number'] ?? null,
            'year' => $data['new_vehicle_year'] ?? null,
            'vin' => $data['new_vehicle_vin'] ?? null,
            'color' => $data['new_vehicle_color'] ?? null,
        ]);

        return (int) $vehicle->id;
    }

    protected function preventDuplicateOpportunityBooking(array $data, int $companyId): void
    {
        if (empty($data['opportunity_id'])) {
            return;
        }

        $existingBooking = Booking::where('company_id', $companyId)
            ->where('opportunity_id', $data['opportunity_id'])
            ->where('is_archived', false)
            ->first();

        abort_if(
            $existingBooking,
            422,
            'This opportunity already has a booking. Please edit the existing booking instead.'
        );
    }

    protected function validateLinkedRecords(array $data, int $companyId): void
    {
        if (! empty($data['opportunity_id'])) {
            $opportunity = Opportunity::where('company_id', $companyId)
                ->find($data['opportunity_id']);

            abort_if(! $opportunity, 422, 'Selected opportunity does not belong to this company.');

            abort_if(
                (int) $opportunity->client_id !== (int) $data['client_id'],
                422,
                'Selected opportunity does not belong to the selected client.'
            );
        }

        if (! empty($data['vehicle_id'])) {
            $vehicle = Vehicle::where('company_id', $companyId)
                ->where('client_id', $data['client_id'])
                ->find($data['vehicle_id']);

            abort_if(! $vehicle, 422, 'Selected vehicle does not belong to the selected client.');
        }
    }

    protected function validateStatusRequirements(array $data): void
    {
        $status = $data['status'] ?? self::STATUS_SCHEDULED;

        if ($status === self::STATUS_LOST && empty($data['lost_reason'])) {
            abort(422, 'Please select a lost booking reason.');
        }

        if ($status === self::STATUS_CONVERTED_TO_JOB && empty($data['vehicle_id'])) {
            abort(422, 'Please link or create a vehicle before converting this booking to a job.');
        }
    }

    protected function ensureSlotAvailable(
        int $companyId,
        string $bookingDate,
        string $slot,
        ?int $excludeBookingId = null,
        bool $allowOverbooking = false,
        ?string $overbookingReason = null
    ): void {
        $baseQuery = Booking::where('company_id', $companyId)
            ->whereDate('booking_date', $bookingDate)
            ->where('is_archived', false)
            ->whereIn('status', self::ACTIVE_STATUSES);

        if ($excludeBookingId) {
            $baseQuery->where('id', '!=', $excludeBookingId);
        }

        $fullDayExists = (clone $baseQuery)
            ->where('slot', 'full_day')
            ->exists();

        if ($slot !== 'full_day' && $fullDayExists) {
            $this->handleSlotCapacityFailure(
                allowOverbooking: $allowOverbooking,
                overbookingReason: $overbookingReason,
                message: 'A full-day booking already exists for this date.'
            );

            return;
        }

        if ($slot === 'full_day') {
            $dayBookingCount = (clone $baseQuery)->count();

            if ($dayBookingCount > 0) {
                $this->handleSlotCapacityFailure(
                    allowOverbooking: $allowOverbooking,
                    overbookingReason: $overbookingReason,
                    message: 'This date already has bookings. Full-day booking would overbook the day.'
                );
            }

            return;
        }

        $capacity = $this->slotCapacity($companyId, $slot);

        $slotBookingCount = (clone $baseQuery)
            ->where('slot', $slot)
            ->count();

        if ($slotBookingCount >= $capacity) {
            $this->handleSlotCapacityFailure(
                allowOverbooking: $allowOverbooking,
                overbookingReason: $overbookingReason,
                message: "The {$slot} slot is already full for this date. Capacity: {$capacity}."
            );
        }
    }

    protected function handleSlotCapacityFailure(
        bool $allowOverbooking,
        ?string $overbookingReason,
        string $message
    ): void {
        if (! $allowOverbooking) {
            abort(422, $message);
        }

        abort_if(
            ! $this->canOverrideSlotCapacity(),
            403,
            'Only admin or manager can override slot capacity.'
        );

        abort_if(
            trim((string) $overbookingReason) === '',
            422,
            'Please provide overbooking reason.'
        );
    }

    protected function slotCapacity(int $companyId, string $slot): int
    {
        $defaults = [
            'morning' => 3,
            'afternoon' => 3,
            'evening' => 3,
            'full_day' => 1,
        ];

        $default = $defaults[$slot] ?? 1;

        if (! Schema::hasTable('company_settings')) {
            return $default;
        }

        $keys = [
            "booking_slot_capacity_{$slot}",
            "slot_capacity_{$slot}",
        ];

        foreach ($keys as $key) {
            $value = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->where('key', $key)
                ->value('value');

            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return $default;
    }

    protected function slotUsage(int $companyId, ?int $excludeBookingId = null): array
    {
        $query = Booking::where('company_id', $companyId)
            ->where('is_archived', false)
            ->whereIn('status', self::ACTIVE_STATUSES);

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query
            ->get(['booking_date', 'slot'])
            ->groupBy(fn ($booking) => optional($booking->booking_date)->format('Y-m-d'))
            ->map(function ($items) {
                return $items
                    ->groupBy(fn ($booking) => strtolower((string) $booking->slot))
                    ->map(fn ($slotItems) => $slotItems->count())
                    ->toArray();
            })
            ->toArray();
    }

    protected function slotCapacities(int $companyId): array
    {
        return [
            'morning' => $this->slotCapacity($companyId, 'morning'),
            'afternoon' => $this->slotCapacity($companyId, 'afternoon'),
            'evening' => $this->slotCapacity($companyId, 'evening'),
            'full_day' => $this->slotCapacity($companyId, 'full_day'),
        ];
    }

    protected function canOverrideSlotCapacity(): bool
    {
        $role = strtolower((string) (auth()->user()?->role ?? ''));

        return in_array($role, ['admin', 'manager', 'superadmin', 'super_admin'], true);
    }

    protected function bookingCounts(int $companyId): array
    {
        $today = now()->toDateString();

        $scheduledCount = Booking::where('company_id', $companyId)
            ->where('is_archived', false)
            ->where('status', self::STATUS_SCHEDULED)
            ->count();

        $convertedCount = Booking::where('company_id', $companyId)
            ->where('is_archived', false)
            ->where('status', self::STATUS_CONVERTED_TO_JOB)
            ->count();

        return [
            'today' => Booking::where('company_id', $companyId)
                ->where('is_archived', false)
                ->whereIn('status', self::ACTIVE_STATUSES)
                ->whereDate('booking_date', $today)
                ->count(),

            'upcoming' => Booking::where('company_id', $companyId)
                ->where('is_archived', false)
                ->whereIn('status', self::ACTIVE_STATUSES)
                ->whereDate('booking_date', '>=', $today)
                ->count(),

            'pending' => Booking::where('company_id', $companyId)
                ->where('is_archived', false)
                ->where('status', self::STATUS_PENDING)
                ->count(),

            'scheduled' => $scheduledCount,

            'converted_to_job' => $convertedCount,

            'lost' => Booking::where('company_id', $companyId)
                ->where('is_archived', false)
                ->where('status', self::STATUS_LOST)
                ->count(),

            'confirmed' => $scheduledCount,
            'completed' => $convertedCount,
        ];
    }

    protected function bookingBucketCounts(int $companyId): array
    {
        $base = Booking::where('company_id', $companyId)
            ->where('is_archived', false)
            ->whereIn('status', self::ACTIVE_STATUSES);

        return [
            'morning' => (clone $base)->where('slot', 'morning')->count(),
            'afternoon' => (clone $base)->where('slot', 'afternoon')->count(),
            'evening' => (clone $base)->where('slot', 'evening')->count(),
            'pending' => (clone $base)->where('status', self::STATUS_PENDING)->count(),

            'overdue' => (clone $base)
                ->where('status', self::STATUS_PENDING)
                ->whereDate('booking_date', '<', now()->toDateString())
                ->count(),

            'no_vehicle' => (clone $base)
                ->whereNull('vehicle_id')
                ->count(),

            'high_priority' => (clone $base)
                ->whereIn('priority', ['high', 'urgent'])
                ->count(),
        ];
    }

    protected function applyBookingBucketFilter($query, string $bucket): void
    {
        match ($bucket) {
            'today' => $query->whereDate('booking_date', now()->toDateString()),
            'upcoming' => $query->whereDate('booking_date', '>=', now()->toDateString()),
            'morning' => $query->where('slot', 'morning'),
            'afternoon' => $query->where('slot', 'afternoon'),
            'evening' => $query->where('slot', 'evening'),
            'pending' => $query->where('status', self::STATUS_PENDING),

            'overdue' => $query
                ->where('status', self::STATUS_PENDING)
                ->whereDate('booking_date', '<', now()->toDateString()),

            'no_vehicle' => $query->whereNull('vehicle_id'),
            'high_priority' => $query->whereIn('priority', ['high', 'urgent']),

            default => null,
        };
    }

    protected function handleScheduledBooking(Booking $booking): void
    {
        try {
            $booking->loadMissing([
                'client',
                'opportunity.lead',
                'vehicleData.make',
                'vehicleData.model',
            ]);

            $this->markOpportunityBookingConfirmed($booking);

            $lead = $booking->opportunity?->lead;

            $customerName =
                $booking->client?->name
                ?: $lead?->name
                ?: $booking->name
                ?: 'Customer';

            $toNumber =
                $booking->client?->whatsapp
                ?: $booking->client?->phone
                ?: $lead?->phone_norm
                ?: $lead?->phone
                ?: '';

            $toNumber = trim((string) $toNumber);

            if ($toNumber === '') {
                Log::warning('[BookingController] booking.confirmed WhatsApp skipped: phone missing', [
                    'booking_id' => $booking->id,
                    'company_id' => $booking->company_id,
                    'client_id' => $booking->client_id,
                    'lead_id' => $lead?->id,
                ]);

                return;
            }

            $dateLabel = $booking->booking_date
                ? $booking->booking_date->format('d M Y')
                : 'your selected date';

            $slotLabel = $booking->slot_label
                ?? ucwords(str_replace('_', ' ', (string) $booking->slot));

            $dateTimeLabel = trim($dateLabel . ' ' . $slotLabel);

            $vehicleLabel = $this->bookingVehicleLabel($booking);

            $garageName =
                $this->companySetting((int) $booking->company_id, 'garage_name')
                ?: $this->companySetting((int) $booking->company_id, 'business_name')
                ?: config('app.name', 'Garage');

            $location =
                $this->companySetting((int) $booking->company_id, 'garage_location_link')
                ?: $this->companySetting((int) $booking->company_id, 'whatsapp.garage_location_link')
                ?: $garageName;

            DB::afterCommit(function () use (
                $booking,
                $lead,
                $customerName,
                $toNumber,
                $vehicleLabel,
                $dateLabel,
                $slotLabel,
                $dateTimeLabel,
                $garageName,
                $location
            ) {
                try {
                    $freshBooking = $booking->fresh([
                        'client',
                        'opportunity.lead',
                        'vehicleData.make',
                        'vehicleData.model',
                    ]);

                    if (! $freshBooking) {
                        return;
                    }

                    if ($this->bookingConfirmationAlreadySent($freshBooking)) {
                        Log::info('[BookingController] booking.confirmed WhatsApp skipped: already sent', [
                            'booking_id' => $freshBooking->id,
                            'company_id' => $freshBooking->company_id,
                        ]);

                        return;
                    }

                    app(SendWhatsAppMessage::class)->fireEvent(
                        (int) $freshBooking->company_id,
                        'booking.confirmed',
                        (string) $toNumber,
                        [
                            'name' => $customerName,
                            'customer_name' => $customerName,
                            'vehicle' => $vehicleLabel,
                            'vehicle_label' => $vehicleLabel,
                            'date' => $dateLabel,
                            'slot' => $slotLabel,
                            'date_time' => $dateTimeLabel,
                            'booking_time' => $dateTimeLabel,
                            'garage' => $garageName,
                            'garage_name' => $garageName,
                            'location' => $location,

                            'company_id' => (int) $freshBooking->company_id,
                            'booking_id' => (int) $freshBooking->id,
                            'lead_id' => $lead?->id,
                            'client_id' => $freshBooking->client_id,
                            'phone' => $toNumber,
                            'event_key' => 'booking.confirmed',
                            'source' => 'manager_scheduled_booking',
                            'action' => 'booking_confirmed_by_manager',
                            'send_mode' => 'meta_template',
                        ]
                    );

                    Log::info('[BookingController] booking.confirmed WhatsApp event fired', [
                        'booking_id' => $freshBooking->id,
                        'company_id' => $freshBooking->company_id,
                        'to' => $toNumber,
                        'event_key' => 'booking.confirmed',
                    ]);
                } catch (\Throwable $e) {
                    Log::error('[BookingController] booking.confirmed WhatsApp failed', [
                        'booking_id' => $booking->id,
                        'company_id' => $booking->company_id,
                        'to' => $toNumber,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            Log::error('[BookingController] Scheduled booking handling failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function markOpportunityBookingConfirmed(Booking $booking): void
    {
        if (! $booking->opportunity) {
            return;
        }

        abort_if(
            (int) $booking->opportunity->company_id !== (int) $booking->company_id,
            403
        );

        $booking->opportunity->update([
            'stage' => 'appointment',
            'next_follow_up' => $booking->booking_date,
            'expected_close_date' => $booking->expected_close_date ?: $booking->booking_date,
            'is_converted' => false,
        ]);
    }

    protected function bookingConfirmationAlreadySent(Booking $booking): bool
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            return false;
        }

        return DB::table('whatsapp_messages')
            ->where('company_id', $booking->company_id)
            ->whereIn('status', ['queued', 'sent', 'delivered', 'read'])
            ->where(function ($query) {
                $query
                    ->where('payload', 'like', '%"event_key":"booking.confirmed"%')
                    ->orWhere('payload', 'like', '%booking.confirmed%');
            })
            ->where(function ($query) use ($booking) {
                $query
                    ->where('payload', 'like', '%"booking_id":' . $booking->id . '%')
                    ->orWhere('payload', 'like', '%"booking_id":"' . $booking->id . '"%');
            })
            ->exists();
    }

    protected function markOpportunityConvertedToJob(Booking $booking): void
    {
        if (! $booking->opportunity) {
            return;
        }

        abort_if(
            (int) $booking->opportunity->company_id !== (int) $booking->company_id,
            403
        );

        $booking->opportunity->update([
            'stage' => 'closed_won',
            'is_converted' => true,
            'close_reason' => null,
            'expected_close_date' => $booking->expected_close_date ?: $booking->booking_date,
        ]);
    }

    protected function markOpportunityLostFromBooking(Booking $booking): void
    {
        if (! $booking->opportunity) {
            return;
        }

        abort_if(
            (int) $booking->opportunity->company_id !== (int) $booking->company_id,
            403
        );

        $booking->opportunity->update([
            'stage' => Opportunity::STAGE_CLOSED_LOST,
            'is_converted' => false,
            'close_reason' => $booking->lost_reason ?? 'Booking lost',
        ]);
    }

    protected function createJobFromBooking(Booking $booking): Job
    {
        $booking->loadMissing(['client', 'opportunity', 'vehicleData.make', 'vehicleData.model']);

        $lookup = [
            'company_id' => $booking->company_id,
            'booking_id' => $booking->id,
        ];

        $payload = [
            'client_id' => $booking->client_id,
            'description' => $booking->service_type ?: $booking->name ?: 'Service job',
            'status' => 'pending',
            'assigned_to' => $booking->assigned_to,
            'start_time' => now(),
        ];

        if (Schema::hasColumn('jobs', 'opportunity_id')) {
            $payload['opportunity_id'] = $booking->opportunity_id;
        }

        if (Schema::hasColumn('jobs', 'lead_id')) {
            $payload['lead_id'] = $booking->lead_id ?: $booking->opportunity?->lead_id;
        }

        /*
        |--------------------------------------------------------------------------
        | Column-safe job payload
        |--------------------------------------------------------------------------
        | Azure DB currently has no jobs.vehicle_id column.
        | Keep this safe for older/newer DBs.
        */

        if (Schema::hasColumn('jobs', 'vehicle_id')) {
            $payload['vehicle_id'] = $booking->vehicle_id;
        }

        if (Schema::hasColumn('jobs', 'job_code')) {
            $payload['job_code'] = $this->nextJobCode($booking);
        }

        Log::info('[BookingController] Creating job from booking', [
            'booking_id' => $booking->id,
            'company_id' => $booking->company_id,
            'client_id' => $booking->client_id,
            'payload' => $payload,
        ]);

        return Job::firstOrCreate($lookup, $payload);
    }

    protected function nextJobCode(Booking $booking): string
    {
        $prefix = 'JOB-' . now()->format('Ymd') . '-';

        $latestId = (int) Job::where('company_id', $booking->company_id)->max('id');

        return $prefix . str_pad((string) ($latestId + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function bookingVehicleLabel(Booking $booking): string
    {
        $make = $booking->vehicleData?->make?->name;
        $model = $booking->vehicleData?->model?->name;
        $plate = $booking->vehicleData?->plate_number;

        $vehicle = trim(implode(' ', array_filter([
            $make,
            $model,
        ])));

        if ($vehicle !== '' && $plate) {
            return "{$vehicle} ({$plate})";
        }

        if ($vehicle !== '') {
            return $vehicle;
        }

        if ($plate) {
            return "Vehicle {$plate}";
        }

        return 'your vehicle';
    }

    protected function companySetting(int $companyId, string $key): ?string
    {
        if (! $companyId || ! Schema::hasTable('company_settings')) {
            return null;
        }

        $value = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('group', 'whatsapp')
            ->where('key', $key)
            ->value('value');

        if ($value === null) {
            $value = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->where('key', $key)
                ->value('value');
        }

        return is_string($value) ? trim($value) : $value;
    }

    protected function assignableUsers(int $companyId)
    {
        return User::where('company_id', $companyId)
            ->whereIn('role', ['admin', 'manager'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    protected function authorizeBooking(Booking $booking): void
    {
        abort_if((int) $booking->company_id !== (int) $this->companyId(), 403);
    }
}
