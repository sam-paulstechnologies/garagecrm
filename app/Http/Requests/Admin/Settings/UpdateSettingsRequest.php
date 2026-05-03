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
            'company.name'    => ['required', 'string', 'max:190'],
            'company.email'   => ['nullable', 'email'],
            'company.phone'   => ['nullable', 'string', 'max:50'],
            'company.address' => ['nullable', 'string', 'max:255'],

            /*
            |--------------------------------------------------------------------------
            | Meta
            |--------------------------------------------------------------------------
            */
            'meta.access_token' => ['nullable', 'string'],
            'meta.page_id'      => ['nullable', 'string'],
            'meta.app_id'       => ['nullable', 'string'],
            'meta.form_ids'     => ['nullable', 'string'], // CSV or JSON handled later

            /*
            |--------------------------------------------------------------------------
            | Twilio
            |--------------------------------------------------------------------------
            */
            'twilio.account_sid'   => ['nullable', 'string'],
            'twilio.auth_token'    => ['nullable', 'string'],
            'twilio.whatsapp_from' => ['nullable', 'string'],

            /*
            |--------------------------------------------------------------------------
            | System
            |--------------------------------------------------------------------------
            */
            'system.timezone'             => ['nullable', 'string'],
            'system.default_country_code' => ['nullable', 'string'],
            'system.notification_email'   => ['nullable', 'email'],

            /*
            |--------------------------------------------------------------------------
            | WhatsApp / Garage Extras  ✅ THIS WAS MISSING
            |--------------------------------------------------------------------------
            */
            'manager_whatsapp'      => ['nullable', 'string', 'max:20'],
            'google_review_link'    => ['nullable', 'url', 'max:255'],
            'garage_location_link'  => ['nullable', 'url', 'max:255'],
        ];
    }
}
