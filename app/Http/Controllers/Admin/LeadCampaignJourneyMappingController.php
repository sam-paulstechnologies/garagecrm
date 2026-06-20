<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeadCampaignJourneyMapping;
use App\Services\Leads\LeadCampaignTypeJourneyMap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadCampaignJourneyMappingController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $this->companyId($request);

        LeadCampaignTypeJourneyMap::ensureDefaultsForCompany($companyId, null, $request->user()?->id);

        $mappings = LeadCampaignJourneyMapping::query()
            ->where('company_id', $companyId)
            ->orderByRaw($this->defaultOrderSql())
            ->orderBy('campaign_type')
            ->get();

        return view('admin.growth.journey-mapping.index', [
            'mappings' => $mappings,
            'summary' => $this->summary($mappings),
            'defaults' => LeadCampaignTypeJourneyMap::defaults(),
        ]);
    }

    public function update(Request $request, LeadCampaignJourneyMapping $mapping)
    {
        $companyId = $this->companyId($request);

        abort_if((int) $mapping->company_id !== $companyId, 403);

        $data = $this->validatedMapping($request);

        $mapping->update(array_merge($data, [
            'updated_by' => $request->user()?->id,
        ]));

        return back()->with('success', 'Campaign journey mapping updated.');
    }

    public function bulkUpdate(Request $request)
    {
        $companyId = $this->companyId($request);
        $saveRow = $request->input('save_row');
        $rows = $request->input('mappings', []);

        $query = LeadCampaignJourneyMapping::query()
            ->where('company_id', $companyId);

        if ($saveRow) {
            $query->where('id', (int) $saveRow);
            $rows = array_intersect_key($rows, [(string) $saveRow => true, (int) $saveRow => true]);
        }

        $mappings = $query->get()->keyBy('id');
        $updated = 0;

        DB::transaction(function () use ($request, $mappings, $rows, &$updated) {
            foreach ($rows as $id => $row) {
                $mapping = $mappings->get((int) $id);

                if (! $mapping) {
                    continue;
                }

                $validated = validator($row, $this->mappingRules())->validate();

                $mapping->update(array_merge($this->normalizeMappingPayload($validated), [
                    'updated_by' => $request->user()?->id,
                ]));

                $updated++;
            }
        });

        return back()->with('success', "Campaign journey mappings saved: {$updated}.");
    }

    public function resetMissingDefaults(Request $request)
    {
        $companyId = $this->companyId($request);

        LeadCampaignTypeJourneyMap::ensureDefaultsForCompany($companyId, null, $request->user()?->id);

        return back()->with('success', 'Missing campaign journey defaults were restored.');
    }

    private function companyId(Request $request): int
    {
        $companyId = (int) ($request->user()?->company_id ?? 0);

        abort_if($companyId <= 0, 403);

        return $companyId;
    }

    private function validatedMapping(Request $request): array
    {
        return $this->normalizeMappingPayload($request->validate($this->mappingRules()));
    }

    private function mappingRules(): array
    {
        return [
            'journey_label' => ['nullable', 'string', 'max:191'],
            'journey_key' => ['nullable', 'string', 'max:191'],
            'journey_trigger_key' => ['nullable', 'string', 'max:191'],
            'is_active' => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
            'preview_only' => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
            'whatsapp_enabled' => ['nullable', Rule::in(['0', '1', 0, 1, true, false])],
            'whatsapp_template_name' => ['nullable', 'string', 'max:191'],
            'followup_template_name' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function normalizeMappingPayload(array $data): array
    {
        return [
            'journey_label' => $this->nullableString($data['journey_label'] ?? null),
            'journey_key' => $this->nullableString($data['journey_key'] ?? null),
            'journey_trigger_key' => $this->nullableString($data['journey_trigger_key'] ?? null),
            'is_active' => $this->truthy($data['is_active'] ?? false),
            'preview_only' => $this->truthy($data['preview_only'] ?? false),
            'whatsapp_enabled' => $this->truthy($data['whatsapp_enabled'] ?? false),
            'whatsapp_template_name' => $this->nullableString($data['whatsapp_template_name'] ?? null),
            'followup_template_name' => $this->nullableString($data['followup_template_name'] ?? null),
            'notes' => $this->nullableString($data['notes'] ?? null),
        ];
    }

    private function truthy(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function summary($mappings): array
    {
        return [
            'total' => $mappings->count(),
            'active' => $mappings->where('is_active', true)->count(),
            'preview_only' => $mappings->where('preview_only', true)->count(),
            'whatsapp_enabled' => $mappings->where('whatsapp_enabled', true)->count(),
            'missing_journey_keys' => $mappings->filter(fn ($mapping) => blank($mapping->journey_key))->count(),
        ];
    }

    private function defaultOrderSql(): string
    {
        $cases = collect(LeadCampaignTypeJourneyMap::labels())
            ->values()
            ->map(fn ($label, $index) => "when '" . str_replace("'", "''", $label) . "' then " . ($index + 1))
            ->implode(' ');

        return "case campaign_type {$cases} else 999 end";
    }
}
