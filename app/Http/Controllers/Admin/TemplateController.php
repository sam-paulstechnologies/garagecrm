<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsApp\WhatsAppTemplate;

class TemplateController extends Controller
{
    public function preview(WhatsAppTemplate $template)
    {
        // If you store variables/buttons as JSON, they’re already cast in your model
        return view('admin.templates.preview', [
            'tpl' => $template,
        ]);
    }
}
