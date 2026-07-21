<?php

namespace Tests\Feature;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationsCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_all_operations_center_views(): void
    {
        $superAdmin = $this->user('super_admin');

        foreach (['journey-flow', 'mind-map', 'technical-map'] as $view) {
            $this->actingAs($superAdmin)
                ->get(route('super-admin.operations.view', $view))
                ->assertOk()
                ->assertSee('ops-root')
                ->assertSee('data-ops-graph-renderer="shared"', false);
        }
    }

    public function test_manager_and_guest_cannot_access_operations_center(): void
    {
        $company = Company::create([
            'name' => 'Ops Garage',
            'email' => 'ops-garage@example.test',
            'phone' => '971500000001',
            'status' => 'active',
        ]);

        $this->get(route('super-admin.operations.view', 'journey-flow'))
            ->assertRedirect('/login');

        $this->actingAs($this->user('manager', $company->id))
            ->get(route('super-admin.operations.view', 'technical-map'))
            ->assertForbidden();

        $this->actingAs($this->user('manager', $company->id))
            ->get(route('super-admin.operations.data', ['view' => 'technical_map']))
            ->assertForbidden();
    }

    public function test_graph_endpoints_return_real_nodes_edges_metrics_and_valid_page_links(): void
    {
        $superAdmin = $this->user('super_admin');

        foreach (['journey_flow', 'mind_map', 'technical_map'] as $view) {
            $response = $this->actingAs($superAdmin)
                ->getJson(route('super-admin.operations.data', ['view' => $view]))
                ->assertOk()
                ->assertJsonStructure([
                    'nodes' => [['id', 'label', 'group', 'summary']],
                    'edges' => [['source', 'target']],
                    'metrics' => ['query_count', 'payload_bytes', 'node_count', 'edge_count', 'valid_page_references'],
                ]);

            $payload = $response->json();
            $this->assertNotEmpty($payload['nodes']);
            $this->assertNotEmpty($payload['edges']);
            $this->assertGreaterThan(0, $payload['metrics']['payload_bytes']);

            foreach (collect($payload['nodes'])->whereNotNull('url') as $node) {
                $this->assertStringStartsWith(config('app.url'), $node['url']);
                $this->assertStringNotContainsString('{', $node['url']);
            }
        }
    }

    public function test_node_details_are_progressive_and_include_source_without_database_queries(): void
    {
        $superAdmin = $this->user('super_admin');

        $graph = $this->actingAs($superAdmin)
            ->getJson(route('super-admin.operations.data', ['view' => 'technical_map']))
            ->assertOk()
            ->json();

        $nodeId = collect($graph['nodes'])->first(fn ($node) => filled($node['file']))['id'];

        $this->actingAs($superAdmin)
            ->getJson(route('super-admin.operations.node', $nodeId))
            ->assertOk()
            ->assertJsonStructure([
                'node' => ['id', 'label', 'file'],
                'relationships',
                'source_excerpt',
                'payload_bytes',
                'query_count',
            ])
            ->assertJsonPath('query_count', 0);
    }

    private function user(string $role, ?int $companyId = null): User
    {
        return User::firstOrCreate(
            ['email' => $role.'-ops-center@example.test'],
            [
                'name' => str($role)->headline().' User',
                'password' => 'password',
                'role' => $role,
                'company_id' => $companyId,
                'status' => true,
                'must_change_password' => false,
            ]
        );
    }
}
