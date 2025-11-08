<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBusinessProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessProfileController extends Controller
{
    public function edit(Request $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;

        $get = fn($k, $d=null) => DB::table('company_settings')
            ->where('company_id', $companyId)
            ->where('key', $k)
            ->value('value') ?? $d;

        $data = [
            // keep both keys in sync; prefer business.manager_phone
            'manager_phone'          => (string) $get('business.manager_phone', $get('whatsapp.manager_number', '')),
            'location'               => (string) $get('business.location', ''),
            'work_hours'             => (string) $get('business.work_hours', 'Mon–Sat 09:00–18:00'),
            'holidays'               => (string) ($get('business.holidays', '[]') ?: '[]'),

            // aligned escalation keys (match AiSettingController)
            'esc_low_confidence'     => (bool) ((int) ($get('escalation.low_confidence', '1'))),
            'esc_sentiment'          => (bool) ((int) ($get('escalation.sentiment', '1'))),
            'esc_timeout_minutes'    => (int)  ($get('escalation.timeout_minutes', '120')),
        ];

        // ensure holidays string is valid JSON array for the UI
        json_decode($data['holidays']);
        if (json_last_error() !== JSON_ERROR_NONE) $data['holidays'] = '[]';

        return view('admin.business.edit', ['initial' => $data]);
    }

    public function update(UpdateBusinessProfileRequest $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;
        $p = $request->validated();

        // guard holidays JSON
        $holidays = $p['holidays'] ?? '[]';
        json_decode($holidays);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $holidays = '[]';
        }

        $rows = [
            ['company_id'=>$companyId,'key'=>'business.manager_phone',      'value'=>trim($p['manager_phone'] ?? '')],
            ['company_id'=>$companyId,'key'=>'whatsapp.manager_number',     'value'=>trim($p['manager_phone'] ?? '')], // keep in sync
            ['company_id'=>$companyId,'key'=>'business.location',           'value'=>trim($p['location'] ?? '')],
            ['company_id'=>$companyId,'key'=>'business.work_hours',         'value'=>trim($p['work_hours'] ?? '')],
            ['company_id'=>$companyId,'key'=>'business.holidays',           'value'=>$holidays],

            // aligned escalation keys
            ['company_id'=>$companyId,'key'=>'escalation.low_confidence',   'value'=>!empty($p['esc_low_confidence']) ? '1' : '0'],
            ['company_id'=>$companyId,'key'=>'escalation.sentiment',        'value'=>!empty($p['esc_sentiment']) ? '1' : '0'],
            ['company_id'=>$companyId,'key'=>'escalation.timeout_minutes',  'value'=>(string) ((int) ($p['esc_timeout_minutes'] ?? 120))],
        ];

        DB::transaction(function() use ($rows) {
            $now = now();
            foreach ($rows as $r) {
                DB::table('company_settings')->updateOrInsert(
                    ['company_id'=>$r['company_id'], 'key'=>$r['key']],
                    ['value'=>$r['value'], 'updated_at'=>$now, 'created_at'=>$now, 'group'=>'ai', 'is_encrypted'=>0]
                );
            }
        });

        return back()->with('success', 'Business profile saved.');
    }
}
