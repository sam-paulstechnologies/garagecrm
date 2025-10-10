<?php

namespace App\Http\Requests\Admin\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Gate/Policy can be used here later
        return true;
    }

    protected function prepareForValidation(): void
    {
        // buttons may arrive as JSON string from hidden field
        $buttons = $this->input('buttons');

        if (is_string($buttons)) {
            $decoded = json_decode($buttons, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['buttons' => $decoded]);
            } else {
                // if bad JSON, force empty so validation can still pass with nullable
                $this->merge(['buttons' => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:120'],
            'provider_template'  => ['required', 'string', 'max:160'],
            'language'           => ['required', 'string', 'max:20'],
            'category'           => ['nullable', 'string', 'max:40'],
            'header'             => ['nullable', 'string'],
            'body'               => ['required', 'string'],
            'footer'             => ['nullable', 'string'],
            'status'             => ['required', Rule::in(['active','draft','archived'])],

            // Buttons: up to 3 (Meta limit for interactive template buttons)
            'buttons'            => ['nullable', 'array', 'max:3'],
            'buttons.*.type'     => ['required_with:buttons', Rule::in(['quick_reply','url','phone'])],
            'buttons.*.text'     => ['required_with:buttons', 'string', 'max:25'],
            'buttons.*.url'      => ['required_if:buttons.*.type,url', 'nullable', 'string', 'max:200'],
            'buttons.*.phone'    => ['required_if:buttons.*.type,phone', 'nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'provider_template' => 'provider template',
        ];
    }

    public function messages(): array
    {
        return [
            'buttons.*.type.required_with' => 'Each button needs a type.',
            'buttons.*.text.required_with' => 'Each button needs text.',
            'buttons.*.url.required_if'    => 'URL is required for URL buttons.',
            'buttons.*.phone.required_if'  => 'Phone is required for phone buttons.',
        ];
    }
}
