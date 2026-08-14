<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Company
            |--------------------------------------------------------------------------
            */
            'company.name' => ['required', 'string', 'max:190'],
            'company.email' => ['nullable', 'email'],
            'company.phone' => ['nullable', 'string', 'max:50'],
            'company.address' => ['nullable', 'string', 'max:255'],

            /*
            |--------------------------------------------------------------------------
            | Meta
            |--------------------------------------------------------------------------
            */
            'meta.access_token' => ['prohibited'],
            'meta.page_id' => ['prohibited'],
            'meta.app_id' => ['prohibited'],
            'meta.form_id' => ['prohibited'],
            'meta.form_ids' => ['prohibited'],

            /*
            |--------------------------------------------------------------------------
            | Twilio
            |--------------------------------------------------------------------------
            */
            'twilio.account_sid' => ['prohibited'],
            'twilio.auth_token' => ['prohibited'],
            'twilio.whatsapp_from' => ['prohibited'],

            /*
            |--------------------------------------------------------------------------
            | System
            |--------------------------------------------------------------------------
            */
            'system.timezone' => ['nullable', 'string'],
            'system.default_country_code' => ['nullable', 'string'],
            'system.notification_email' => ['nullable', 'email'],

            /*
            |--------------------------------------------------------------------------
            | WhatsApp / Garage Extras  ✅ THIS WAS MISSING
            |--------------------------------------------------------------------------
            */
            'manager_whatsapp' => ['nullable', 'string', 'max:20'],
            'google_review_link' => ['nullable', 'url', 'max:255'],
            'garage_location_link' => ['nullable', 'url', 'max:255'],
        ];
    }
}
