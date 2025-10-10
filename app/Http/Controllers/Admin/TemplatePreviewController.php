<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsApp\WhatsAppTemplate;
use Inertia\Inertia;

class TemplatePreviewController extends Controller
{
    public function show(WhatsAppTemplate $template)
    {
        return Inertia::render('Templates/Preview', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'provider' => $template->provider,
                'provider_template' => $template->provider_template,
                'language' => $template->language,
                'category' => $template->category,
                'header' => $template->header,
                'body' => $template->body,
                'footer' => $template->footer,
                'buttons' => $template->buttons,
            ],
            'payload' => $template->variables ?? [],
        ]);
    }
}
