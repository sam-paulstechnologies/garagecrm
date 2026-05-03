<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\LeadSource;

class WebsiteLeadFormController extends Controller
{
    public function show(string $token)
    {
        $source = LeadSource::where('form_token', $token)
            ->where('type', 'website')
            ->where('status', 'active')
            ->firstOrFail();

        return view('public.website_form', compact('source'));
    }
}
