<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WhatsApp\StoreTemplateRequest;
use App\Http\Requests\Admin\WhatsApp\UpdateTemplateRequest;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class WhatsAppTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        // $this->middleware('can:manage-whatsapp'); // enable if you have policies
    }

    public function index(Request $request)
    {
        $companyId = $request->user()->company_id ?? $request->user()->company->id ?? null;

        $q = WhatsAppTemplate::query()
            ->where('company_id', $companyId)
            ->latest('updated_at');

        if ($s = trim($request->get('q', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                  ->orWhere('language', 'like', "%{$s}%")
                  ->orWhere('category', 'like', "%{$s}%");
            });
        }

        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }

        if ($cat = $request->get('category')) {
            $q->where('category', $cat);
        }

        $templates = $q->paginate(20)->withQueryString();

        $categories = WhatsAppTemplate::query()
            ->where('company_id', $companyId)
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.whatsapp.templates.index', compact('templates', 'categories'));
    }

    public function create()
    {
        return view('admin.whatsapp.templates.create');
    }

    public function show(Request $request, WhatsAppTemplate $template)
    {
        $this->ensureTemplateBelongsToCompany($request, $template);

        return view('admin.whatsapp.templates.show', compact('template'));
    }

    public function store(StoreTemplateRequest $request)
    {
        $companyId = $request->user()->company_id ?? $request->user()->company->id ?? null;

        // buttons may arrive as JSON or array
        $buttons = $request->input('buttons', []);
        if (is_string($buttons)) {
            $decoded = json_decode($buttons, true);
            $buttons = is_array($decoded) ? $decoded : [];
        }

        $tpl = new WhatsAppTemplate($request->validated() + [
            'company_id' => $companyId,
            'buttons'    => $buttons,
            'provider'   => $request->input('provider', config('services.whatsapp.provider', 'twilio')),
            'status'     => $request->input('status', 'active'),
            'language'   => $request->input('language', 'en'),
        ]);

        $tpl->company_id = $companyId;
        $tpl->variables = $tpl->extractVariables();
        $tpl->save();

        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', 'Template created.');
    }

    public function edit(Request $request, WhatsAppTemplate $template)
    {
        $this->ensureTemplateBelongsToCompany($request, $template);

        return view('admin.whatsapp.templates.edit', compact('template'));
    }

    public function update(UpdateTemplateRequest $request, WhatsAppTemplate $template)
    {
        $this->ensureTemplateBelongsToCompany($request, $template);

        $data = $request->validated();
        unset($data['company_id']);

        $buttons = $request->input('buttons');
        if (is_string($buttons)) {
            $decoded = json_decode($buttons, true);
            $buttons = is_array($decoded) ? $decoded : [];
        }
        $data['buttons']  = $buttons ?: [];
        $data['provider'] = $data['provider'] ?? $template->provider ?? config('services.whatsapp.provider', 'twilio');
        $data['language'] = $data['language'] ?? $template->language ?? 'en';
        $data['status']   = $data['status']   ?? $template->status   ?? 'active';

        $template->fill($data);
        $template->company_id = $request->user()->company_id ?? $request->user()->company->id ?? null;
        $template->variables = $template->extractVariables();
        $template->save();

        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(Request $request, WhatsAppTemplate $template)
    {
        $this->ensureTemplateBelongsToCompany($request, $template);

        $template->delete();
        return redirect()->route('admin.whatsapp.templates.index')
            ->with('success', 'Template deleted.');
    }

    /** Live preview: returns rendered header/body/footer as JSON with demo vars. */
    public function preview(Request $request, WhatsAppTemplate $template)
    {
        $this->ensureTemplateBelongsToCompany($request, $template);

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

    /**
     * Test-send a template to a phone number (+E164).
     * Uses the unified WhatsAppService which:
     *  - picks provider per-tenant if configured
     *  - logs to whatsapp_messages with the correct schema
     */
    public function testSend(Request $request, WhatsAppTemplate $template, WhatsAppService $wa)
    {
        $this->ensureTemplateBelongsToCompany($request, $template);

        $request->validate([
            'to_phone'   => ['required','regex:/^\+\d{8,20}$/'],
            'lead_id'    => ['nullable','integer'],
        ]);

        $companyId = $request->user()->company_id ?? $request->user()->company->id ?? null;

        // Build ordered params based on extracted variables
        $params = [];
        foreach ($template->variables ?? [] as $v) {
            $params[] = (string) $request->input("vars.$v", $v);
        }

        // Optional links (as array or JSON)
        $links = $request->input('links', []);
        if (is_string($links)) {
            $decoded = json_decode($links, true);
            $links = is_array($decoded) ? $decoded : [];
        }

        $context = [
            'company_id' => $companyId,
            'lead_id'    => $request->input('lead_id'),
        ];

        // IMPORTANT:
        // WhatsAppService expects the provider template name.
        // For Meta: it's exactly the provider template name.
        // For Twilio sandbox: it will fall back to a text library keyed by template name.
        // We’ll prefer provider_template if present; else fallback to name.
        $providerTemplateName = $template->provider_template ?: $template->name;

        $res = $wa->sendTemplate(
            toE164:       $request->input('to_phone'),
            templateName: $providerTemplateName,
            params:       $params,
            links:        $links,
            context:      $context
        );

        $failed = is_array($res) && isset($res['error']);

        return back()->with($failed ? 'error' : 'success',
            $failed ? ('Send failed: '.$res['error']) : 'Test message queued/sent.'
        );
    }

    private function ensureTemplateBelongsToCompany(Request $request, WhatsAppTemplate $template): void
    {
        $companyId = $request->user()->company_id ?? $request->user()->company->id ?? null;

        abort_unless((int) $template->company_id === (int) $companyId, 404);
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