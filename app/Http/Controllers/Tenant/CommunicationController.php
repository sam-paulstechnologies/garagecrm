<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Client;
use App\Models\Template;

class CommunicationController extends Controller
{
    public function create()
    {
        $companyId = Auth::user()->company_id;

        $clients = Client::where('company_id', $companyId)->get();
        $templates = Template::all(); // Templates are shared across companies

        return view('communications.send', compact('clients', 'templates'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'template_id'    => 'required|exists:templates,id',
            'schedule'       => 'required|in:now,schedule',
            'schedule_time'  => 'nullable|date|after:now',
        ]);

        $client = Client::findOrFail($request->client_id);
        if ($client->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized client access.');
        }

        // Sample logic placeholder for sending or scheduling
        // Implement your actual email/SMS/WhatsApp dispatch here
        if ($request->schedule === 'now') {
            // Send immediately
            // e.g., CommunicationService::sendNow($client, $template)
        } else {
            // Schedule send
            // e.g., CommunicationService::schedule($client, $template, $request->schedule_time)
        }

        return redirect()->route('communications.create')
            ->with('success', 'Communication has been sent or scheduled successfully.');
    }
}
