<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use Illuminate\Http\Request;
use App\Imports\ClientImport;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    // 📄 List all active clients
    public function index()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                         ->where('is_archived', false)
                         ->latest()
                         ->paginate(20);

        return view('admin.clients.index', compact('clients'));
    }

    // ➕ Show create form
    public function create()
    {
        return view('admin.clients.create');
    }

    // 💾 Store new client (updated to support AJAX)
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|max:255',
            'phone'    => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'source'   => 'nullable|string|max:255',
        ]);

        $data['company_id'] = auth()->user()->company_id;

        $client = Client::create($data);

        // If it's an AJAX request, return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'id'    => $client->id,
                'name'  => $client->name,
                'phone' => $client->phone
            ]);
        }

        // Fallback to regular redirect
        return redirect()->route('admin.clients.index')->with('success', 'Client created successfully.');
    }

    // ✏️ Show edit form
    public function edit(Client $client)
    {
        $this->authorizeClient($client);
        return view('admin.clients.edit', compact('client'));
    }

    // 🔁 Update client
    public function update(Request $request, Client $client)
    {
        $this->authorizeClient($client);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|max:255',
            'phone'    => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'source'   => 'nullable|string|max:255',
        ]);

        $client->update($data);

        return redirect()->route('admin.clients.index')->with('success', 'Client updated successfully.');
    }

    // 🗃️ Soft delete
    public function archive($id)
    {
        $client = Client::where('company_id', auth()->user()->company_id)->findOrFail($id);
        $client->update(['is_archived' => true]);

        return redirect()->route('admin.clients.index')->with('success', 'Client archived successfully.');
    }

    // 🗂️ View archived clients
    public function archived()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)
                         ->where('is_archived', true)
                         ->latest()
                         ->get();

        return view('admin.clients.archived', compact('clients'));
    }

    // ♻️ Restore archived client
    public function restore($id)
    {
        $client = Client::where('company_id', auth()->user()->company_id)->findOrFail($id);
        $client->update(['is_archived' => false]);

        return redirect()->route('admin.clients.archived')->with('success', 'Client restored successfully.');
    }

    // 👁️ Show client details
    public function show(Client $client)
    {
        $this->authorizeClient($client);

        $client->loadMissing(['leads', 'opportunities', 'files', 'notes']);

        return view('admin.clients.show', compact('client'));
    }

    // 🔐 Restrict access by company
    protected function authorizeClient(Client $client)
    {
        abort_if($client->company_id !== auth()->user()->company_id, 403);
    }

    // 📥 Show import form
    public function importForm()
    {
        return view('admin.clients.import');
    }

    // 📤 Handle import with feedback
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt',
        ]);

        $importer = new ClientImport(auth()->user()->company_id);
        Excel::import($importer, $request->file('file'));

        return redirect()->route('admin.clients.index')
            ->with('import_success', true)
            ->with('imported', $importer->imported)
            ->with('skipped', $importer->skipped)
            ->with('total', $importer->total);
    }
}
