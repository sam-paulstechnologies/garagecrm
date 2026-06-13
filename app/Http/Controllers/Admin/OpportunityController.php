<?php

namespace App\Http\Controllers\Admin;

use App\Events\BookingStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class OpportunityController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    /** 📄 Index */
    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));
        $stage = trim((string) $request->get('stage', ''));
        $priority = trim((string) $request->get('priority', ''));
        $bucket = trim((string) $request->get('bucket', ''));

        $opportunities = $this->baseOpportunityQuery($companyId, $q)
            ->where('is_archived', false)
            ->when($stage === '', function ($query) {
                $query->whereNotIn('stage', [
                    Opportunity::STAGE_CLOSED_WON,
                    Opportunity::STAGE_CLOSED_LOST,
                ]);
            })
            ->when($stage !== '', function ($query) use ($stage) {
                $query->where('stage', $stage);
            })
            ->when($priority !== '', function ($query) use ($priority) {
                $query->where('priority', $priority);
            })
            ->when($bucket !== '', function ($query) use ($bucket) {
                $this->applyBucketFilter($query, $bucket);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.opportunities.index', [
            'opportunities' => $opportunities,
            'q' => $q,
            'stage' => $stage,
            'priority' => $priority,
            'bucket' => $bucket,
            'stages' => Opportunity::STAGES,
            'opportunityCounts' => $this->opportunityCounts($companyId),
            'bucketCounts' => $this->bucketCounts($companyId),
        ]);
    }

    /** 📦 Emergency archived list only */
    public function archived()
    {
        $companyId = $this->companyId();

        $opportunities = Opportunity::query()
            ->where('company_id', $companyId)
            ->where('is_archived', true)
            ->with([
                'client:id,name,phone,email',
                'lead:id,name,phone,email',
                'assignee:id,name,role',
                'vehicleMake:id,name',
                'vehicleModel:id,name',
            ])
            ->latest()
            ->paginate(20);

        return view('admin.opportunities.archived', compact('opportunities'));
    }

    /** ➕ Create */
    public function create()
    {
        $companyId = $this->companyId();

        return view('admin.opportunities.create', [
            'clients' => Client::where('company_id', $companyId)
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),

            'leads' => Lead::where('company_id', $companyId)
                ->orderByDesc('id')
                ->get(['id', 'name', 'phone', 'client_id']),

            'users' => $this->assignableUsers($companyId),

            'vehicles' => Vehicle::where('company_id', $companyId)
                ->with(['make:id,name', 'model:id,name'])
                ->orderByDesc('id')
                ->get(),

            'makes' => VehicleMake::orderBy('name')->get(['id', 'name']),

            'models' => VehicleModel::orderBy('name')->get(['id', 'name', 'make_id']),
        ]);
    }

    /** 💾 Store */
    public function store(Request $request)
    {
        $companyId = $this->companyId();

        $data = $this->validatedData($request, $companyId, true);
        $data['service_type'] = $this->resolveOpportunityServiceType($data);

        $this->validateLinkedRecords($data, $companyId);
        $this->validateStageRequirements($data);

        $data['company_id'] = $companyId;
        $data['is_archived'] = false;
        $data['priority'] = $data['priority'] ?? 'medium';

        if ($data['stage'] !== Opportunity::STAGE_CLOSED_LOST) {
            $data['close_reason'] = null;
        }

        DB::transaction(function () use (&$data, $companyId) {
            $data['vehicle_id'] = $this->resolveVehicleForOpportunity($data, $companyId);

            if ($data['stage'] === Opportunity::STAGE_CLOSED_WON) {
                $data['is_converted'] = true;
            }

            if ($data['stage'] === Opportunity::STAGE_CLOSED_LOST) {
                $data['is_converted'] = false;
            }

            $opportunity = Opportunity::create($this->opportunityPayload($data));

            if ($data['stage'] === Opportunity::STAGE_CLOSED_WON) {
                $booking = $this->createOrUpdateBookingFromOpportunity($opportunity, $data);

                DB::afterCommit(function () use ($booking) {
                    $freshBooking = $booking->fresh();

                    if ($freshBooking) {
                        event(new BookingStatusUpdated($freshBooking, (string) $freshBooking->status));
                    }
                });
            }
        });

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', 'Opportunity created.');
    }

    /** 👁️ Show */
    public function show(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $opportunity->load([
            'client',
            'lead',
            'assignee',
            'vehicleMake',
            'vehicleModel',
        ]);

        return view('admin.opportunities.show', compact('opportunity'));
    }

    /** ✏️ Edit */
    public function edit(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $companyId = $this->companyId();

        $opportunity->load([
            'client',
            'lead',
            'assignee',
            'vehicleMake',
            'vehicleModel',
        ]);

        return view('admin.opportunities.edit', [
            'opportunity' => $opportunity,

            'clients' => Client::where('company_id', $companyId)
                ->orderBy('name')
                ->get(['id', 'name', 'phone']),

            'leads' => Lead::where('company_id', $companyId)
                ->orderByDesc('id')
                ->get(['id', 'name', 'phone', 'client_id']),

            'users' => $this->assignableUsers($companyId),

            'vehicles' => Vehicle::where('company_id', $companyId)
                ->where('client_id', $opportunity->client_id)
                ->with(['make:id,name', 'model:id,name'])
                ->orderByDesc('id')
                ->get(),

            'makes' => VehicleMake::orderBy('name')->get(['id', 'name']),

            'models' => VehicleModel::orderBy('name')->get(['id', 'name', 'make_id']),
        ]);
    }

    /** 🔁 Update */
    public function update(Request $request, Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $companyId = $this->companyId();

        $data = $this->validatedData($request, $companyId, false);
        $data['client_id'] = $opportunity->client_id;
        $data['service_type'] = $this->resolveOpportunityServiceType($data, $opportunity);

        $this->validateLinkedRecords($data, $companyId);
        $this->validateStageRequirements($data);

        $data['priority'] = $data['priority'] ?? 'medium';

        if ($data['stage'] !== Opportunity::STAGE_CLOSED_LOST) {
            $data['close_reason'] = null;
        }

        DB::transaction(function () use ($opportunity, &$data, $companyId) {
            $currentStage = (string) $opportunity->stage;
            $newStage = (string) $data['stage'];

            $this->validateStageTransition($currentStage, $newStage);

            $data['vehicle_id'] = $this->resolveVehicleForOpportunity($data, $companyId);

            if ($newStage === Opportunity::STAGE_CLOSED_WON) {
                $data['is_converted'] = true;
            }

            if ($newStage === Opportunity::STAGE_CLOSED_LOST) {
                $data['is_converted'] = false;
            }

            $opportunity->update($this->opportunityPayload($data));

            if ($newStage === Opportunity::STAGE_CLOSED_WON) {
                $booking = $this->createOrUpdateBookingFromOpportunity($opportunity->fresh(), $data);

                DB::afterCommit(function () use ($booking) {
                    $freshBooking = $booking->fresh();

                    if ($freshBooking) {
                        event(new BookingStatusUpdated($freshBooking, (string) $freshBooking->status));
                    }
                });
            }
        });

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', 'Opportunity updated.');
    }

    /** 🗑️ Emergency archive only */
    public function destroy(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $opportunity->update(['is_archived' => true]);

        return back()->with('success', 'Opportunity archived.');
    }

    /** ♻️ Emergency restore only */
    public function restore(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $opportunity->update(['is_archived' => false]);

        return redirect()
            ->route('admin.opportunities.archived')
            ->with('success', 'Opportunity restored.');
    }

    protected function validatedData(Request $request, int $companyId, bool $isCreate): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],

            'stage' => [
                'required',
                Rule::in(Opportunity::STAGES),
            ],

            'service_type' => ['nullable'],
            'service_type.*' => ['nullable', 'string', 'max:100'],
            'services' => ['nullable', 'array'],
            'services.*' => ['nullable', 'string', 'max:100'],
            'custom_service_type' => ['nullable', 'string', 'max:100'],
            'other_service_text' => ['nullable', 'string', 'max:100'],
            'value' => ['nullable', 'numeric'],
            'expected_close_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'notes' => ['nullable', 'string'],

            'close_reason' => [
                Rule::requiredIf(fn () => $request->input('stage') === Opportunity::STAGE_CLOSED_LOST),
                'nullable',
                'string',
                'max:255',
            ],

            'booking_date' => [
                'nullable',
                'date',
            ],

            'booking_slot' => [
                'nullable',
                Rule::in(['morning', 'afternoon', 'evening']),
            ],

            'booking_notes' => ['nullable', 'string', 'max:2000'],

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                        ->whereIn('role', ['admin', 'manager']);
                }),
            ],

            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'manual_make_id' => ['nullable', 'exists:vehicle_makes,id'],
            'manual_model_id' => ['nullable', 'exists:vehicle_models,id'],
            'manual_year' => ['nullable', 'string', 'max:10'],
            'manual_color' => ['nullable', 'string', 'max:50'],
            'manual_plate_number' => ['nullable', 'string', 'max:100'],
            'manual_vin' => ['nullable', 'string', 'max:17'],
            'manual_current_mileage' => ['nullable', 'integer', 'min:0'],
            'manual_registration_expiry_date' => ['nullable', 'date'],
            'manual_insurance_expiry_date' => ['nullable', 'date'],
        ];

        if ($isCreate) {
            $rules['client_id'] = [
                'required',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ];

            $rules['lead_id'] = [
                'nullable',
                Rule::exists('leads', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ];
        }

        return $request->validate($rules);
    }

    protected function resolveOpportunityServiceType(array $data, ?Opportunity $opportunity = null): ?string
    {
        $services = collect();

        if (! empty($data['service_type'])) {
            $serviceType = $data['service_type'];

            if (is_array($serviceType)) {
                $services = $services->merge($serviceType);
            } else {
                $services = $services->merge(explode(',', (string) $serviceType));
            }
        }

        foreach (['services', 'service_options', 'selected_services'] as $field) {
            if (! empty($data[$field])) {
                $services = $services->merge((array) $data[$field]);
            }
        }

        $customService = trim((string) ($data['custom_service_type'] ?? $data['other_service_text'] ?? ''));

        $cleanServices = $services
            ->map(fn ($service) => trim((string) $service))
            ->filter()
            ->values();

        $hasOther = $cleanServices
            ->contains(fn ($service) => strcasecmp((string) $service, 'Other') === 0 || str_starts_with(strtolower((string) $service), 'other:'));

        if ($hasOther && $customService !== '') {
            $cleanServices = $cleanServices
                ->reject(fn ($service) => strcasecmp((string) $service, $customService) === 0)
                ->values();
        }

        $normalized = $cleanServices
            ->map(function (string $service) use ($customService) {
                if (str_starts_with(strtolower($service), 'other:')) {
                    return $service;
                }

                if (strcasecmp($service, 'Other') === 0 && $customService !== '') {
                    return 'Other: ' . $customService;
                }

                return $service;
            })
            ->unique(fn ($service) => strtolower($service))
            ->values();

        if ($normalized->isEmpty() && $customService !== '') {
            $normalized->push($customService);
        }

        if ($normalized->isEmpty() && $opportunity?->service_type) {
            $normalized = collect(explode(',', (string) $opportunity->service_type))
                ->map(fn ($service) => trim((string) $service))
                ->filter()
                ->values();
        }

        $value = trim($normalized->implode(', '));

        return $value !== '' ? $value : null;
    }

    protected function opportunityPayload(array $data): array
    {
        return collect($data)
            ->except([
                'manual_make_id',
                'manual_model_id',
                'manual_year',
                'manual_color',
                'manual_plate_number',
                'manual_vin',
                'manual_current_mileage',
                'manual_registration_expiry_date',
                'manual_insurance_expiry_date',
                'services',
                'custom_service_type',
                'other_service_text',

                'booking_date',
                'booking_slot',
                'booking_notes',
            ])
            ->toArray();
    }

    protected function resolveVehicleForOpportunity(array $data, int $companyId): ?int
    {
        if (! empty($data['vehicle_id'])) {
            return (int) $data['vehicle_id'];
        }

        if (! $this->hasManualVehicleData($data)) {
            return null;
        }

        if (! empty($data['manual_make_id']) && ! empty($data['manual_model_id'])) {
            $modelBelongsToMake = VehicleModel::where('id', $data['manual_model_id'])
                ->where('make_id', $data['manual_make_id'])
                ->exists();

            abort_if(! $modelBelongsToMake, 422, 'Selected vehicle model does not belong to the selected make.');
        }

        $existingVehicle = null;

        if (! empty($data['manual_plate_number'])) {
            $existingVehicle = Vehicle::where('company_id', $companyId)
                ->where('client_id', $data['client_id'])
                ->where('plate_number', $data['manual_plate_number'])
                ->first();
        }

        if (! $existingVehicle && ! empty($data['manual_vin'])) {
            $existingVehicle = Vehicle::where('company_id', $companyId)
                ->where('client_id', $data['client_id'])
                ->where('vin', $data['manual_vin'])
                ->first();
        }

        $vehiclePayload = [
            'company_id' => $companyId,
            'client_id' => $data['client_id'],
            'make_id' => $data['manual_make_id'] ?? null,
            'model_id' => $data['manual_model_id'] ?? null,
            'year' => $data['manual_year'] ?? null,
            'color' => $data['manual_color'] ?? null,
            'plate_number' => $data['manual_plate_number'] ?? null,
            'vin' => $data['manual_vin'] ?? null,
            'current_mileage' => $data['manual_current_mileage'] ?? null,
            'registration_expiry_date' => $data['manual_registration_expiry_date'] ?? null,
            'insurance_expiry_date' => $data['manual_insurance_expiry_date'] ?? null,
        ];

        if ($existingVehicle) {
            $existingVehicle->update(array_filter(
                $vehiclePayload,
                fn ($value) => $value !== null && $value !== ''
            ));

            return (int) $existingVehicle->id;
        }

        $vehicle = Vehicle::create($vehiclePayload);

        return (int) $vehicle->id;
    }

    protected function hasManualVehicleData(array $data): bool
    {
        $fields = [
            'manual_make_id',
            'manual_model_id',
            'manual_year',
            'manual_color',
            'manual_plate_number',
            'manual_vin',
            'manual_current_mileage',
            'manual_registration_expiry_date',
            'manual_insurance_expiry_date',
        ];

        foreach ($fields as $field) {
            if (! empty($data[$field])) {
                return true;
            }
        }

        return false;
    }

    protected function baseOpportunityQuery(int $companyId, string $q)
    {
        return Opportunity::query()
            ->where('company_id', $companyId)
            ->with([
                'client:id,name,phone,email',
                'lead:id,name,phone,email',
                'assignee:id,name,role',
                'vehicleMake:id,name',
                'vehicleModel:id,name',
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('stage', 'like', "%{$q}%")
                        ->orWhere('priority', 'like', "%{$q}%")
                        ->orWhere('service_type', 'like', "%{$q}%")
                        ->orWhere('notes', 'like', "%{$q}%")
                        ->orWhere('close_reason', 'like', "%{$q}%")
                        ->orWhereHas('client', function ($clientQuery) use ($q) {
                            $clientQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        })
                        ->orWhereHas('lead', function ($leadQuery) use ($q) {
                            $leadQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        })
                        ->orWhereHas('vehicleMake', function ($makeQuery) use ($q) {
                            $makeQuery->where('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('vehicleModel', function ($modelQuery) use ($q) {
                            $modelQuery->where('name', 'like', "%{$q}%");
                        });
                });
            });
    }

    protected function opportunityCounts(int $companyId): array
    {
        return [
            'open' => Opportunity::where('company_id', $companyId)
                ->where('is_archived', false)
                ->whereNotIn('stage', [
                    Opportunity::STAGE_CLOSED_WON,
                    Opportunity::STAGE_CLOSED_LOST,
                ])
                ->count(),

            'appointment' => Opportunity::where('company_id', $companyId)
                ->where('is_archived', false)
                ->where('stage', 'appointment')
                ->count(),

            'missed_appointment' => Opportunity::where('company_id', $companyId)
                ->where('is_archived', false)
                ->where('stage', 'appointment')
                ->whereNotNull('expected_close_date')
                ->whereDate('expected_close_date', '<', now()->toDateString())
                ->count(),

            'won' => Opportunity::where('company_id', $companyId)
                ->where('is_archived', false)
                ->where('stage', Opportunity::STAGE_CLOSED_WON)
                ->count(),

            'lost' => Opportunity::where('company_id', $companyId)
                ->where('is_archived', false)
                ->where('stage', Opportunity::STAGE_CLOSED_LOST)
                ->count(),
        ];
    }

    protected function bucketCounts(int $companyId): array
    {
        $base = Opportunity::where('company_id', $companyId)
            ->where('is_archived', false)
            ->whereNotIn('stage', [
                Opportunity::STAGE_CLOSED_WON,
                Opportunity::STAGE_CLOSED_LOST,
            ]);

        return [
            'new' => (clone $base)->where('stage', 'new')->count(),

            'attempting_contact' => (clone $base)->where('stage', 'attempting_contact')->count(),

            'manager_confirmation_pending' => (clone $base)->where('stage', 'manager_confirmation_pending')->count(),

            'appointment' => (clone $base)->where('stage', 'appointment')->count(),

            'missed_appointment' => (clone $base)
                ->where('stage', 'appointment')
                ->whereNotNull('expected_close_date')
                ->whereDate('expected_close_date', '<', now()->toDateString())
                ->count(),

            'high_priority' => (clone $base)
                ->whereIn('priority', ['high', 'urgent'])
                ->count(),

            'unassigned' => (clone $base)
                ->whereNull('assigned_to')
                ->count(),

            'no_vehicle' => (clone $base)
                ->whereNull('vehicle_id')
                ->count(),

            'no_value' => (clone $base)
                ->where(function ($q) {
                    $q->whereNull('value')
                        ->orWhere('value', 0);
                })
                ->count(),
        ];
    }

    protected function applyBucketFilter($query, string $bucket): void
    {
        match ($bucket) {
            'new' => $query->where('stage', 'new'),

            'attempting_contact' => $query->where('stage', 'attempting_contact'),

            'manager_confirmation_pending' => $query->where('stage', 'manager_confirmation_pending'),

            'appointment' => $query->where('stage', 'appointment'),

            'missed_appointment' => $query
                ->where('stage', 'appointment')
                ->whereNotNull('expected_close_date')
                ->whereDate('expected_close_date', '<', now()->toDateString()),

            'high_priority' => $query->whereIn('priority', ['high', 'urgent']),

            'unassigned' => $query->whereNull('assigned_to'),

            'no_vehicle' => $query->whereNull('vehicle_id'),

            'no_value' => $query->where(function ($q) {
                $q->whereNull('value')
                    ->orWhere('value', 0);
            }),

            default => null,
        };
    }

    protected function createOrUpdateBookingFromOpportunity(Opportunity $opportunity, array $data): Booking
    {
        $bookingDate = $data['booking_date'] ?? null;
        $bookingSlot = $data['booking_slot'] ?? null;
        $bookingNotes = $data['booking_notes'] ?? null;

        if (! $bookingDate) {
            throw ValidationException::withMessages([
                'booking_date' => 'Please select booking date before confirming the booking.',
            ]);
        }

        if (! $bookingSlot) {
            throw ValidationException::withMessages([
                'booking_slot' => 'Please select booking slot before confirming the booking.',
            ]);
        }

        $notes = $bookingNotes
            ?: ($opportunity->notes ?: 'Auto created from opportunity');

        $existingBooking = Booking::query()
            ->where('company_id', $opportunity->company_id)
            ->where('opportunity_id', $opportunity->id)
            ->first();

        $bookingStatus = $existingBooking?->status === Booking::STATUS_CONVERTED_TO_JOB
            ? Booking::STATUS_CONVERTED_TO_JOB
            : Booking::STATUS_SCHEDULED;

        $bookingPayload = [
            'client_id' => $opportunity->client_id,
            'vehicle_id' => $opportunity->vehicle_id,

            'name' => $opportunity->title,
            'service_type' => $opportunity->service_type,
            'priority' => $opportunity->priority ?? 'medium',

            'expected_duration' => 1,
            'expected_close_date' => $bookingDate,

            'booking_date' => $bookingDate,
            'slot' => $bookingSlot,

            'status' => $bookingStatus,

            'notes' => $notes,

            'state_changed_at' => now(),
            'state_changed_by' => auth()->id(),
        ];

        if (Schema::hasColumn('bookings', 'lead_id')) {
            $bookingPayload['lead_id'] = $opportunity->lead_id;
        }

        return Booking::updateOrCreate(
            [
                'company_id' => $opportunity->company_id,
                'opportunity_id' => $opportunity->id,
            ],
            $bookingPayload
        );
    }

    protected function assignableUsers(int $companyId)
    {
        return User::where('company_id', $companyId)
            ->whereIn('role', ['admin', 'manager'])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    protected function validateStageRequirements(array $data): void
    {
        if (($data['stage'] ?? null) === Opportunity::STAGE_CLOSED_WON && empty($data['service_type'])) {
            throw ValidationException::withMessages([
                'service_type' => 'Please select at least one service type before confirming the booking.',
            ]);
        }

        if (($data['stage'] ?? null) === Opportunity::STAGE_CLOSED_WON && empty($data['booking_date'])) {
            throw ValidationException::withMessages([
                'booking_date' => 'Please select booking date before confirming the booking.',
            ]);
        }

        if (($data['stage'] ?? null) === Opportunity::STAGE_CLOSED_WON && empty($data['booking_slot'])) {
            throw ValidationException::withMessages([
                'booking_slot' => 'Please select booking slot before confirming the booking.',
            ]);
        }

        if (($data['stage'] ?? null) === Opportunity::STAGE_CLOSED_LOST && empty($data['close_reason'])) {
            throw ValidationException::withMessages([
                'close_reason' => 'Please add close reason before marking this opportunity as closed lost.',
            ]);
        }
    }

    protected function authorizeCompany(Opportunity $opportunity): void
    {
        abort_if(
            (int) $opportunity->company_id !== (int) $this->companyId(),
            403
        );
    }

    protected function validateLinkedRecords(array $data, int $companyId): void
    {
        if (! empty($data['vehicle_id'])) {
            $vehicle = Vehicle::where('company_id', $companyId)
                ->where('client_id', $data['client_id'])
                ->find($data['vehicle_id']);

            abort_if(! $vehicle, 422, 'Selected vehicle does not belong to the selected client.');
        }

        if (! empty($data['lead_id'])) {
            $lead = Lead::where('company_id', $companyId)
                ->find($data['lead_id']);

            abort_if(! $lead, 422, 'Selected lead does not belong to this company.');

            if (! empty($lead->client_id)) {
                abort_if(
                    (int) $lead->client_id !== (int) $data['client_id'],
                    422,
                    'Selected lead does not belong to the selected client.'
                );
            }
        }
    }

    protected function validateStageTransition(string $current, string $next): void
    {
        $pipeline = Opportunity::STAGES;

        $currentIndex = array_search($current, $pipeline, true);
        $nextIndex = array_search($next, $pipeline, true);

        if ($currentIndex === false || $nextIndex === false) {
            return;
        }

        if ($nextIndex < $currentIndex) {
            abort(422, 'Invalid pipeline transition.');
        }
    }
}
