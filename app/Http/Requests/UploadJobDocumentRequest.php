<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadJobDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'file'        => ['required','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
            'description' => ['nullable','string'],
            // doctype: only 'job_card' for this endpoint (we can extend later)
            'doctype'     => ['nullable','in:job_card'],
        ];
    }

    public function doctype(): string
    {
        return $this->input('doctype', 'job_card');
    }
}
