<?php

namespace App\Http\Requests\Admin;

use App\Messaging\Enums\ConnectionMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartMessagingWhatsAppOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && filled($this->user()?->company_id);
    }

    public function rules(): array
    {
        return [
            'connection_mode' => ['required', Rule::enum(ConnectionMode::class)],
            'consent_accepted' => ['required', 'accepted'],
        ];
    }
}
