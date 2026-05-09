<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\WhatsApp\WhatsAppTemplateMapping;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WhatsAppMappingController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    protected function canonicalEventKeys(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Lead Journey
            |--------------------------------------------------------------------------
            */
            'lead.created',
            'lead.whatsapp_failed.manager_alert',

            /*
            |--------------------------------------------------------------------------
            | Booking Journey
            |--------------------------------------------------------------------------
            */
            'booking.confirmed',
            'booking.rescheduled',
            'booking.cancelled',

            /*
            |--------------------------------------------------------------------------
            | Job Journey
            |--------------------------------------------------------------------------
            */
            'job.started',
            'job.progress',
            'job.done.feedback',

            /*
            |--------------------------------------------------------------------------
            | Feedback Journey
            |--------------------------------------------------------------------------
            */
            'feedback.positive.review',
            'feedback.negative.manager_alert',

            /*
            |--------------------------------------------------------------------------
            | Retention Journey
            |--------------------------------------------------------------------------
            */
            'retention.general_service',
            'retention.oil_service',
            'retention.battery',
            'retention.ac',
            'retention.tyres',
            'retention.brakes',

            /*
            |--------------------------------------------------------------------------
            | Legacy / Existing Events
            |--------------------------------------------------------------------------
            | Kept temporarily so old mappings do not disappear from the page.
            |--------------------------------------------------------------------------
            */
            'lead.created.meta',
            'lead.followup.20m',
            'lead.reply.suggest_time',
            'schedule.confirmed',
            'schedule.reminder',
        ];
    }

    public function index()
    {
        $companyId = $this->companyId();

        /*
        |--------------------------------------------------------------------------
        | Templates
        |--------------------------------------------------------------------------
        | Do not filter only active here.
        | Admin should see approved/active/pending/inactive status on the mapping page.
        |--------------------------------------------------------------------------
        */
        $templates = WhatsAppTemplate::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $mappings = WhatsAppTemplateMapping::where('company_id', $companyId)
            ->with('template')
            ->orderBy('event_key')
            ->get();

        $existingEventKeys = $mappings
            ->pluck('event_key')
            ->filter()
            ->values()
            ->all();

        $eventKeys = collect($this->canonicalEventKeys())
            ->merge($existingEventKeys)
            ->unique()
            ->values()
            ->all();

        return view('admin.whatsapp.mappings.index', compact(
            'templates',
            'mappings',
            'eventKeys'
        ));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'event_key' => [
                'required',
                'string',
                'max:120',
            ],

            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('whatsapp_templates', 'id')
                    ->where('company_id', $companyId),
            ],
        ]);

        WhatsAppTemplateMapping::updateOrCreate(
            [
                'company_id' => $companyId,
                'event_key'  => $data['event_key'],
            ],
            [
                'template_id' => $data['template_id'] ?? null,
                'is_active'   => ! empty($data['template_id']),
            ]
        );

        return back()->with('success', 'WhatsApp template mapping saved.');
    }

    public function update(Request $request, WhatsAppTemplateMapping $mapping)
    {
        $this->ensureMappingBelongsToCompany($mapping);

        $companyId = $this->companyId();

        $data = $request->validate([
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('whatsapp_templates', 'id')
                    ->where('company_id', $companyId),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $mapping->update([
            'template_id' => $data['template_id'] ?? null,
            'is_active'   => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('success', 'WhatsApp template mapping updated.');
    }

    public function toggle(WhatsAppTemplateMapping $mapping)
    {
        $this->ensureMappingBelongsToCompany($mapping);

        if (! $mapping->template_id && ! $mapping->is_active) {
            return back()->with('warning', 'Cannot activate this mapping because no template is assigned.');
        }

        $mapping->is_active = ! $mapping->is_active;
        $mapping->save();

        return back()->with('success', 'WhatsApp template mapping status updated.');
    }

    private function ensureMappingBelongsToCompany(WhatsAppTemplateMapping $mapping): void
    {
        abort_unless((int) $mapping->company_id === $this->companyId(), 404);
    }
}