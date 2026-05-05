<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommunicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $companyId = (int) (auth()->user()->company_id ?? auth()->user()->company->id ?? 0);

        return [
            'client_id'          => [
                'sometimes',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],
            'lead_id'            => [
                'nullable',
                Rule::exists('leads', 'id')->where('company_id', $companyId),
            ],
            'opportunity_id'     => [
                'nullable',
                Rule::exists('opportunities', 'id')->where('company_id', $companyId),
            ],
            'booking_id'         => [
                'nullable',
                Rule::exists('bookings', 'id')->where('company_id', $companyId),
            ],
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