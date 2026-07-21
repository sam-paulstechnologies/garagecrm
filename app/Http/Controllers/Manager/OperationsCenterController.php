<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\Operations\OperationsCatalogueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OperationsCenterController extends Controller
{
    public function view()
    {
        return view('manager.operations.index', [
            'view' => 'journey-flow',
            'title' => 'Manager Journey Flow',
            'subtitle' => 'A restricted operational map for daily garage work. Platform, source, storage, and configuration details are hidden.',
            'graphView' => 'manager_journey',
        ]);
    }

    public function data(OperationsCatalogueService $catalogue)
    {
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $companyId = auth()->user()?->company_id ?: 'none';
        $startedAt = microtime(true);
        $payload = Cache::remember("operations.catalogue.manager.{$companyId}.v3", now()->addMinutes(10), fn () => $catalogue->managerCatalogue());
        $payload['metrics']['query_count'] = $queries;
        $payload['metrics']['response_ms'] = round((microtime(true) - $startedAt) * 1000, 2);
        $payload['metrics']['payload_bytes'] = strlen(json_encode($payload));

        return response()->json($payload);
    }

    public function node(string $id, OperationsCatalogueService $catalogue)
    {
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $details = $catalogue->managerNode($id);

        abort_unless($details, 404);

        $details['query_count'] = $queries;
        $details['payload_bytes'] = strlen(json_encode($details));

        return response()->json($details);
    }
}
