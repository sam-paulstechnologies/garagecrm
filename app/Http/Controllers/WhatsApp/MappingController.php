<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\WhatsApp\WhatsAppTemplateMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MappingController extends Controller
{
    protected function companyId() {
        // replace with your tenant resolution
        return Auth::user()->company_id ?? 1;
    }

    public function index() {
        $templates = WhatsAppTemplate::where('status','active')->orderBy('name')->get();
        $mappings = WhatsAppTemplateMapping::where('company_id', $this->companyId())
            ->orderBy('event_key')->get();
        $eventKeys = [
            'lead.created.meta',
            'lead.followup.20m',
            'lead.reply.suggest_time',  // inbound intent → manager notify
            'schedule.confirmed',
            'schedule.reminder',
            'job.done.feedback',
            'feedback.positive.review'
        ];
        return view('whatsapp.mappings.index', compact('templates','mappings','eventKeys'));
    }

    public function store(Request $r) {
        $data = $r->validate([
            'event_key' => 'required|string|max:80',
            'template_id' => 'nullable|integer|exists:whatsapp_templates,id',
        ]);
        $data['company_id'] = $this->companyId();
        WhatsAppTemplateMapping::updateOrCreate(
            ['company_id'=>$data['company_id'],'event_key'=>$data['event_key']],
            ['template_id'=>$data['template_id'],'is_active'=>true]
        );
        return back()->with('ok','Mapping saved.');
    }

    public function update(Request $r, $id) {
        $m = WhatsAppTemplateMapping::findOrFail($id);
        $m->update($r->validate([
            'template_id' => 'nullable|integer|exists:whatsapp_templates,id',
            'is_active' => 'nullable|boolean'
        ]));
        return back()->with('ok','Updated.');
    }

    public function toggle($id) {
        $m = WhatsAppTemplateMapping::findOrFail($id);
        $m->is_active = ! $m->is_active;
        $m->save();
        return back()->with('ok','Toggled.');
    }
}
