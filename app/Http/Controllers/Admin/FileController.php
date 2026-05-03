<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shared\File;
use App\Models\Client\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /* ================= Helpers ================= */

    protected function companyId(): int
    {
        return auth()->user()->company_id;
    }

    protected function authorizeCompany(File $file): void
    {
        abort_if($file->company_id !== $this->companyId(), 403);
    }

    /* ================= Index ================= */

    public function index()
    {
        $files = File::with(['client', 'uploader'])
            ->forCompany($this->companyId())
            ->orderByDesc('uploaded_at')
            ->paginate(20);

        return view('admin.files.index', compact('files'));
    }

    /* ================= Create ================= */

    public function create()
    {
        $clients = Client::where('company_id', $this->companyId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.files.create', compact('clients'));
    }

    /* ================= Store ================= */

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'  => ['required', 'exists:clients,id'],
            'file_type'  => ['required', 'string', 'max:50'],
            'category'   => ['nullable', 'string', 'max:50'],
            'notes'      => ['nullable', 'string'],
            'file_name'  => ['nullable', 'string', 'max:255'],
            'file'       => ['required', 'file', 'max:20480'], // 20MB
        ]);

        $path = $request->file('file')->store('uploads/files', 'public');

        File::create([
            'company_id'  => $this->companyId(),
            'client_id'   => $data['client_id'],
            'file_type'   => $data['file_type'],
            'category'    => $data['category'] ?? null,
            'notes'       => $data['notes'] ?? null,

            'file_name'   => $data['file_name']
                ?? $request->file('file')->getClientOriginalName(),

            'file_path'   => $path,
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        return redirect()
            ->route('admin.files.index')
            ->with('success', 'File uploaded successfully.');
    }

    /* ================= Destroy ================= */

    public function destroy(File $file)
    {
        $this->authorizeCompany($file);

        // Safety: prevent deleting files tied to jobs/invoices
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
