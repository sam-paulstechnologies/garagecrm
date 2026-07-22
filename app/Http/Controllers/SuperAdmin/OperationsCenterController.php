<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Services\Operations\OperationsCatalogueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OperationsCenterController extends SuperAdminController
{
    public function view(string $view)
    {
        abort_unless(in_array($view, ['journey-flow', 'mind-map', 'technical-map'], true), 404);

        return view('super_admin.operations.index', [
            'view' => $view,
            'title' => match ($view) {
                'journey-flow' => 'SayaraForce Journey Flow',
                'mind-map' => 'SayaraForce Mind Map',
                'technical-map' => 'SayaraForce Technical Map',
            },
            'subtitle' => match ($view) {
                'journey-flow' => 'Operational customer, lead, booking, job, invoice, and WhatsApp workflow from real SayaraForce routes.',
                'mind-map' => 'Role-aware product map across Super Admin, Admin, Manager, public intake, and automation surfaces.',
                'technical-map' => 'Progressive technical architecture map with routes, controllers, jobs, services, queues, and source traces.',
            },
            'graphView' => str_replace('-', '_', $view),
            'layoutMode' => match ($view) {
                'journey-flow' => 'flow-tree',
                'mind-map' => 'radial-tree',
                'technical-map' => 'layered-tree',
            },
        ]);
    }

    public function data(Request $request, OperationsCatalogueService $catalogue)
    {
        $view = str_replace('-', '_', (string) $request->query('view', 'journey_flow'));
        $view = match ($view) {
            'journey_flow' => 'journey',
            'mind_map' => 'mind',
            'technical_map' => 'technical',
            default => abort(404),
        };

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $startedAt = microtime(true);
        $payload = Cache::remember("operations.catalogue.{$view}.tree.v1", now()->addMinutes(10), fn () => $catalogue->catalogue($view));
        $payload['metrics']['query_count'] = $queries;
        $payload['metrics']['response_ms'] = round((microtime(true) - $startedAt) * 1000, 2);
        $payload['metrics']['payload_bytes'] = strlen(json_encode($payload));

        return response()->json($payload);
    }

    public function branch(Request $request, OperationsCatalogueService $catalogue)
    {
        $view = $this->normalizedView((string) $request->query('view', 'journey_flow'));
        $parentId = (string) $request->query('parent_id', '');

        abort_if(blank($parentId), 422);

        $payload = $catalogue->branch($view, $parentId);

        abort_unless($payload, 404);

        return response()->json($payload);
    }

    public function search(Request $request, OperationsCatalogueService $catalogue)
    {
        $view = $this->normalizedView((string) $request->query('view', 'journey_flow'));
        $term = (string) $request->query('q', '');

        return response()->json($catalogue->search($view, $term));
    }

    public function trace(Request $request, OperationsCatalogueService $catalogue)
    {
        $target = (string) $request->query('target', 'opportunity-details');

        return response()->json($catalogue->trace($target));
    }

    public function node(string $id, OperationsCatalogueService $catalogue)
    {
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $details = $catalogue->node($id);

        abort_unless($details, 404);

        $details['query_count'] = $queries;
        $details['payload_bytes'] = strlen(json_encode($details));

        return response()->json($details);
    }

    private function normalizedView(string $view): string
    {
        $view = str_replace('-', '_', $view);

        return match ($view) {
            'journey_flow' => 'journey',
            'mind_map' => 'mind',
            'technical_map' => 'technical',
            default => abort(404),
        };
    }
}
