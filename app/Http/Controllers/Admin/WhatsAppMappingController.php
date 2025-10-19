<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\WhatsApp\WhatsAppTemplateMapping;
use Illuminate\Http\Request;

class WhatsAppMappingController extends Controller
{
    protected function companyId(): int { return (int)(auth()->user()->company_id ?? 1); }

    public function index()
    {
        $templates = WhatsAppTemplate::where('status','active')->orderBy('name')->get();

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
            'template_id' => 'nullable|integer|exists:whatsapp_templates,id',
        ]);

        WhatsAppTemplateMapping::updateOrCreate(
            ['company_id'=>$this->companyId(),'event_key'=>$data['event_key']],
            ['template_id'=>$data['template_id'],'is_active'=>true]
        );

        return back()->with('success','Mapping saved.');
    }

    public function update(Request $r, WhatsAppTemplateMapping $mapping)
    {
        $mapping->update($r->validate([
            'template_id' => 'nullable|integer|exists:whatsapp_templates,id',
            'is_active'   => 'nullable|boolean',
        ]));
        return back()->with('success','Mapping updated.');
    }

    public function toggle(WhatsAppTemplateMapping $mapping)
    {
        $mapping->is_active = ! $mapping->is_active;
        $mapping->save();
        return back()->with('success','Mapping toggled.');
    }
}
