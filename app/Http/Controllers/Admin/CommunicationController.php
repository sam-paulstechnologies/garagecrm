<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\Communication;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    public function index()
    {
        $communications = Communication::with('client')
            ->where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(20);

        return view('admin.communications.index', compact('communications'));
    }

    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        return view('admin.communications.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'            => 'required|exists:clients,id',
            'communication_type'  => 'required|string',
            'content'              => 'nullable|string',
            'communication_date'  => 'required|date',
            'follow_up_required'   => 'boolean',
        ]);

        $data['company_id'] = auth()->user()->company_id;

        Communication::create($data);

        return redirect()->route('admin.communications.index')->with('success', 'Communication log saved.');
    }

    public function edit(Communication $communication)
    {
        $this->authorizeCompany($communication);

        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        return view('admin.communications.edit', compact('communication', 'clients'));
    }

    public function update(Request $request, Communication $communication)
    {
        $this->authorizeCompany($communication);

        $data = $request->validate([
            'client_id'            => 'required|exists:clients,id',
            'communication_type'  => 'required|string',
            'content'              => 'nullable|string',
            'communication_date'  => 'required|date',
            'follow_up_required'   => 'boolean',
        ]);

        $communication->update($data);

        return redirect()->route('admin.communications.index')->with('success', 'Communication updated.');
    }

    public function destroy(Communication $communication)
    {
        $this->authorizeCompany($communication);
        $communication->delete();

        return redirect()->route('admin.communications.index')->with('success', 'Communication deleted.');
    }

    protected function authorizeCompany($model)
    {
        abort_if($model->company_id !== auth()->user()->company_id, 403);
    }
}
