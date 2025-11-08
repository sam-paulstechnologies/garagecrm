<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user(); }

    public function rules(): array
    {
        return [
            'enabled'              => ['required','boolean'],
            'confidence_threshold' => ['required','numeric','between:0,1'],
            'first_reply'          => ['required','boolean'],
            'intent_handle'        => ['nullable','string','max:1000'],
            'intent_handoff'       => ['nullable','string','max:1000'],
            'intent_forbidden'     => ['nullable','string','max:1000'],
            'policy_text'          => ['nullable','string','max:2000'],
        ];
    }
}
