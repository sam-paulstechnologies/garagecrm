<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Opportunity;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Models\Job\Booking;
use App\Models\Shared\Communication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 🔔 Events
use App\Events\OpportunityStageChanged;

class OpportunityController extends Controller
{
    /** 📄 List opportunities with search, filters, sorting, pagination */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $q        = trim((string) $request->get('q', ''));
        $stage    = $request->get('stage');
        $source   = $request->get('source');
        $assignee = $request->get('assigned_to');
        $priority = $request->get('priority');
        $order    = $request->get('order', 'latest');

        $ops = Opportunity::query()
            ->with([
                'client:id,name,phone,email',
                'lead:id,name,phone,email,company_id',
                'vehicleMake:id,name',
                'vehicleModel:id,name',
            ])
            ->where('company_id', $companyId)
            ->when($q, fn($q1) => $q1->where(fn($w) => $w
                ->where('title', 'like', "%{$q}%")
                ->orWhere('notes', 'like', "%{$q}%")
                ->orWhere('source', 'like', "%{$q}%")))
            ->when($stage, fn($q2) => $q2->where('stage', $stage))
            ->when($source, fn($q3) => $q3->where('source', $source))
            ->when($assignee, fn($q4) => $q4->where('assigned_to', $assignee))
            ->when($priority, fn($q5) => $q5->where('priority', $priority))
            ->when($order === 'value', fn($q6) => $q6->orderByDesc('value')->latest('id'))
            ->when($order === 'next_follow_up', fn($q7) => $q7->orderBy('next_follow_up')->latest('id'))
            ->when($order === 'expected_close', fn($q8) => $q8->orderBy('expected_close_date')->latest('id'))
            ->when($order === 'latest', fn($q9) => $q9->latest())
            ->paginate(20)
            ->withQueryString();

