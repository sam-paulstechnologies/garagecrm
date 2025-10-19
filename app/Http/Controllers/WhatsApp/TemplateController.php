<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\WhatsApp\WhatsAppTemplate;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function index() {
        $templates = WhatsAppTemplate::orderBy('name')->paginate(20);
        return view('whatsapp.templates.index', compact('templates'));
    }

    public function create() {
        return view('whatsapp.templates.form', ['template' => new WhatsAppTemplate()]);
    }

    public function store(Request $r) {
        $data = $r->validate([
            'name' => 'required|string|max:120',
            'provider_template' => 'nullable|string|max:160',
            'language' => 'required|string|max:20',
            'category' => 'nullable|string|max:40',
            'header' => 'nullable|string',
            'body' => 'required|string',
            'footer' => 'nullable|string',
            'buttons' => 'nullable|array',
            'variables' => 'nullable|array',
            'provider' => 'nullable|string|max:40',
            'status' => 'nullable|in:draft,active,archived'
        ]);
        $data['status'] = $data['status'] ?? 'active';
        $tpl = WhatsAppTemplate::create($data);
        return redirect()->route('whatsapp.templates.index')->with('ok','Template created.');
    }

    public function edit($id) {
        $template = WhatsAppTemplate::findOrFail($id);
        return view('whatsapp.templates.form', compact('template'));
    }

    public function update(Request $r, $id) {
        $template = WhatsAppTemplate::findOrFail($id);
        $template->update($r->validate([
            'name' => 'required|string|max:120',
            'provider_template' => 'nullable|string|max:160',
            'language' => 'required|string|max:20',
            'category' => 'nullable|string|max:40',
            'header' => 'nullable|string',
            'body' => 'required|string',
            'footer' => 'nullable|string',
            'buttons' => 'nullable|array',
            'variables' => 'nullable|array',
            'provider' => 'nullable|string|max:40',
            'status' => 'nullable|in:draft,active,archived'
        ]));
        return redirect()->route('whatsapp.templates.index')->with('ok','Updated.');
    }

    public function toggle($id) {
        $t = WhatsAppTemplate::findOrFail($id);
        $t->status = $t->status === 'active' ? 'archived' : 'active';
        $t->save();
        return back()->with('ok','Status toggled.');
    }

    // Live preview: resolve variables (dummy resolution based on sample payload)
    public function preview(Request $r) {
        $body = $r->input('body','');
        $vars = $r->input('vars',[]);
        $preview = preg_replace_callback('/\{\{(\w+)\}\}/', function($m) use ($vars){
            return $vars[$m[1]] ?? '{{'.$m[1].'}}';
        }, $body);
        return response()->json(['preview'=>$preview]);
    }
}
