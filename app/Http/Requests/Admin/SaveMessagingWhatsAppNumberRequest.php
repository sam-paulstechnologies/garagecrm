<?php

namespace App\Http\Requests\Admin;

use App\Messaging\Enums\ConnectionMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMessagingWhatsAppNumberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && filled($this->user()?->company_id);
    }

    public function rules(): array
    {
        return [
            'country_code' => ['nullable', 'string', 'max:8', 'regex:/^\+?[0-9\s()-]+$/'],
            'phone_number' => ['required', 'string', 'max:32'],
            'label' => ['nullable', 'string', 'max:80'],
            'connection_mode' => ['required', Rule::enum(ConnectionMode::class)],
        ];
    }
}
