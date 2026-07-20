<?php

namespace App\Http\Controllers\SuperAdmin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\PlatformMarketing\PlatformMarketingProspect;
use App\Models\PlatformMarketing\PlatformMarketingSegment;
use App\Services\PlatformMarketing\PlatformProspectService;
use Illuminate\Http\Request;

class ProspectController extends Controller
{
    public function index(Request $request)
    {
        $query = PlatformMarketingProspect::query();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('business_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('normalized_phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->trim()->toString()) {
            $query->where('status', $status);
        }

        if ($consent = $request->string('consent_status')->trim()->toString()) {
            $query->where('consent_status', $consent);
        }

        return view('super_admin.marketing.prospects.index', [
            'prospects' => $query->latest()->paginate(20)->withQueryString(),
            'statuses' => PlatformMarketingProspect::STATUSES,
            'buckets' => PlatformMarketingProspect::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function create()
    {
        return view('super_admin.marketing.prospects.form', [
            'prospect' => new PlatformMarketingProspect(['status' => 'new', 'consent_status' => 'unknown', 'interested_product' => 'SayaraForce']),
            'statuses' => PlatformMarketingProspect::STATUSES,
        ]);
    }

    public function store(Request $request, PlatformProspectService $service)
    {
        $service->createOrUpdate($this->validated($request), null, $request->user()->id);

        return redirect()->route('super-admin.marketing.prospects.index')->with('success', 'Prospect created.');
    }

    public function show(PlatformMarketingProspect $prospect)
    {
        $prospect->load(['conversations.messages', 'campaignRecipients.campaign']);

        return view('super_admin.marketing.prospects.show', [
            'prospect' => $prospect,
            'segments' => PlatformMarketingSegment::orderBy('name')->get(),
        ]);
    }

    public function edit(PlatformMarketingProspect $prospect)
    {
        return view('super_admin.marketing.prospects.form', [
            'prospect' => $prospect,
            'statuses' => PlatformMarketingProspect::STATUSES,
        ]);
    }

    public function update(Request $request, PlatformMarketingProspect $prospect, PlatformProspectService $service)
    {
        $service->createOrUpdate($this->validated($request), $prospect, $request->user()->id);

        return redirect()->route('super-admin.marketing.prospects.show', $prospect)->with('success', 'Prospect updated.');
    }

    public function export()
    {
        $rows = PlatformMarketingProspect::orderBy('id')->get([
            'business_name',
            'contact_name',
            'whatsapp_number',
            'email',
            'country',
            'city',
            'business_type',
            'status',
            'consent_status',
            'lead_score',
        ]);

        $csv = "business_name,contact_name,whatsapp_number,email,country,city,business_type,status,consent_status,lead_score\n";

        foreach ($rows as $row) {
            $csv .= collect($row->toArray())->map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"')->implode(',')."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="platform-prospects.csv"',
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'business_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'whatsapp_number' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'business_type' => ['nullable', 'string', 'max:120'],
            'branches_count' => ['nullable', 'integer', 'min:0'],
            'employees_count' => ['nullable', 'integer', 'min:0'],
            'source' => ['nullable', 'string', 'max:120'],
            'source_detail' => ['nullable', 'string', 'max:255'],
            'interested_product' => ['nullable', 'string', 'max:120'],
            'current_software' => ['nullable', 'string', 'max:255'],
            'pain_points' => ['nullable', 'string'],
            'lead_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'string', 'in:'.implode(',', PlatformMarketingProspect::STATUSES)],
            'consent_status' => ['required', 'string', 'in:unknown,opted_in,opted_out,not_required'],
            'consent_source' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
