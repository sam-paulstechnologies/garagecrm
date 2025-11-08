<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessProfileRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user(); }

    public function rules(): array
    {
        return [
            'manager_phone'          => ['nullable','string','max:40'],
            'location'               => ['nullable','string','max:255'],
            'work_hours'             => ['nullable','string','max:255'],
            'holidays'               => ['nullable','string','max:1000'],
            'esc_low_confidence'     => ['required','boolean'],
            'esc_negative_sentiment' => ['required','boolean'],
            'esc_timeout_enabled'    => ['required','boolean'],
            'esc_timeout_minutes'    => ['required','integer','min:10','max:1440'],
        ];
    }
}
