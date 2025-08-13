<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shared\File;
use App\Models\Client\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function index()
    {
        $files = File::with('client')
            ->where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(20);

        return view('admin.files.index', compact('files'));
    }

    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        return view('admin.files.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'  => 'required|exists:clients,id',
            'file_type'  => 'required|string|max:50',
            'file_name'  => 'nullable|string|max:255',
            'file'       => 'required|file|max:20480', // max 20MB
        ]);

        $path = $request->file('file')->store('uploads/files', 'public');

        File::create([
            'client_id'   => $data['client_id'],
            'file_type'   => $data['file_type'],
            'file_name'   => $data['file_name'] ?? $request->file('file')->getClientOriginalName(),
            'file_path'   => $path,
            'company_id'  => auth()->user()->company_id,
            'uploaded_at' => now(),
        ]);

        return redirect()->route('admin.files.index')->with('success', 'File uploaded successfully.');
    }

    public function destroy(File $file)
    {
        $this->authorizeCompany($file);

        Storage::disk('public')->delete($file->file_path);
        $file->delete();

        return redirect()->route('admin.files.index')->with('success', 'File deleted.');
    }

    protected function authorizeCompany(File $file)
    {
        abort_if($file->company_id !== auth()->user()->company_id, 403);
    }
}
