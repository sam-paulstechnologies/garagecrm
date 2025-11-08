<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function edit(Request $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;

        $keys = [
            // Policy
            'ai.enabled',
            'ai.confidence_threshold',
            'ai.first_reply',
            'ai.intent.handle',
            'ai.intent.handoff',
            'ai.intent.forbidden',
            'ai.forbidden_topics',
            'ai.policy_reply',
            // Business profile
            'business.manager_phone',
            'business.work_hours',
            'business.holidays',
            'business.location',
            'business.location_coords',
            // Escalations
            'escalation.low_confidence',
            'escalation.sentiment',
            'escalation.timeout_minutes',
        ];

        $rows = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('key', $keys)
            ->pluck('value', 'key');

        $v = fn($k, $d=null) => isset($rows[$k]) ? $rows[$k] : $d;

        $initial = [
            // policy
            'enabled'              => (bool) ($v('ai.enabled','0') === '1'),
            'confidence_threshold' => is_numeric($v('ai.confidence_threshold')) ? (float)$v('ai.confidence_threshold') : (float)config('ai.confidence_threshold', 0.60),
            'first_reply'          => (bool) ($v('ai.first_reply', env('AI_FIRST_REPLY', false)) ? true : false),
            'intent_handle'        => $v('ai.intent.handle', 'greeting,price,service_info'),
            'intent_handoff'       => $v('ai.intent.handoff', 'booking_change,complex_quote'),
            'intent_forbidden'     => $v('ai.intent.forbidden', 'payments,personal_data'),
            'forbidden_topics'     => $v('ai.forbidden_topics', 'Card details,PIN,OTP'),
            'policy_reply'         => $v('ai.policy_reply', "I can’t help with that. I’ll connect you to our manager."),
            // business
            'manager_phone'        => $v('business.manager_phone', ''),
            'work_hours'           => $v('business.work_hours', 'Mon–Sat 09:00–18:00'),
            'holidays'             => $v('business.holidays', '[]'),
            'location'             => $v('business.location', ''),
            'location_coords'      => $v('business.location_coords', ''),
            // escalations
            'esc_low_confidence'   => (bool) ($v('escalation.low_confidence','1') === '1'),
            'esc_sentiment'        => (bool) ($v('escalation.sentiment','1') === '1'),
            'esc_timeout_minutes'  => (int)  ($v('escalation.timeout_minutes','120')),
        ];

        // make holidays safe JSON for UI
        json_decode($initial['holidays']);
        if (json_last_error() !== JSON_ERROR_NONE) $initial['holidays'] = '[]';

        return inertia('Admin/AiSettings', ['initial' => $initial]);
    }

    public function update(Request $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;

        $data = $request->validate([
            // policy
            'enabled'              => ['required','boolean'],
            'confidence_threshold' => ['required','numeric','min:0','max:1'],
            'first_reply'          => ['required','boolean'],
            'intent_handle'        => ['nullable','string'],
            'intent_handoff'       => ['nullable','string'],
            'intent_forbidden'     => ['nullable','string'],
            'forbidden_topics'     => ['nullable','string'],
            'policy_reply'         => ['nullable','string','max:1000'],
            // business
            'manager_phone'        => ['nullable','string','max:32'],
            'work_hours'           => ['nullable','string','max:190'],
            'holidays'             => ['nullable','string'],
            'location'             => ['nullable','string','max:255'],
            'location_coords'      => ['nullable','string','max:64'],
            // escalations
            'esc_low_confidence'   => ['required','boolean'],
            'esc_sentiment'        => ['required','boolean'],
            'esc_timeout_minutes'  => ['required','integer','min:5','max:10080'],
        ]);

        // validate holidays JSON
        if (!empty($data['holidays'])) {
            $decoded = json_decode($data['holidays'], true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return back()->withErrors(['holidays' => 'Holidays must be a JSON array like ["2025-12-25"].'])->withInput();
            }
        } else {
            $data['holidays'] = '[]';
        }

        $pairs = [
            ['key'=>'ai.enabled',              'value'=>$data['enabled'] ? '1' : '0'],
            ['key'=>'ai.confidence_threshold', 'value'=> (string) $data['confidence_threshold']],
            ['key'=>'ai.first_reply',          'value'=>$data['first_reply'] ? '1' : '0'],
            ['key'=>'ai.intent.handle',        'value'=> (string) ($data['intent_handle'] ?? '')],
            ['key'=>'ai.intent.handoff',       'value'=> (string) ($data['intent_handoff'] ?? '')],
            ['key'=>'ai.intent.forbidden',     'value'=> (string) ($data['intent_forbidden'] ?? '')],
            ['key'=>'ai.forbidden_topics',     'value'=> (string) ($data['forbidden_topics'] ?? '')],
            ['key'=>'ai.policy_reply',         'value'=> (string) ($data['policy_reply'] ?? '')],
            ['key'=>'business.manager_phone',  'value'=> (string) ($data['manager_phone'] ?? '')],
            ['key'=>'business.work_hours',     'value'=> (string) ($data['work_hours'] ?? '')],
            ['key'=>'business.holidays',       'value'=> (string) ($data['holidays'] ?? '[]')],
            ['key'=>'business.location',       'value'=> (string) ($data['location'] ?? '')],
            ['key'=>'business.location_coords','value'=> (string) ($data['location_coords'] ?? '')],
            ['key'=>'escalation.low_confidence',  'value'=>$data['esc_low_confidence'] ? '1' : '0'],
            ['key'=>'escalation.sentiment',       'value'=>$data['esc_sentiment'] ? '1' : '0'],
            ['key'=>'escalation.timeout_minutes', 'value'=> (string) $data['esc_timeout_minutes']],
        ];

        DB::transaction(function () use ($companyId, $pairs, $request) {
            $now  = now();
            $user = $request->user()?->id;

            foreach ($pairs as $p) {
                $exists = DB::table('company_settings')
                    ->where('company_id', $companyId)
                    ->where('key', $p['key'])
                    ->exists();

                if ($exists) {
                    DB::table('company_settings')
                      ->where('company_id', $companyId)
                      ->where('key', $p['key'])
                      ->update([
                          'value' => $p['value'],
                          'group' => 'ai',
                          'updated_by' => $user,
                          'updated_at' => $now,
                      ]);
                } else {
                    DB::table('company_settings')->insert([
                        'company_id'  => $companyId,
                        'key'         => $p['key'],
                        'value'       => $p['value'],
                        'group'       => 'ai',
                        'is_encrypted'=> 0,
                        'updated_by'  => $user,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ]);
                }
            }
        });

        return back()->with('success', 'AI Control Center settings saved.');
    }
}