        return view('admin.opportunities.index', compact(
            'ops','q','stage','source','assignee','priority','order'
        ))->with('opportunities', $ops);
    }

    /** 🗂️ Archived opportunities */
    public function archived(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $q = trim((string) $request->get('q', ''));

        $ops = Opportunity::onlyTrashed()
            ->where('company_id', $companyId)
            ->when($q, fn($qq) => $qq->where('title', 'like', "%{$q}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.opportunities.archived', ['opportunities' => $ops, 'q' => $q]);
    }

    public function restore($id)
    {
        $opportunity = Opportunity::onlyTrashed()
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $opportunity->restore();

        return redirect()->route('admin.opportunities.index')->with('success', 'Opportunity restored.');
    }

    /** ➕ Create form (company-scoped options) */
    public function create()
    {
        $companyId = auth()->user()->company_id;

        $clients  = Client::where('company_id', $companyId)->orderBy('name')->get(['id','name','phone','email']);
        $leads    = Lead::where('company_id', $companyId)->latest()->get(['id','name','email','phone']);

        // Vehicles: include VIN if present
        $vehicleCols = Schema::hasColumn('vehicles', 'vin')
            ? ['id','client_id','plate_number','vin','company_id']
            : ['id','client_id','plate_number','company_id'];
        $vehicles = Vehicle::where('company_id', $companyId)->latest()->get($vehicleCols);

        $makes = VehicleMake::orderBy('name')->get(['id','name']);

        // Models: cope with either `vehicle_make_id` OR `make_id`
        if (Schema::hasColumn('vehicle_models', 'vehicle_make_id')) {
            $models = VehicleModel::orderBy('name')->get(['id','name','vehicle_make_id']);
        } elseif (Schema::hasColumn('vehicle_models', 'make_id')) {
            // alias make_id → vehicle_make_id so views that expect that key keep working
            $models = VehicleModel::select(['id','name', DB::raw('make_id as vehicle_make_id')])
                ->orderBy('name')
                ->get();
        } else {
            // fallback: at least provide id+name
            $models = VehicleModel::orderBy('name')->get(['id','name']);
        }

        return view('admin.opportunities.create', compact('clients', 'leads', 'vehicles', 'makes', 'models'));
    }

    /** 💾 Store (optionally schedule Booking on Closed Won) + 🔔 fire stage-changed */
    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'           => ['required','exists:clients,id'],
            'lead_id'             => ['nullable','exists:leads,id'],
            'title'               => ['required','string','max:255'],
            'service_type'        => ['required_if:stage,closed_won','string'],
            'stage'               => ['required','in:new,attempting_contact,appointment,offer,closed_won,closed_lost'],
            'value'               => ['nullable','numeric'],
            'expected_close_date' => ['nullable','date'],
            'notes'               => ['nullable','string'],
            'source'              => ['nullable','string','max:100'],
            'assigned_to'         => ['nullable','integer'],
            'priority'            => ['nullable','in:low,medium,high'],
            'expected_duration'   => ['nullable','integer'],
            'score'               => ['nullable','integer'],
            'is_converted'        => ['nullable','boolean'],
            'close_reason'        => ['nullable','string'],
            'next_follow_up'      => ['nullable','date'],
            'vehicle_id'          => ['nullable','exists:vehicles,id'],
            'vehicle_make_id'     => ['nullable','exists:vehicle_makes,id'],
            'vehicle_model_id'    => ['nullable','exists:vehicle_models,id'],
            'other_make'          => ['nullable','string','max:255'],
            'other_model'         => ['nullable','string','max:255'],
            'booking_date'        => ['nullable','date'],
            'booking_time'        => ['nullable','string'],
        ], [
            'service_type.required_if' => 'Please select at least one service when marking the opportunity as Closed Won.',
        ]);

        $data['company_id']   = auth()->user()->company_id;
        $data['is_converted'] = $data['is_converted'] ?? false;

        $opportunity = null;
        DB::transaction(function () use (&$opportunity, $data) {
            $opportunity = Opportunity::create($data);

            if (
                ($data['stage'] ?? null) === 'closed_won' &&
                !empty($data['booking_date']) &&
                !empty($data['booking_time'])
            ) {
                $this->createOrUpdateBookingForOpportunity($opportunity, $data);
            }
        });

        $newStage = $opportunity->stage;
        if ($newStage !== 'new') {
            $oppId = $opportunity->id;
            DB::afterCommit(function () use ($oppId, $newStage) {
                $fresh = Opportunity::find($oppId);
                event(new OpportunityStageChanged($fresh, 'new', $newStage));
            });
        }

        if (
            $request->input('stage') === 'closed_won' &&
            $request->filled(['booking_date', 'booking_time'])
        ) {
            return redirect()->route('admin.bookings.index')
                ->with('success', 'Opportunity created and Booking scheduled.');
        }

        return redirect()->route('admin.opportunities.index')->with('success', 'Opportunity created.');
    }

    /** ✏️ Edit form */
    public function edit(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);
        $companyId = auth()->user()->company_id;

        $clients  = Client::where('company_id', $companyId)->orderBy('name')->get(['id','name','phone','email']);
        $leads    = Lead::where('company_id', $companyId)->latest()->get(['id','name','email','phone']);

        $vehicleCols = Schema::hasColumn('vehicles', 'vin')
            ? ['id','client_id','plate_number','vin','company_id']
            : ['id','client_id','plate_number','company_id'];
        $vehicles = Vehicle::where('company_id', $companyId)->latest()->get($vehicleCols);

        $makes = VehicleMake::orderBy('name')->get(['id','name']);

        if (Schema::hasColumn('vehicle_models', 'vehicle_make_id')) {
            $models = VehicleModel::orderBy('name')->get(['id','name','vehicle_make_id']);
        } elseif (Schema::hasColumn('vehicle_models', 'make_id')) {
            $models = VehicleModel::select(['id','name', DB::raw('make_id as vehicle_make_id')])
                ->orderBy('name')
                ->get();
        } else {
            $models = VehicleModel::orderBy('name')->get(['id','name']);
        }

        return view('admin.opportunities.edit', compact('opportunity', 'clients', 'leads', 'vehicles', 'makes', 'models'));
    }

    /** 🔁 Update (optionally schedule/update Booking on Closed Won) + 🔔 fire stage-changed */
    public function update(Request $request, Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $data = $request->validate([
            'client_id'           => ['required','exists:clients,id'],
            'lead_id'             => ['nullable','exists:leads,id'],
            'title'               => ['required','string','max:255'],
            'service_type'        => ['required_if:stage,closed_won','string'],
            'stage'               => ['required','in:new,attempting_contact,appointment,offer,closed_won,closed_lost'],
            'value'               => ['nullable','numeric'],
            'expected_close_date' => ['nullable','date'],
            'notes'               => ['nullable','string'],
            'source'              => ['nullable','string','max:100'],
            'assigned_to'         => ['nullable','integer'],
            'priority'            => ['nullable','in:low,medium,high'],
            'expected_duration'   => ['nullable','integer'],
            'score'               => ['nullable','integer'],
            'is_converted'        => ['nullable','boolean'],
            'close_reason'        => ['nullable','string'],
            'next_follow_up'      => ['nullable','date'],
            'vehicle_id'          => ['nullable','exists:vehicles,id'],
            'vehicle_make_id'     => ['nullable','exists:vehicle_makes,id'],
            'vehicle_model_id'    => ['nullable','exists:vehicle_models,id'],
            'other_make'          => ['nullable','string','max:255'],
            'other_model'         => ['nullable','string','max:255'],
            'booking_date'        => ['nullable','date'],
            'booking_time'        => ['nullable','string'],
        ], [
            'service_type.required_if' => 'Please select at least one service when marking the opportunity as Closed Won.',
        ]);

        $oldStage = $opportunity->stage;

        DB::transaction(function () use ($opportunity, $data) {
            $opportunity->update($data);

            if (
                ($data['stage'] ?? null) === 'closed_won' &&
                !empty($data['booking_date']) &&
                !empty($data['booking_time'])
            ) {
                $this->createOrUpdateBookingForOpportunity($opportunity, $data);
            }
        });

        $oppId = $opportunity->id;
        DB::afterCommit(function () use ($oppId, $oldStage) {
            $fresh = Opportunity::find($oppId);
            if ($fresh && $fresh->stage !== $oldStage) {
                event(new OpportunityStageChanged($fresh, $oldStage, $fresh->stage));
            }
        });

        if (
            $request->input('stage') === 'closed_won' &&
            $request->filled(['booking_date', 'booking_time'])
        ) {
            return redirect()->route('admin.bookings.index')
                ->with('success', 'Opportunity updated and Booking scheduled.');
        }

        return redirect()->route('admin.opportunities.index')->with('success', 'Opportunity updated.');
    }

    /** 👁️ Show opportunity (and its communications) */
    public function show(Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $opportunity->loadMissing([
            'client:id,name,phone,email',
            'lead:id,name,email,phone,company_id',
            'vehicleMake:id,name',
            'vehicleModel:id,name',
        ]);

        $communications = Communication::query()
            ->forCompany($opportunity->company_id)
            ->where('opportunity_id', $opportunity->id)
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.opportunities.show', compact('opportunity', 'communications'));
    }

    /** ✅ Close Won helper */
    public function closeWon(Request $request, Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $data = $request->validate([
            'service_type'   => ['required','string'],
            'booking_date'   => ['nullable','date'],
            'booking_time'   => ['nullable','string'],
            'value'          => ['nullable','numeric'],
            'notes'          => ['nullable','string'],
        ]);

        $oldStage = $opportunity->stage;

        DB::transaction(function () use ($opportunity, $data) {
            $payload = array_merge($data, [
                'stage'        => 'closed_won',
                'is_converted' => true,
            ]);

            $opportunity->update($payload);

            if (!empty($data['booking_date']) && !empty($data['booking_time'])) {
                $this->createOrUpdateBookingForOpportunity($opportunity, [
                    'company_id'   => $opportunity->company_id,
                    'client_id'    => $opportunity->client_id,
                    'service_type' => $data['service_type'],
                    'assigned_to'  => $opportunity->assigned_to,
                    'notes'        => $data['notes'] ?? null,
                    'booking_date' => $data['booking_date'],
                    'booking_time' => $data['booking_time'],
                ]);
            }
        });

        DB::afterCommit(function () use ($opportunity, $oldStage) {
            $fresh = $opportunity->fresh();
            event(new OpportunityStageChanged($fresh, $oldStage, 'closed_won'));
        });

        return $request->expectsJson()
            ? response()->json(['message' => 'Opportunity marked as Closed Won.'])
            : back()->with('success', 'Opportunity marked as Closed Won.');
    }

    /** ❌ Close Lost helper */
    public function closeLost(Request $request, Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $data = $request->validate([
            'close_reason' => ['nullable','string','max:500'],
            'value'        => ['nullable','numeric'],
            'notes'        => ['nullable','string'],
        ]);

        $oldStage = $opportunity->stage;

        DB::transaction(function () use ($opportunity, $data) {
            $opportunity->update(array_merge($data, [
                'stage'        => 'closed_lost',
                'is_converted' => false,
            ]));
        });

        DB::afterCommit(function () use ($opportunity, $oldStage) {
            $fresh = $opportunity->fresh();
            event(new OpportunityStageChanged($fresh, $oldStage, 'closed_lost'));
        });

        return $request->expectsJson()
            ? response()->json(['message' => 'Opportunity marked as Closed Lost.'])
            : back()->with('success', 'Opportunity marked as Closed Lost.');
    }

    /** 📆 Touch next follow-up */
    public function touchFollowUp(Request $request, Opportunity $opportunity)
    {
        $this->authorizeCompany($opportunity);

        $data = $request->validate([
            'next_follow_up' => ['nullable','date'],
        ]);

        $opportunity->update([
            'next_follow_up' => $data['next_follow_up'] ?? now()->addDays(2),
        ]);

        return $request->expectsJson()
            ? response()->json(['next_follow_up' => $opportunity->next_follow_up])
            : back()->with('success', 'Next follow-up updated.');
    }

    /** 🔐 Company guard */
    protected function authorizeCompany(Opportunity $opportunity): void
    {
        abort_if($opportunity->company_id !== auth()->user()->company_id, 403);
    }

    /**
     * 🧩 Booking upsert (scheduled_at or booking_date/booking_time)
     */
    protected function createOrUpdateBookingForOpportunity(Opportunity $opportunity, array $data): void
    {
        $lookup = [
            'company_id'     => $opportunity->company_id,
            'opportunity_id' => $opportunity->id,
        ];

        $payload = [
            'client_id'    => $opportunity->client_id,
            'service_type' => $data['service_type'] ?? '',
            'notes'        => $data['notes'] ?? null,
            'assigned_to'  => $data['assigned_to'] ?? $opportunity->assigned_to,
        ];

        if (Schema::hasColumn('bookings', 'scheduled_at')) {
            $payload['scheduled_at'] = ($data['booking_date'] ?? '') . ' ' . ($data['booking_time'] ?? '') . ':00';
        } else {
            if (Schema::hasColumn('bookings', 'booking_date')) {
                $payload['booking_date'] = $data['booking_date'] ?? null;
            }
            if (Schema::hasColumn('bookings', 'booking_time')) {
                $payload['booking_time'] = $data['booking_time'] ?? null;
            }
        }

        Booking::updateOrCreate($lookup, $payload);
    }
}
