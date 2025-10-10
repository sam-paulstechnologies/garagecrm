<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WhatsApp\StoreTemplateRequest;
use App\Http\Requests\Admin\WhatsApp\UpdateTemplateRequest;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Services\TwilioWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class WhatsAppTemplateController extends Controller
{
    public function __construct()
    {
        // If you’ve got gates/policies, uncomment:
        // $this->middleware('can:manage-whatsapp');
        $this->middleware(['auth']);
    }

    public function index(Request $request)
    {
        $companyId = $request->user()->company_id ?? $request->user()->company->id ?? null;

        $templates = WhatsAppTemplate::query()
            ->where('company_id', $companyId)
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where(function ($w) use ($request) {
                    $w->where('name', 'like', '%'.$request->q.'%')
                      ->orWhere('provider_template', 'like', '%'.$request->q.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.whatsapp.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.whatsapp.templates.create');
    }

    public function store(StoreTemplateRequest $request)
    {
        $companyId = $request->user()->company_id ?? $request->user()->company->id ?? null;

        $tpl = new WhatsAppTemplate($request->validated() + [
            'company_id' => $companyId,
            'buttons'    => $request->input('buttons', []),
        ]);

        $tpl->variables = $tpl->extractVariables();
        $tpl->save();

        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', 'Template created.');
    }

    public function edit(WhatsAppTemplate $template)
    {
        return view('admin.whatsapp.templates.edit', compact('template'));
    }

    public function update(UpdateTemplateRequest $request, WhatsAppTemplate $template)
    {
        $template->fill($request->validated());
        // buttons come as JSON string or array; normalize to array
        $buttons = $request->input('buttons');
        if (is_string($buttons)) {
            $decoded = json_decode($buttons, true);
            $buttons = is_array($decoded) ? $decoded : [];
        }
        $template->buttons   = $buttons ?: [];
        $template->variables = $template->extractVariables();
        $template->save();

        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(WhatsAppTemplate $template)
    {
        $template->delete();
        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', 'Template deleted.');
    }

    /** Live preview: returns rendered header/body/footer as JSON (demo vars). */
    public function preview(Request $request, WhatsAppTemplate $template)
    {
        $demo = [];
        foreach ($template->variables ?? [] as $v) {
            $demo[$v] = $request->input("vars.$v", strtoupper($v));
        }

        $render = fn (?string $text) => $this->renderVars($text, $demo);

        return response()->json([
            'header' => $render($template->header),
            'body'   => $render($template->body),
            'footer' => $render($template->footer),
        ]);
    }

    /** Test-send a template to a phone number (+E164). */
    public function testSend(Request $request, WhatsAppTemplate $template, TwilioWhatsApp $wa)
{
    $request->validate([
        'to_phone' => ['required','regex:/^\+\d{8,20}$/'],
    ]);

    // Collect placeholders from inputs, default to var name.
    $vars = [];
    foreach ($template->variables ?? [] as $v) {
        $vars[$v] = (string) $request->input("vars.$v", $v);
    }

    // Twilio wants "whatsapp:+E164"
    $to = 'whatsapp:' . $request->to_phone;

    $res = $wa->sendTemplate(
        $to,
        $template->provider_template,        // provider template name (string)
        $vars,
        ['language' => $template->language]
    );

    $ok    = (bool) \Illuminate\Support\Arr::get($res, 'ok', false);
    $error = \Illuminate\Support\Arr::get($res, 'error');

    // Persist using your existing columns
    \App\Models\WhatsApp\WhatsAppMessage::create([
        'provider'     => 'twilio',
        'direction'    => 'outbound',
        'to_number'    => $request->to_phone,
        'from_number'  => ltrim((string) config('services.twilio.whatsapp_from'), 'whatsapp:'),
        'template'     => $template->provider_template, // store the template name
        'payload'      => [
            'placeholders' => $vars,
            'language'     => $template->language,
            // Include raw response if you like:
            'provider_res' => $ok ? ['ok' => true] : ['ok'=>false, 'error'=>$error],
        ],
        'status'       => $ok ? 'queued' : 'failed',
        'error_code'   => $ok ? null : 'provider_error',
        'error_message'=> $ok ? null : (is_string($error) ? $error : json_encode($error)),
        // Optional: relate to entities if you have them available here
        // 'lead_id'       => $someLeadId ?? null,
        // 'opportunity_id'=> null,
        // 'job_id'        => null,
    ]);

    return back()->with($ok ? 'success' : 'error',
        $ok ? 'Test message queued.' : ('Failed to queue: '.($error ?: 'unknown'))
    );
}


    private function renderVars(?string $text, array $vars): string
    {
        if (!$text) return '';
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function ($m) use ($vars) {
            $key = $m[1];
            return (string) Arr::get($vars, $key, $key);
        }, $text);
    }
}
