<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DemoRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'garage_name' => ['required', 'string', 'max:160'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:150'],
            'current_management_system' => ['nullable', 'string', 'max:160'],
            'monthly_leads' => ['nullable', 'string', 'in:under_50,50_100,101_250,250_plus,not_sure'],
            'main_challenge' => ['nullable', 'string', 'max:1200'],
            // Retained for older forms and integrations that still post these fields.
            'monthly_cars' => ['nullable', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:1200'],
        ]);

        $data['source'] = 'public_website';
        $data['captured_at'] = now()->toIso8601String();

        Storage::disk('local')->append(
            'sayaraforce/demo-enquiries.jsonl',
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        return redirect()
            ->route('public.demo.thank-you')
            ->with('success', 'Your audit request has been received.');
    }
}
