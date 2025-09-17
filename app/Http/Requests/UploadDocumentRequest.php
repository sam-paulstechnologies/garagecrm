<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route/middleware should guard access; allow here.
        return true;
    }

    public function rules(): array
    {
        $maxMb = (int) config('document_ingest.max_size_mb', 20);
        $mimes = (string) config('document_ingest.allowed_mimes', 'pdf,jpg,jpeg,png');

        return [
            'type' => ['required', 'in:invoice,job_card,other'],
            'file' => ['required', 'file', "mimes:{$mimes}", 'max:' . ($maxMb * 1024)], // max in KB
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'     => 'Type must be invoice, job_card, or other.',
            'file.mimes'  => 'Only the following file types are allowed: ' . config('document_ingest.allowed_mimes'),
            'file.max'    => 'The file may not be greater than ' . (int) config('document_ingest.max_size_mb', 20) . ' MB.',
        ];
    }
}
