<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'enabled'              => ['required', 'boolean'],
            'confidence_threshold' => ['required', 'numeric', 'between:0,1'],
            'policy_reply'         => ['nullable', 'string', 'max:480'],
            'intent_handle'        => ['nullable', 'string'],
            'intent_handoff'       => ['nullable', 'string'],
            'intent_forbidden'     => ['nullable', 'string'],
            'forbidden_topics'     => ['nullable', 'string'],
        ];
    }
}
