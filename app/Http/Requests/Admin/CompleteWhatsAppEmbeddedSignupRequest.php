<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteWhatsAppEmbeddedSignupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && filled($this->user()->company_id);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:4096'],
            'state' => ['required', 'string', 'size:64'],
            'session_event' => [
                'required',
                'string',
                Rule::in(['FINISH', 'FINISH_ONLY_WABA', 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING']),
            ],
            'business_id' => ['nullable', 'string', 'max:100', 'regex:/^[0-9]+$/'],
            'waba_id' => ['nullable', 'string', 'max:100', 'regex:/^[0-9]+$/'],
            'phone_number_id' => ['nullable', 'string', 'max:100', 'regex:/^[0-9]+$/'],
        ];
    }
}
