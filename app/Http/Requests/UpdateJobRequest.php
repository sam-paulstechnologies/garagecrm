<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $companyId = (int) (auth()->user()->company_id ?? auth()->user()->company->id ?? 0);

        return [
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],

            'booking_id' => [
                'nullable',
                'integer',
                Rule::exists('bookings', 'id')->where('company_id', $companyId),
            ],

            'description' => ['required', 'string', 'max:1000'],

            'start_time' => ['nullable', 'date'],

            /*
            |--------------------------------------------------------------------------
            | Removed from UI but kept nullable for compatibility
            |--------------------------------------------------------------------------
            */
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],

            'work_summary' => ['nullable', 'string'],
            'issues_found' => ['nullable', 'string'],
            'parts_used' => ['nullable', 'string'],

            /*
            |--------------------------------------------------------------------------
            | Removed from UI but kept nullable for compatibility
            |--------------------------------------------------------------------------
            */
            'total_time_minutes' => ['nullable', 'integer', 'min:0'],

            'status' => ['required', 'in:pending,in_progress,completed'],

            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],

            /*
            |--------------------------------------------------------------------------
            | Invoice fields required only when closing job
            |--------------------------------------------------------------------------
            */
            'invoice_number' => [
                Rule::requiredIf(fn () => $this->input('status') === 'completed'),
                'nullable',
                'string',
                'max:100',
            ],

            'invoice_amount' => [
                Rule::requiredIf(fn () => $this->input('status') === 'completed'),
                'nullable',
                'numeric',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_number.required' => 'Invoice number is required to close the job.',
            'invoice_amount.required' => 'Invoice amount is required to close the job.',
            'invoice_amount.min' => 'Invoice amount must be greater than 0.',
        ];
    }
}