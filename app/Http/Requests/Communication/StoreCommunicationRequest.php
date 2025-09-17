<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommunicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id'          => ['required','exists:clients,id'],
            'lead_id'            => ['nullable','exists:leads,id'],
            'opportunity_id'     => ['nullable','exists:opportunities,id'],
            'booking_id'         => ['nullable','exists:bookings,id'],
            'company_id'         => ['required','integer'],
            'type'               => ['required','in:call,email,whatsapp'],
            'content'            => ['nullable','string'],
            'communication_date' => ['nullable','date'],
            'follow_up_required' => ['nullable','boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['follow_up_required' => $this->boolean('follow_up_required')]);
    }
}
