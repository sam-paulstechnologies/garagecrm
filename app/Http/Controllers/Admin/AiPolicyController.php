<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAiPolicyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiPolicyController extends Controller
{
    public function edit(Request $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;

        $get = fn($k, $d=null) => DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('key', $k)
            ->value('value') ?? $d;

        $data = [
            'enabled'              => (bool) ((int) ($get('ai.enabled', '1'))),
            'confidence_threshold' => (string) ($get('ai.confidence_threshold', '0.72')),
            'policy_reply'         => (string) ($get('ai.policy_reply', "I'm not sure I understood that correctly. Our manager will contact you shortly.")),
            'intent_handle'        => (string) ($get('ai.intent.handle', 'greeting,booking,price_quote')),
            'intent_handoff'       => (string) ($get('ai.intent.handoff', 'reschedule,warranty')),
            'intent_forbidden'     => (string) ($get('ai.intent.forbidden', 'pickup_delivery,legal_advice,medical_advice')),
            'forbidden_topics'     => (string) ($get('ai.forbidden_topics', 'pickup,drop,payment link,refund,discount')),
        ];

        return view('admin.ai.policy', ['initial' => $data]);
    }

    public function update(UpdateAiPolicyRequest $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;
        $p = $request->validated();

        $rows = [
            ['key'=>'ai.enabled',              'value'=> !empty($p['enabled']) ? '1' : '0'],
            ['key'=>'ai.confidence_threshold', 'value'=> (string) $p['confidence_threshold']],
            ['key'=>'ai.policy_reply',         'value'=> trim((string) ($p['policy_reply'] ?? ''))],
            ['key'=>'ai.intent.handle',        'value'=> trim((string) ($p['intent_handle'] ?? ''))],
            ['key'=>'ai.intent.handoff',       'value'=> trim((string) ($p['intent_handoff'] ?? ''))],
            ['key'=>'ai.intent.forbidden',     'value'=> trim((string) ($p['intent_forbidden'] ?? ''))],
            ['key'=>'ai.forbidden_topics',     'value'=> trim((string) ($p['forbidden_topics'] ?? ''))],
        ];

        DB::transaction(function() use ($rows, $companyId) {
            $now = now();
            foreach ($rows as $r) {
                DB::table('company_settings')->updateOrInsert(
                    ['company_id'=>$companyId, 'key'=>$r['key']],
                    ['value'=>$r['value'], 'updated_at'=>$now, 'created_at'=>$now, 'group'=>'ai', 'is_encrypted'=>0]
                );
            }
        });

        return back()->with('success', 'AI policy updated.');
    }
}
