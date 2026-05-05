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

        abort_if(!$companyId, 403);

        return $companyId;
    }

    public function index()
    {
        $templates = WhatsAppTemplate::where('company_id', $this->companyId())
            ->where('status','active')
            ->orderBy('name')
            ->get();

        $mappings  = WhatsAppTemplateMapping::where('company_id', $this->companyId())
            ->orderBy('event_key')
            ->get();

        $eventKeys = [
            'lead.created.meta',
            'lead.followup.20m',
            'lead.reply.suggest_time',
            'schedule.confirmed',
            'schedule.reminder',
            'job.done.feedback',
            'feedback.positive.review',
        ];

        return view('admin.whatsapp.mappings.index', compact('templates','mappings','eventKeys'));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'event_key'   => 'required|string|max:80',
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('whatsapp_templates', 'id')->where('company_id', $this->companyId()),
            ],
        ]);

        WhatsAppTemplateMapping::updateOrCreate(
            ['company_id'=>$this->companyId(),'event_key'=>$data['event_key']],
            ['template_id'=>$data['template_id'],'is_active'=>true]
        );

        return back()->with('success','Mapping saved.');
    }

    public function update(Request $r, WhatsAppTemplateMapping $mapping)
    {
        $this->ensureMappingBelongsToCompany($mapping);

        $mapping->update($r->validate([
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('whatsapp_templates', 'id')->where('company_id', $this->companyId()),
            ],
            'is_active'   => 'nullable|boolean',
        ]));
        return back()->with('success','Mapping updated.');
    }

    public function toggle(WhatsAppTemplateMapping $mapping)
    {
        $this->ensureMappingBelongsToCompany($mapping);

        $mapping->is_active = ! $mapping->is_active;
        $mapping->save();
        return back()->with('success','Mapping toggled.');
    }

    private function ensureMappingBelongsToCompany(WhatsAppTemplateMapping $mapping): void
    {
        abort_unless((int) $mapping->company_id === $this->companyId(), 404);
    }
}