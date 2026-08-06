<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Messaging\Exceptions\MessagingProvisioningException;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\WhatsApp\ProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessagingConnectionController extends SuperAdminController
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'status' => ['nullable', 'string', 'max:40'],
            'product_key' => ['nullable', 'string', 'max:64'],
        ]);

        $connections = MessagingConnection::query()
            ->with(['company', 'phoneNumbers'])
            ->when($filters['company_id'] ?? null, fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['product_key'] ?? null, fn ($query, $product) => $query->where('product_key', $product))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('super_admin.messaging_connections.index', [
            'connections' => $connections,
            'filters' => $filters,
            'companies' => $this->companiesForFilter(),
        ]);
    }

    public function show(MessagingConnection $messagingConnection)
    {
        $messagingConnection->load([
            'company',
            'phoneNumbers',
            'checks' => fn ($query) => $query->orderBy('check_key'),
            'audits' => fn ($query) => $query->latest('occurred_at')->limit(50),
        ]);

        return view('super_admin.messaging_connections.show', [
            'connection' => $messagingConnection,
            'phone' => $messagingConnection->phoneNumbers->firstWhere('is_primary', true)
                ?? $messagingConnection->phoneNumbers->first(),
        ]);
    }

    public function retry(MessagingConnection $messagingConnection, ProvisioningService $provisioning, Request $request): RedirectResponse
    {
        try {
            $provisioning->retry($messagingConnection, $request->user(), platformOverride: true);
            return back()->with('success', 'Provisioning retry completed. Review the refreshed checks below.');
        } catch (MessagingProvisioningException $exception) {
            return back()->withErrors(['messaging' => $exception->getMessage()]);
        }
    }
}
