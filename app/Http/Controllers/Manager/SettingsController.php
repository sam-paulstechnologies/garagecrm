<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Manager-safe settings only
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | Do not expose WhatsApp API credentials, Meta settings, Twilio settings,
        | payment gateway settings, AI policy settings, billing, subscription,
        | company-wide automation rules, or role permissions here.
        |--------------------------------------------------------------------------
        */

        $settingsCards = [
            [
                'title' => 'Business Hours',
                'description' => 'View or update garage operating hours used by the manager team.',
                'status' => 'Coming soon',
                'tone' => 'blue',
            ],
            [
                'title' => 'Service Availability',
                'description' => 'Manage operational availability for service booking and customer scheduling.',
                'status' => 'Coming soon',
                'tone' => 'orange',
            ],
            [
                'title' => 'Notifications',
                'description' => 'Control manager-facing alerts for leads, bookings, jobs, and invoices.',
                'status' => 'Coming soon',
                'tone' => 'green',
            ],
            [
                'title' => 'Quick Replies',
                'description' => 'Prepare safe WhatsApp reply snippets for common customer conversations.',
                'status' => 'Coming soon',
                'tone' => 'purple',
            ],
        ];

        $restrictedSettings = [
            'WhatsApp API credentials',
            'Meta webhook configuration',
            'Twilio credentials',
            'Payment gateway settings',
            'Billing and subscription',
            'AI policy configuration',
            'Role and permission management',
            'Company-wide automation rules',
            'Invoice numbering configuration',
            'Template approval / template mapping',
        ];

        return view('manager.settings.index', [
            'user' => $user,
            'settingsCards' => $settingsCards,
            'restrictedSettings' => $restrictedSettings,
        ]);
    }
}