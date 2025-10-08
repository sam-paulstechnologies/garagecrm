<?php

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins (based on your User::ROLES)
        return auth()->check() && auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            // company block
            'company.name'    => ['required','string','max:190'],
            'company.email'   => ['nullable','email'],
            'company.phone'   => ['nullable','string','max:50'],
            'company.address' => ['nullable','string'],

            // meta
            'meta.access_token' => ['nullable','string'],
            'meta.page_id'      => ['nullable','string'],
            'meta.app_id'       => ['nullable','string'],
            'meta.form_ids'     => ['nullable','string'], // accept CSV; we’ll parse

            // twilio
            'twilio.account_sid'   => ['nullable','string'],
            'twilio.auth_token'    => ['nullable','string'],
            'twilio.whatsapp_from' => ['nullable','string'],

            // system
            'system.timezone'             => ['nullable','string'],
            'system.default_country_code' => ['nullable','string'],
            'system.notification_email'   => ['nullable','email'],
        ];
    }
}
