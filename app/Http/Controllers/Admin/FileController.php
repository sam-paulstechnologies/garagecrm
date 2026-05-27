<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Shared\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    protected function authorizeCompany(File $file): void
    {
        abort_unless((int) $file->company_id === $this->companyId(), 404);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $companyId = $this->companyId();

        $files = File::query()
            ->with([
                'client' => fn ($query) => $query->where('company_id', $companyId),
                'uploader',
            ])
            ->forCompany($companyId)
            ->orderByDesc('uploaded_at')
            ->paginate(20);

        return view('admin.files.index', compact('files'));
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $companyId = $this->companyId();

        $clients = Client::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.files.create', compact('clients'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],
            'file_type' => [
                'required',
                Rule::in(['Contract', 'Album', 'Sample', 'Invoice']),
            ],
            'category' => [
                'nullable',
                Rule::in(['before_service', 'after_service', 'contract', 'invoice', 'quote', 'other']),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:20480',
            ],
        ]);

        $client = Client::query()
            ->where('company_id', $companyId)
            ->findOrFail($data['client_id']);

        $uploadedFile = $request->file('file');

        $path = $uploadedFile->store(
            "companies/{$companyId}/uploads/files",
            'public'
        );

        File::create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'file_type' => $data['file_type'],
            'category' => $data['category'] ?? null,
            'notes' => $data['notes'] ?? null,
            'file_name' => $data['file_name'] ?? $uploadedFile->getClientOriginalName(),
            'file_path' => $path,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        return redirect()
            ->route('admin.files.index')
            ->with('success', 'File uploaded successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(File $file)
    {
        $this->authorizeCompany($file);

        // Safety: prevent deleting files tied to jobs/invoices.
        if ($file->job_id || $file->invoice_id) {
            return back()->with(
                'warning',
                'This file is linked to a Job or Invoice and cannot be deleted.'
            );
        }

        if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
            Storage::disk('public')->delete($file->file_path);
        }

        $file->delete();

        return redirect()
            ->route('admin.files.index')
            ->with('success', 'File deleted successfully.');
    }
}