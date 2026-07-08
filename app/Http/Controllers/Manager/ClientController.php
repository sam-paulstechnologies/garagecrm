<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ClientController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));

        $baseQuery = Client::query()
            ->where('company_id', $companyId)
            ->when(Schema::hasColumn('clients', 'is_active'), function ($query) {
                $query->where('is_active', 1);
            })
            ->when(Schema::hasColumn('clients', 'is_archived'), function ($query) {
                $query->where('is_archived', false);
            });

        $clients = (clone $baseQuery)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    foreach ([
                        'name',
                        'full_name',
                        'customer_name',
                        'client_name',
                        'phone',
                        'mobile',
                        'phone_number',
                        'whatsapp_number',
                        'email',
                        'vehicle_make',
                        'vehicle_model',
                        'plate_number',
                        'notes',
                    ] as $column) {
                        if (Schema::hasColumn('clients', $column)) {
                            $sub->orWhere($column, 'like', '%' . $q . '%');
                        }
                    }
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $clientCounts = [
            'total' => (clone $baseQuery)->count(),
            'with_phone' => Schema::hasColumn('clients', 'phone')
                ? (clone $baseQuery)->whereNotNull('phone')->where('phone', '!=', '')->count()
                : 0,
            'with_email' => Schema::hasColumn('clients', 'email')
                ? (clone $baseQuery)->whereNotNull('email')->where('email', '!=', '')->count()
                : 0,
            'new_30_days' => Schema::hasColumn('clients', 'created_at')
                ? (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count()
                : 0,
        ];

        return view('manager.clients.index', compact('clients', 'q', 'clientCounts'));
    }

    public function show(Client $client)
    {
        $this->authorizeClient($client);

        $client->load($this->availableRelations());

        return view('manager.clients.show', compact('client'));
    }

    protected function authorizeClient(Client $client): void
    {
        abort_if((int) $client->company_id !== $this->companyId(), 403);
    }

    protected function availableRelations(): array
    {
        $relations = [];

        foreach (['leads', 'opportunities', 'bookings', 'jobs', 'invoices', 'communications', 'messageLogs'] as $relation) {
            if (method_exists(Client::class, $relation)) {
                $relations[] = $relation;
            }
        }

        return $relations;
    }
}
