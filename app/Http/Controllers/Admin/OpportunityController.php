<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OpportunityController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(!$companyId, 403);

        return $companyId;
    }

    /** 📄 Index */
    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $opportunities = Opportunity::query()
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->with([
                'client:id,name',
                'assignee:id,name',
                'vehicleMake:id,name',
                'vehicleModel:id,name'
            ])
            ->when($request->q, function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->q . '%');
            })
            ->when($request->stage, function ($q) use ($request) {
                $q->where('stage', $request->stage);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.opportunities.index', compact('opportunities'));
    }

    /** 📦 Archived */
    public function archived()
    {
        $companyId = $this->companyId();

        $opportunities = Opportunity::query()
            ->where('company_id', $companyId)
            ->where('is_archived', true)
            ->with([
                'client:id,name',
                'assignee:id,name',
                'vehicleMake:id,name',
                'vehicleModel:id,name'
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
                ->get(['id', 'name', 'phone']),

            'leads' => Lead::where('company_id', $companyId)
                ->get(['id', 'name', 'phone']),

            'makes' => VehicleMake::orderBy('name')->get(),

            'models' => VehicleModel::orderBy('name')->get(),
        ]);
    }

    /** 💾 Store */
    public function store(Request $request)
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'lead_id' => [
                'nullable',
                Rule::exists('leads', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'title' => ['required', 'string', 'max:255'],

            'stage' => [
                'required',
                Rule::in(Opportunity::STAGES),
            ],

            'service_type' => ['nullable', 'string'],
            'value' => ['nullable', 'numeric'],
            'expected_close_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'notes' => ['nullable', 'string'],

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ]);

        $this->validateLinkedRecords($data, $companyId);

        if ($data['stage'] === Opportunity::STAGE_CLOSED_WON && empty($data['service_type'])) {
            return back()->withErrors([
                'service_type' => 'Please add service type before closing the opportunity.'
            ])->withInput();
        }

        if ($data['stage'] === Opportunity::STAGE_CLOSED_WON && empty($data['expected_close_date'])) {
            return back()->withErrors([
                'expected_close_date' => 'Please add expected close date before closing the opportunity.'
            ])->withInput();
        }

        $data['company_id'] = $companyId;
        $data['is_archived'] = false;

        DB::transaction(function () use ($data) {

            if ($data['stage'] === Opportunity::STAGE_CLOSED_WON) {
                $data['is_converted'] = true;
            }

            if ($data['stage'] === Opportunity::STAGE_CLOSED_LOST) {
                $data['is_converted'] = false;
            }

            $opportunity = Opportunity::create($data);

            /** AUTO BOOKING IF CLOSED WON */
            if ($data['stage'] === Opportunity::STAGE_CLOSED_WON) {
                Booking::create([
                    'company_id' => $opportunity->company_id,
                    'client_id' => $opportunity->client_id,
                    'opportunity_id' => $opportunity->id,

                    'vehicle_id' => $opportunity->vehicle_id,

                    'name' => $opportunity->title,
                    'service_type' => $opportunity->service_type,

                    'priority' => $opportunity->priority ?? 'medium',

                    'expected_duration' => 1,
                    'expected_close_date' => $opportunity->expected_close_date,

                    'booking_date' => $opportunity->expected_close_date,
                    'slot' => 'morning',

                    'status' => Booking::STATUS_PENDING,

                    'notes' => $opportunity->notes ?? 'Auto created from opportunity',

                    'state_changed_at' => now(),
                    'state_changed_by' => auth()->id(),
                ]);
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
            'vehicleModel'
        ]);

        return view('admin.opportunities.show', compact('opportunity'));
    }

    /** ✏️ Edit */
    public function edit(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $companyId = $this->companyId();

        return view('admin.opportunities.edit', [
            'opportunity' => $opportunity,

            'clients' => Client::where('company_id', $companyId)
                ->get(['id', 'name', 'phone']),

            'leads' => Lead::where('company_id', $companyId)
                ->get(['id', 'name', 'phone']),

            'makes' => VehicleMake::orderBy('name')->get(),

            'models' => VehicleModel::orderBy('name')->get(),
        ]);
    }

    /** 🔁 Update */
    public function update(Request $request, Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $companyId = $this->companyId();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],

            'stage' => [
                'required',
                Rule::in(Opportunity::STAGES),
            ],

            'service_type' => ['nullable', 'string'],
            'value' => ['nullable', 'numeric'],
            'expected_close_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'notes' => ['nullable', 'string'],

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],

            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
        ]);

        $data['client_id'] = $opportunity->client_id;

        $this->validateLinkedRecords($data, $companyId);

        unset($data['client_id']);

        if ($data['stage'] === Opportunity::STAGE_CLOSED_WON && empty($data['service_type'])) {
            return back()->withErrors([
                'service_type' => 'Please add service type before closing the opportunity.'
            ])->withInput();
        }

        if ($data['stage'] === Opportunity::STAGE_CLOSED_WON && empty($data['expected_close_date'])) {
            return back()->withErrors([
                'expected_close_date' => 'Please add expected close date before closing the opportunity.'
            ])->withInput();
        }

        DB::transaction(function () use ($opportunity, $data) {

            $currentStage = $opportunity->stage;
            $newStage = $data['stage'];

            $this->validateStageTransition($currentStage, $newStage);

            if ($newStage === Opportunity::STAGE_CLOSED_WON) {
                $data['is_converted'] = true;
            }

            if ($newStage === Opportunity::STAGE_CLOSED_LOST) {
                $data['is_converted'] = false;
            }

            $opportunity->update($data);

            /** AUTO CREATE BOOKING */
            if ($newStage === Opportunity::STAGE_CLOSED_WON) {

                $existingBooking = Booking::where('company_id', $opportunity->company_id)
                    ->where('opportunity_id', $opportunity->id)
                    ->first();

                if (!$existingBooking) {
                    Booking::create([
                        'company_id' => $opportunity->company_id,
                        'client_id' => $opportunity->client_id,
                        'opportunity_id' => $opportunity->id,

                        'vehicle_id' => $opportunity->vehicle_id,

                        'name' => $opportunity->title,
                        'service_type' => $opportunity->service_type,

                        'priority' => $opportunity->priority ?? 'medium',

                        'expected_duration' => 1,
                        'expected_close_date' => $opportunity->expected_close_date,

                        'booking_date' => $opportunity->expected_close_date,
                        'slot' => 'morning',

                        'status' => Booking::STATUS_PENDING,

                        'notes' => $opportunity->notes ?? 'Auto created from opportunity',

                        'state_changed_at' => now(),
                        'state_changed_by' => auth()->id(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', 'Opportunity updated.');
    }

    /** 🗑️ Archive */
    public function destroy(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $opportunity->update(['is_archived' => true]);

        return back()->with('success', 'Opportunity archived.');
    }

    /** ♻️ Restore */
    public function restore(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $opportunity->update(['is_archived' => false]);

        return redirect()
            ->route('admin.opportunities.archived')
            ->with('success', 'Opportunity restored.');
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
        if (!empty($data['vehicle_id'])) {
            $vehicle = Vehicle::where('company_id', $companyId)
                ->where('client_id', $data['client_id'])
                ->find($data['vehicle_id']);

            abort_if(!$vehicle, 422, 'Selected vehicle does not belong to the selected client.');
        }

        if (!empty($data['lead_id'])) {
            $lead = Lead::where('company_id', $companyId)
                ->find($data['lead_id']);

            abort_if(!$lead, 422, 'Selected lead does not belong to this company.');

            if (!empty($lead->client_id)) {
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