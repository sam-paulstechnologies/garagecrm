<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAiSettingsRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiController extends Controller
{
    public function edit(Request $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;

        $get = fn($k, $d=null) => DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('key', $k)
            ->value('value') ?? $d;

        $data = [
            'enabled'              => (int) ($get('ai.enabled', '1')) ? true : false,
            'confidence_threshold' => (float) ($get('ai.confidence_threshold', '0.60')),
            'first_reply'          => (int) ($get('ai.first_reply', '0')) ? true : false,
            'intent_handle'        => (string) ($get('ai.intent.handle', 'greeting,price,service_info')),
            'intent_handoff'       => (string) ($get('ai.intent.handoff', 'booking_change,complex_quote')),
            'intent_forbidden'     => (string) ($get('ai.intent.forbidden', 'pickup_drop,pricing_commit')),
            'policy_text'          => (string) ($get('ai.policy_text', "Sorry, I’m not allowed to answer that here. I’ll connect you to a manager if needed.")),
        ];

        // Blade view version (no Inertia)
        return view('admin.ai.edit', ['initial' => $data]);
    }

    public function update(UpdateAiSettingsRequest $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;
        $p = $request->validated();

        $rows = [
            ['company_id'=>$companyId,'key'=>'ai.enabled','value'=>$p['enabled'] ? '1' : '0'],
            ['company_id'=>$companyId,'key'=>'ai.confidence_threshold','value'=>number_format((float)$p['confidence_threshold'], 2, '.', '')],
            ['company_id'=>$companyId,'key'=>'ai.first_reply','value'=>$p['first_reply'] ? '1' : '0'],
            ['company_id'=>$companyId,'key'=>'ai.intent.handle','value'=>trim($p['intent_handle'] ?? '')],
            ['company_id'=>$companyId,'key'=>'ai.intent.handoff','value'=>trim($p['intent_handoff'] ?? '')],
            ['company_id'=>$companyId,'key'=>'ai.intent.forbidden','value'=>trim($p['intent_forbidden'] ?? '')],
            ['company_id'=>$companyId,'key'=>'ai.policy_text','value'=>trim($p['policy_text'] ?? '')],
        ];

        DB::transaction(function() use ($rows) {
            foreach ($rows as $r) {
                DB::table('company_settings')->updateOrInsert(
                    ['company_id'=>$r['company_id'], 'key'=>$r['key']],
                    ['value'=>$r['value'], 'updated_at'=>now(), 'created_at'=>now()]
                );
            }
        });

        return back()->with('success', 'AI settings saved.');
    }
}
