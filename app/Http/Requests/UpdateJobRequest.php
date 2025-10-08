<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'client_id'          => ['required','exists:clients,id'],
            'booking_id'         => ['nullable','integer'],
            'description'        => ['required','string','max:1000'],
            'start_time'         => ['nullable','date'],
            'end_time'           => ['nullable','date','after_or_equal:start_time'],
            'work_summary'       => ['nullable','string'],
            'issues_found'       => ['nullable','string'],
            'parts_used'         => ['nullable','string'],
            'total_time_minutes' => ['nullable','integer','min:0'],
            'status'             => ['required','in:pending,in_progress,completed'],
            'assigned_to'        => ['nullable','exists:users,id'],
        ];
    }
}
