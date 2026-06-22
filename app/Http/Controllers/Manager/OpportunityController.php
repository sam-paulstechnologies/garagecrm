<?php

namespace App\Http\Controllers\Manager;

use App\Events\BookingStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking as JobBooking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OpportunityController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    | These values must match the actual opportunity stage values.
    | Do not store display labels like "Closed Won" or unsupported stages like
    | "follow_up" if the database enum does not support them.
    |--------------------------------------------------------------------------
    */
    protected array $opportunityStages = [
        Opportunity::STAGE_NEW,
        Opportunity::STAGE_ATTEMPTING_CONTACT,
        Opportunity::STAGE_APPOINTMENT,
        Opportunity::STAGE_OFFER,
        Opportunity::STAGE_MANAGER_CONFIRMATION_PENDING,
        Opportunity::STAGE_BOOKING_CONFIRMED,
        Opportunity::STAGE_CLOSED_LOST,
    ];

    protected array $stageLabels = [
        Opportunity::STAGE_NEW => 'New',
        Opportunity::STAGE_ATTEMPTING_CONTACT => 'Attempting Contact',
        Opportunity::STAGE_APPOINTMENT => 'Appointment',
        Opportunity::STAGE_OFFER => 'Offer',
        Opportunity::STAGE_MANAGER_CONFIRMATION_PENDING => 'Manager Confirmation Pending',
        Opportunity::STAGE_BOOKING_CONFIRMED => 'Booking Confirmed',
        Opportunity::STAGE_CLOSED_LOST => 'Closed Lost',
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
        $stage = $this->normalizeOpportunityStage($request->get('stage'));
        $status = trim((string) $request->get('status', ''));

        $opportunities = Opportunity::query()
            ->where('company_id', $companyId)
            ->when(Schema::hasColumn('opportunities', 'is_active'), function ($query) {
                $query->where('is_active', 1);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    foreach ([
                        'title',
                        'name',
                        'customer_name',
                        'client_name',
                        'phone',
                        'mobile',
                        'phone_number',
                        'whatsapp_number',
                        'email',
                        'vehicle_make',
                        'vehicle_model',
                        'notes',
                        'manager_notes',
                        'internal_notes',
                    ] as $column) {
                        if (Schema::hasColumn('opportunities', $column)) {
                            $sub->orWhere($column, 'like', '%' . $q . '%');
                        }
                    }
                });
            })
            ->when($stage !== '' && Schema::hasColumn('opportunities', 'stage'), function ($query) use ($stage) {
                $query->where('stage', $stage);
            })
            ->when($status !== '' && Schema::hasColumn('opportunities', 'status'), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(Schema::hasColumn('opportunities', 'stage'), function ($query) {
                $query->whereNotIn('stage', [
                    'collecting_details',
                    'closed_won',
                    'Collecting Details',
                    'Closed Won',
                ]);
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $managers = $this->assignableUsers($companyId);
        $opportunityStages = $this->opportunityStages;
        $stageLabels = $this->stageLabels;

        return view('manager.opportunities.index', compact(
            'opportunities',
            'managers',
            'q',
            'stage',
            'status',
            'opportunityStages',
            'stageLabels'
        ));
    }

    public function show(Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        /*
        |--------------------------------------------------------------------------
        | Safe fallback
        |--------------------------------------------------------------------------
        | If the show blade is missing in this build, do not crash.
        |--------------------------------------------------------------------------
        */
        if (! view()->exists('manager.opportunities.show')) {
            return redirect()
                ->route('manager.opportunities.index')
                ->with('success', 'Opportunity details page is not available yet. You can manage the opportunity from the opportunities list.');
        }

        $managers = $this->assignableUsers($this->companyId());
        $opportunityStages = $this->opportunityStages;
        $stageLabels = $this->stageLabels;

        return view('manager.opportunities.show', compact(
            'opportunity',
            'managers',
            'opportunityStages',
            'stageLabels'
        ));
    }

    public function updateStage(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $validated = $request->validate([
            'stage' => ['required', 'string', Rule::notIn(['collecting_details', 'closed_won'])],
            'booking_date' => ['nullable', 'date'],
            'booking_slot' => ['nullable', Rule::in(['morning', 'afternoon', 'evening', 'full_day'])],
            'booking_notes' => ['nullable', 'string', 'max:2000'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'stage_sub_status' => ['nullable', 'string', 'max:100'],
            'stage_reason' => ['nullable', 'string', 'max:1000'],
            'close_reason' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($this->isLegacyOpportunityStageInput($validated['stage'])) {
            return back()->withErrors([
                'stage' => 'Collecting Details and Closed Won are legacy stages. Use Attempting Contact or Booking Confirmed.',
            ]);
        }

        $stage = $this->normalizeOpportunityStage($validated['stage']);

        if (! in_array($stage, $this->opportunityStages, true)) {
            return back()->withErrors([
                'stage' => 'Invalid opportunity stage selected.',
            ]);
        }

        $this->validateStageContext($stage, $validated);

        $booking = null;
        $bookingExisted = false;

        DB::transaction(function () use ($opportunity, $validated, $stage, &$booking, &$bookingExisted) {
            if (Schema::hasColumn('opportunities', 'stage')) {
                $opportunity->stage = $stage;
            }

            if (Schema::hasColumn('opportunities', 'status')) {
                $opportunity->status = $this->statusFromStage($stage);
            }

            if ($stage === Opportunity::STAGE_BOOKING_CONFIRMED && Schema::hasColumn('opportunities', 'won_at')) {
                $opportunity->won_at = now();
            }

            if ($stage === Opportunity::STAGE_CLOSED_LOST && Schema::hasColumn('opportunities', 'lost_at')) {
                $opportunity->lost_at = now();
            }

            if (Schema::hasColumn('opportunities', 'close_reason')) {
                $opportunity->close_reason = $stage === Opportunity::STAGE_CLOSED_LOST
                    ? $this->closedLostReasonFromRequest($validated, $opportunity->close_reason)
                    : null;
            }

            if (Schema::hasColumn('opportunities', 'is_converted')) {
                $opportunity->is_converted = $stage === Opportunity::STAGE_BOOKING_CONFIRMED;
            }

            if ($stage === Opportunity::STAGE_BOOKING_CONFIRMED && ! empty($validated['service_type']) && Schema::hasColumn('opportunities', 'service_type')) {
                $opportunity->service_type = $validated['service_type'];
            }

            if (! empty($validated['notes'])) {
                $this->appendNotes($opportunity, $validated['notes']);
            }

            $opportunity->save();

            if ($stage === Opportunity::STAGE_BOOKING_CONFIRMED) {
                $bookingExisted = $this->opportunityHasBooking($opportunity);
                $booking = $this->createOrUpdateBookingFromOpportunity($opportunity->fresh(), $validated);

                DB::afterCommit(function () use ($booking) {
                    $freshBooking = $booking?->fresh();

                    if ($freshBooking) {
                        event(new BookingStatusUpdated($freshBooking, (string) $freshBooking->status));
                    }
                });
            }
        });

        if ($stage === Opportunity::STAGE_BOOKING_CONFIRMED && $booking) {
            return $this->redirectToBooking($booking, $bookingExisted);
        }

        return back()->with('success', 'Opportunity stage updated successfully.');
    }

    public function assign(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $validated = $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $assignee = User::query()
            ->where('company_id', $this->companyId())
            ->findOrFail($validated['assigned_to']);

        DB::transaction(function () use ($opportunity, $assignee) {
            $assignedColumn = $this->firstExistingColumn('opportunities', [
                'assigned_to',
                'assigned_to_id',
                'assigned_user_id',
                'manager_id',
                'user_id',
                'owner_id',
            ]);

            if ($assignedColumn) {
                $opportunity->{$assignedColumn} = $assignee->id;
            }

            if (Schema::hasColumn('opportunities', 'assigned_at')) {
                $opportunity->assigned_at = now();
            }

            $opportunity->save();
        });

        return back()->with('success', 'Opportunity assigned successfully.');
    }

    public function updateFollowUp(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $validated = $request->validate([
            'follow_up_date' => ['nullable', 'date'],
            'follow_up_required' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($opportunity, $validated) {
            if (Schema::hasColumn('opportunities', 'follow_up_date')) {
                $opportunity->follow_up_date = $validated['follow_up_date'] ?? null;
            }

            if (Schema::hasColumn('opportunities', 'follow_up_required')) {
                $opportunity->follow_up_required = (bool) ($validated['follow_up_required'] ?? false);
            }

            /*
            |--------------------------------------------------------------------------
            | Follow-up handling
            |--------------------------------------------------------------------------
            | Do not set stage to "follow_up". Some schema versions do not support it.
            | Keep the current valid stage and only save follow-up fields/notes.
            |--------------------------------------------------------------------------
            */

            if (! empty($validated['notes'])) {
                $this->appendNotes($opportunity, $validated['notes']);
            }

            $opportunity->save();
        });

        return back()->with('success', 'Opportunity follow-up updated successfully.');
    }

    public function scheduleBooking(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $validated = $request->validate([
            'booking_date' => ['required', 'date'],
            'booking_time' => ['nullable', 'date_format:H:i'],
            'slot' => ['nullable', 'string', Rule::in(['morning', 'afternoon', 'evening', 'full_day'])],
            'service_type' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['slot'] = $this->normalizeBookingSlot($validated['slot'] ?? null);

        $bookingId = null;

        DB::transaction(function () use ($opportunity, $validated, &$bookingId) {
            $bookingClass = $this->bookingModelClass();

            $booking = new $bookingClass();

            if (Schema::hasColumn('bookings', 'company_id')) {
                $booking->company_id = $opportunity->company_id;
            }

            if (Schema::hasColumn('bookings', 'opportunity_id')) {
                $booking->opportunity_id = $opportunity->id;
            }

            if (Schema::hasColumn('bookings', 'lead_id') && ! empty($opportunity->lead_id)) {
                $booking->lead_id = $opportunity->lead_id;
            }

            if (Schema::hasColumn('bookings', 'client_id') && ! empty($opportunity->client_id)) {
                $booking->client_id = $opportunity->client_id;
            }

            $customerName = $opportunity->customer_name
                ?? $opportunity->client_name
                ?? $opportunity->name
                ?? $opportunity->title
                ?? null;

            if (Schema::hasColumn('bookings', 'customer_name')) {
                $booking->customer_name = $customerName;
            }

            if (Schema::hasColumn('bookings', 'name')) {
                $booking->name = $customerName;
            }

            $phone = $opportunity->phone
                ?? $opportunity->mobile
                ?? $opportunity->phone_number
                ?? $opportunity->whatsapp_number
                ?? null;

            if (Schema::hasColumn('bookings', 'phone')) {
                $booking->phone = $phone;
            }

            if (Schema::hasColumn('bookings', 'mobile')) {
                $booking->mobile = $phone;
            }

            if (Schema::hasColumn('bookings', 'phone_number')) {
                $booking->phone_number = $phone;
            }

            if (Schema::hasColumn('bookings', 'whatsapp_number')) {
                $booking->whatsapp_number = $phone;
            }

            if (Schema::hasColumn('bookings', 'email')) {
                $booking->email = $opportunity->email ?? null;
            }

            if (Schema::hasColumn('bookings', 'vehicle_id') && ! empty($opportunity->vehicle_id)) {
                $booking->vehicle_id = $opportunity->vehicle_id;
            }

            if (Schema::hasColumn('bookings', 'vehicle_make')) {
                $booking->vehicle_make = $opportunity->vehicle_make ?? $opportunity->make ?? null;
            }

            if (Schema::hasColumn('bookings', 'vehicle_model')) {
                $booking->vehicle_model = $opportunity->vehicle_model ?? $opportunity->model ?? null;
            }

            if (Schema::hasColumn('bookings', 'vehicle_year')) {
                $booking->vehicle_year = $opportunity->vehicle_year ?? null;
            }

            if (Schema::hasColumn('bookings', 'plate_number')) {
                $booking->plate_number = $opportunity->plate_number ?? null;
            }

            if (Schema::hasColumn('bookings', 'booking_date')) {
                $booking->booking_date = $validated['booking_date'];
            }

            if (Schema::hasColumn('bookings', 'scheduled_date')) {
                $booking->scheduled_date = $validated['booking_date'];
            }

            if (Schema::hasColumn('bookings', 'date')) {
                $booking->date = $validated['booking_date'];
            }

            if (Schema::hasColumn('bookings', 'booking_time')) {
                $booking->booking_time = $validated['booking_time'] ?? null;
            }

            if (Schema::hasColumn('bookings', 'scheduled_time')) {
                $booking->scheduled_time = $validated['booking_time'] ?? null;
            }

            if (Schema::hasColumn('bookings', 'time')) {
                $booking->time = $validated['booking_time'] ?? null;
            }

            if (Schema::hasColumn('bookings', 'slot')) {
                $booking->slot = $validated['slot'];
            }

            if (Schema::hasColumn('bookings', 'time_slot')) {
                $booking->time_slot = $validated['slot'];
            }

            if (Schema::hasColumn('bookings', 'service_type')) {
                $booking->service_type = $validated['service_type']
                    ?? $opportunity->service_type
                    ?? null;
            }

            if (Schema::hasColumn('bookings', 'service_category')) {
                $booking->service_category = $opportunity->service_category ?? null;
            }

            /*
            |--------------------------------------------------------------------------
            | Manager scheduled booking
            |--------------------------------------------------------------------------
            | This path is manager-confirmed, so booking should become scheduled.
            | This matches the current bookings.status enum.
            |--------------------------------------------------------------------------
            */
            if (Schema::hasColumn('bookings', 'status')) {
                $booking->status = 'scheduled';
            }

            if (Schema::hasColumn('bookings', 'confirmed_at')) {
                $booking->confirmed_at = now();
            }

            if (Schema::hasColumn('bookings', 'state_changed_at')) {
                $booking->state_changed_at = now();
            }

            if (Schema::hasColumn('bookings', 'state_changed_by')) {
                $booking->state_changed_by = auth()->id();
            }

            if (Schema::hasColumn('bookings', 'notes')) {
                $booking->notes = $validated['notes'] ?? null;
            }

            if (Schema::hasColumn('bookings', 'manager_notes')) {
                $booking->manager_notes = $validated['notes'] ?? null;
            }

            if (Schema::hasColumn('bookings', 'created_by')) {
                $booking->created_by = auth()->id();
            }

            if (Schema::hasColumn('bookings', 'scheduled_by')) {
                $booking->scheduled_by = auth()->id();
            }

            if (Schema::hasColumn('bookings', 'scheduled_at')) {
                $booking->scheduled_at = now();
            }

            $booking->save();

            $bookingId = $booking->id;

            if (Schema::hasColumn('opportunities', 'stage')) {
                $opportunity->stage = 'appointment';
            }

            if (Schema::hasColumn('opportunities', 'status')) {
                $opportunity->status = 'open';
            }

            if (Schema::hasColumn('opportunities', 'booking_id')) {
                $opportunity->booking_id = $booking->id;
            }

            if (Schema::hasColumn('opportunities', 'follow_up_required')) {
                $opportunity->follow_up_required = false;
            }

            if (Schema::hasColumn('opportunities', 'follow_up_date')) {
                $opportunity->follow_up_date = null;
            }

            $bookingNote = 'Booking scheduled for ' . $validated['booking_date'];

            if (! empty($validated['booking_time'])) {
                $bookingNote .= ' at ' . $validated['booking_time'];
            }

            if (! empty($validated['slot'])) {
                $bookingNote .= ' (' . $validated['slot'] . ')';
            }

            if (! empty($validated['notes'])) {
                $bookingNote .= '. Notes: ' . $validated['notes'];
            }

            $this->appendNotes($opportunity, $bookingNote);

            $opportunity->save();
        });

        if ($bookingId && app('router')->has('manager.bookings.show')) {
            return redirect()
                ->route('manager.bookings.show', $bookingId)
                ->with('success', 'Booking scheduled successfully from opportunity.');
        }

        return redirect()
            ->route('manager.bookings.index')
            ->with('success', 'Booking scheduled successfully from opportunity.');
    }

    public function markLost(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        $validated = $request->validate([
            'lost_reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($opportunity, $validated) {
            if (Schema::hasColumn('opportunities', 'stage')) {
                $opportunity->stage = Opportunity::STAGE_CLOSED_LOST;
            }

            if (Schema::hasColumn('opportunities', 'status')) {
                $opportunity->status = 'lost';
            }

            if (Schema::hasColumn('opportunities', 'lost_reason')) {
                $opportunity->lost_reason = $validated['lost_reason'] ?? null;
            }

            if (Schema::hasColumn('opportunities', 'close_reason')) {
                $opportunity->close_reason = $validated['lost_reason'] ?? null;
            }

            if (Schema::hasColumn('opportunities', 'lost_at')) {
                $opportunity->lost_at = now();
            }

            if (! empty($validated['notes'])) {
                $this->appendNotes($opportunity, $validated['notes']);
            }

            $opportunity->save();
        });

        return redirect()
            ->route('manager.opportunities.index')
            ->with('success', 'Opportunity marked as lost.');
    }

    public function markWon(Request $request, Opportunity $opportunity)
    {
        $this->authorizeOpportunity($opportunity);

        /*
        |--------------------------------------------------------------------------
        | Guardrail
        |--------------------------------------------------------------------------
        | Manager should not close an opportunity as won without a booking path.
        | The proper manager journey is:
        | Opportunity → Schedule Booking → Booking → Job → Invoice.
        |--------------------------------------------------------------------------
        */
        if (! $this->opportunityHasBooking($opportunity)) {
            return back()->withErrors([
                'opportunity' => 'Please schedule a booking before marking this opportunity as won.',
            ]);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($opportunity, $validated) {
            if (Schema::hasColumn('opportunities', 'stage')) {
                $opportunity->stage = Opportunity::STAGE_BOOKING_CONFIRMED;
            }

            if (Schema::hasColumn('opportunities', 'status')) {
                $opportunity->status = $this->statusFromStage(Opportunity::STAGE_BOOKING_CONFIRMED);
            }

            if (Schema::hasColumn('opportunities', 'is_converted')) {
                $opportunity->is_converted = true;
            }

            if (Schema::hasColumn('opportunities', 'won_at')) {
                $opportunity->won_at = now();
            }

            if (! empty($validated['notes'])) {
                $this->appendNotes($opportunity, $validated['notes']);
            }

            $opportunity->save();
        });

        $booking = $this->latestBookingForOpportunity($opportunity->fresh());

        return $booking
            ? $this->redirectToBooking($booking, true)
            : redirect()
                ->route('manager.opportunities.index')
                ->with('success', 'Opportunity marked as booking confirmed.');
    }

    protected function authorizeOpportunity(Opportunity $opportunity): void
    {
        abort_if((int) $opportunity->company_id !== $this->companyId(), 403);
    }

    protected function statusFromStage(string $stage): string
    {
        return match ($stage) {
            Opportunity::STAGE_BOOKING_CONFIRMED => 'won',
            Opportunity::STAGE_CLOSED_LOST => 'lost',
            Opportunity::STAGE_MANAGER_CONFIRMATION_PENDING,
            Opportunity::STAGE_APPOINTMENT,
            Opportunity::STAGE_OFFER => 'open',
            default => 'active',
        };
    }

    protected function appendNotes(Opportunity $opportunity, string $note): void
    {
        $note = trim($note);

        if ($note === '') {
            return;
        }

        $noteColumn = $this->firstExistingColumn('opportunities', [
            'manager_notes',
            'internal_notes',
            'notes',
        ]);

        if (! $noteColumn) {
            return;
        }

        $existing = trim((string) ($opportunity->{$noteColumn} ?? ''));

        $entry = '[' . now()->format('Y-m-d H:i') . '] '
            . auth()->user()?->name
            . ': '
            . $note;

        $opportunity->{$noteColumn} = $existing
            ? $existing . PHP_EOL . PHP_EOL . $entry
            : $entry;
    }

    protected function assignableUsers(int $companyId)
    {
        return User::query()
            ->where('company_id', $companyId)
            ->when(Schema::hasColumn('users', 'is_active'), function ($query) {
                $query->where('is_active', 1);
            })
            ->when(Schema::hasColumn('users', 'status'), function ($query) {
                $query->whereIn('status', ['active', 'Active', 1]);
            })
            ->whereIn('role', ['admin', 'manager'])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    protected function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function bookingModelClass(): string
    {
        return JobBooking::class;
    }

    protected function normalizeBookingSlot(?string $slot): string
    {
        $slot = strtolower(trim((string) $slot));

        return match ($slot) {
            'morning' => 'morning',
            'afternoon' => 'afternoon',
            'evening' => 'evening',
            'full_day', 'full day', 'fullday' => 'full_day',
            default => 'morning',
        };
    }

    protected function normalizeOpportunityStage(?string $stage): string
    {
        $stage = strtolower(trim((string) $stage));
        $stage = str_replace(['-', ' '], '_', $stage);

        return match ($stage) {
            'new' => Opportunity::STAGE_NEW,
            'attempting_contact', 'attempting', 'contacting', 'contacted', 'collecting_details', 'collecting', 'details', 'details_collection' => Opportunity::STAGE_ATTEMPTING_CONTACT,
            'manager_confirmation_pending', 'manager_confirmation', 'confirmation_pending' => Opportunity::STAGE_MANAGER_CONFIRMATION_PENDING,
            'appointment', 'scheduled', 'booking_scheduled' => Opportunity::STAGE_APPOINTMENT,
            'offer', 'quotation', 'quote', 'follow_up' => Opportunity::STAGE_OFFER,
            'booking_confirmed', 'closed_won', 'won' => Opportunity::STAGE_BOOKING_CONFIRMED,
            'closed_lost', 'lost' => Opportunity::STAGE_CLOSED_LOST,
            default => $stage,
        };
    }

    protected function isLegacyOpportunityStageInput(string $stage): bool
    {
        $normalized = strtolower(trim($stage));
        $normalized = str_replace(['-', ' '], '_', $normalized);

        return in_array($normalized, ['collecting_details', 'closed_won'], true);
    }

    protected function validateStageContext(string $stage, array $data): void
    {
        if ($stage === Opportunity::STAGE_BOOKING_CONFIRMED) {
            validator($data, [
                'service_type' => ['required', 'string', 'max:255'],
                'booking_date' => ['required', 'date'],
                'booking_slot' => ['required', Rule::in(['morning', 'afternoon', 'evening', 'full_day'])],
            ])->validate();
        }

        if ($stage === Opportunity::STAGE_CLOSED_LOST) {
            $subStatus = (string) ($data['stage_sub_status'] ?? '');

            validator($data, [
                'stage_sub_status' => ['required', Rule::in(array_keys($this->closedLostSubStatuses()))],
                'stage_reason' => [
                    Rule::requiredIf(fn () => $subStatus === 'other'),
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ])->validate();
        }
    }

    protected function createOrUpdateBookingFromOpportunity(Opportunity $opportunity, array $data): JobBooking
    {
        $bookingDate = $data['booking_date'] ?? null;
        $bookingSlot = $this->normalizeBookingSlot($data['booking_slot'] ?? null);

        if (! $bookingDate) {
            throw ValidationException::withMessages([
                'booking_date' => 'Please select booking date before confirming the booking.',
            ]);
        }

        $existingBooking = JobBooking::query()
            ->where('company_id', $opportunity->company_id)
            ->where('opportunity_id', $opportunity->id)
            ->first();

        $bookingStatus = $existingBooking?->status === JobBooking::STATUS_CONVERTED_TO_JOB
            ? JobBooking::STATUS_CONVERTED_TO_JOB
            : JobBooking::STATUS_SCHEDULED;

        $payload = [
            'client_id' => $opportunity->client_id,
            'vehicle_id' => $opportunity->vehicle_id,
            'lead_id' => $opportunity->lead_id,
            'name' => $opportunity->title,
            'service_type' => $data['service_type'] ?? $opportunity->service_type,
            'priority' => $opportunity->priority ?? 'medium',
            'expected_duration' => 1,
            'expected_close_date' => $bookingDate,
            'booking_date' => $bookingDate,
            'slot' => $bookingSlot,
            'status' => $bookingStatus,
            'notes' => $data['booking_notes'] ?? $opportunity->notes ?? 'Auto created from opportunity',
            'state_changed_at' => now(),
            'state_changed_by' => auth()->id(),
        ];

        return JobBooking::updateOrCreate(
            [
                'company_id' => $opportunity->company_id,
                'opportunity_id' => $opportunity->id,
            ],
            $payload
        );
    }

    protected function redirectToBooking(JobBooking $booking, bool $bookingExisted)
    {
        $message = $bookingExisted
            ? 'Booking already exists. Opening booking.'
            : 'Booking confirmed and booking created.';

        if (Route::has('manager.bookings.show')) {
            return redirect()
                ->route('manager.bookings.show', $booking)
                ->with('success', $message);
        }

        return redirect()
            ->route('manager.bookings.index')
            ->with('success', $message);
    }

    protected function closedLostSubStatuses(): array
    {
        return [
            'not_interested' => 'Not interested',
            'price_not_accepted' => 'Price not accepted',
            'customer_cancelled' => 'Customer cancelled',
            'unreachable_after_follow_up' => 'Unreachable after follow-up',
            'service_not_required' => 'Service no longer required',
            'service_not_offered' => 'Service not offered',
            'duplicate' => 'Duplicate opportunity',
            'booked_elsewhere' => 'Booked elsewhere',
            'spam_or_test' => 'Spam / test',
            'other' => 'Other',
        ];
    }

    protected function closedLostReasonFromRequest(array $data, ?string $fallback = null): ?string
    {
        $subStatus = $data['stage_sub_status'] ?? null;

        if (! $subStatus) {
            return $data['close_reason'] ?? $fallback;
        }

        $label = $this->closedLostSubStatuses()[$subStatus] ?? ucfirst(str_replace('_', ' ', (string) $subStatus));
        $reason = trim((string) ($data['stage_reason'] ?? ''));

        return $reason !== ''
            ? $label . ': ' . $reason
            : $label;
    }

    protected function latestBookingForOpportunity(Opportunity $opportunity): ?JobBooking
    {
        return JobBooking::query()
            ->where('company_id', $opportunity->company_id)
            ->where('opportunity_id', $opportunity->id)
            ->latest('id')
            ->first();
    }

    protected function opportunityHasBooking(Opportunity $opportunity): bool
    {
        if (Schema::hasColumn('opportunities', 'booking_id') && ! empty($opportunity->booking_id)) {
            return true;
        }

        if (! Schema::hasTable('bookings')) {
            return false;
        }

        return JobBooking::query()
            ->where('company_id', $opportunity->company_id)
            ->when(Schema::hasColumn('bookings', 'opportunity_id'), function ($query) use ($opportunity) {
                $query->where('opportunity_id', $opportunity->id);
            })
            ->when(Schema::hasColumn('bookings', 'status'), function ($query) {
                $query->whereIn('status', [
                    'pending',
                    'scheduled',
                    'converted_to_job',
                ]);
            })
            ->exists();
    }
}
