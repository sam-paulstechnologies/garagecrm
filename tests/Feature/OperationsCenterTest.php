<?php

namespace Tests\Feature;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class OperationsCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

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

    public function test_manager_has_restricted_journey_flow_and_is_denied_super_admin_maps(): void
    {
        $company = Company::create([
            'name' => 'Ops Garage',
            'email' => 'ops-garage@example.test',
            'phone' => '971500000001',
            'status' => 'active',
        ]);

        $this->get(route('super-admin.operations.view', 'journey-flow'))
            ->assertRedirect('/login');

        $manager = $this->user('manager', $company->id);

        $this->actingAs($manager)
            ->get(route('manager.operations.journey-flow'))
            ->assertOk()
            ->assertSee('Manager Journey Flow')
            ->assertSee('data-ops-graph-renderer="shared"', false)
            ->assertDontSee('Technical Map');

        $this->actingAs($manager)
            ->get(route('super-admin.operations.view', 'technical-map'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('super-admin.operations.data', ['view' => 'technical_map']))
            ->assertForbidden();
    }

    public function test_admin_and_other_roles_are_denied_operations_center_as_designed(): void
    {
        $company = Company::create([
            'name' => 'Role Check Garage',
            'email' => 'role-check@example.test',
            'phone' => '971500000002',
            'status' => 'active',
        ]);

        $this->actingAs($this->user('admin', $company->id))
            ->get(route('super-admin.operations.view', 'journey-flow'))
            ->assertForbidden();

        $this->actingAs($this->user('admin', $company->id))
            ->get(route('manager.operations.journey-flow'))
            ->assertForbidden();

        $this->actingAs($this->user('user', $company->id))
            ->get(route('manager.operations.journey-flow'))
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
                $this->assertNotNull($node['route_name'] ?: $node['uri']);
            }
        }
    }

    public function test_manager_graph_payload_hides_internal_platform_details_and_is_tenant_safe(): void
    {
        $company = Company::create([
            'name' => 'Manager Ops Garage',
            'email' => 'manager-ops@example.test',
            'phone' => '971500000003',
            'status' => 'active',
        ]);

        Company::create([
            'name' => 'Other Tenant Garage',
            'email' => 'other-tenant@example.test',
            'phone' => '971500000004',
            'status' => 'active',
        ]);

        $payload = $this->actingAs($this->user('manager', $company->id))
            ->getJson(route('manager.operations.data'))
            ->assertOk()
            ->assertJsonStructure([
                'nodes' => [['id', 'label', 'group', 'summary']],
                'edges' => [['source', 'target']],
                'metrics' => ['query_count', 'payload_bytes', 'node_count', 'edge_count', 'valid_page_references'],
            ])
            ->json();

        $encoded = json_encode($payload);

        $this->assertStringNotContainsString('Other Tenant Garage', $encoded);
        $this->assertStringNotContainsString('App\\\\Http\\\\Controllers', $encoded);
        $this->assertStringNotContainsString('routes/', $encoded);
        $this->assertStringNotContainsString('database', strtolower($encoded));
        $this->assertStringNotContainsString('super-admin', $encoded);
        $this->assertStringNotContainsString('settings', $encoded);
        $this->assertStringNotContainsString('team', $encoded);

        foreach ($payload['nodes'] as $node) {
            $this->assertNull($node['controller']);
            $this->assertNull($node['file']);
            $this->assertIsArray($node['middleware']);
            $this->assertCount(0, $node['middleware']);
        }
    }

    public function test_manager_open_page_links_are_real_accessible_static_pages(): void
    {
        $company = Company::create([
            'name' => 'Link Check Garage',
            'email' => 'link-check@example.test',
            'phone' => '971500000005',
            'status' => 'active',
        ]);
        $manager = $this->user('manager', $company->id);

        $payload = $this->actingAs($manager)
            ->getJson(route('manager.operations.data'))
            ->assertOk()
            ->json();

        foreach (collect($payload['nodes'])->whereNotNull('url') as $node) {
            $this->assertStringNotContainsString('{', $node['url']);
            $path = parse_url($node['url'], PHP_URL_PATH) ?: '/';

            $matched = Route::getRoutes()->match(Request::create($path, 'GET'));

            $this->assertNotNull($matched->getName());
            $this->assertStringStartsWith('manager.', $matched->getName());
            $this->assertSame([], $matched->parameters());
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

    public function test_manager_node_details_are_progressive_without_source_or_database_details(): void
    {
        $company = Company::create([
            'name' => 'Details Garage',
            'email' => 'details@example.test',
            'phone' => '971500000006',
            'status' => 'active',
        ]);
        $manager = $this->user('manager', $company->id);

        $graph = $this->actingAs($manager)
            ->getJson(route('manager.operations.data'))
            ->assertOk()
            ->json();

        $nodeId = collect($graph['nodes'])->firstWhere('group', 'route')['id'];

        $this->actingAs($manager)
            ->getJson(route('manager.operations.node', $nodeId))
            ->assertOk()
            ->assertJsonPath('node.controller', null)
            ->assertJsonPath('node.file', null)
            ->assertJsonPath('source_excerpt', [])
            ->assertJsonPath('query_count', 0)
            ->assertJsonFragment(['access_note' => 'Manager Journey Flow hides platform, source, storage, and route internals.']);
    }

    public function test_shared_renderer_contains_fullscreen_mobile_layout_and_local_persistence_hooks(): void
    {
        $response = $this->actingAs($this->user('super_admin'))
            ->get(route('super-admin.operations.view', 'journey-flow'))
            ->assertOk();

        $response->assertSee('function setFullscreen(enabled)', false)
            ->assertSee("event.key === 'Escape'", false)
            ->assertSee('ops-scroll-lock', false)
            ->assertSee('ops-minimap', false)
            ->assertSee('ops-detail-toggle', false)
            ->assertSee('localStorage.setItem(storageKey', false)
            ->assertSee('ops-graph-selected-', false)
            ->assertSee('@media (max-width: 900px)', false);
    }

    public function test_drag_and_layout_restore_hooks_do_not_mutate_application_data(): void
    {
        $beforeCompanies = Company::count();

        $this->actingAs($this->user('super_admin'))
            ->get(route('super-admin.operations.view', 'mind-map'))
            ->assertOk()
            ->assertSee('makeDraggable(button)', false)
            ->assertSee('persistPositions()', false)
            ->assertSee('localStorage.setItem(storageKey', false);

        $this->assertSame($beforeCompanies, Company::count());
    }

    public function test_operations_center_query_thresholds_remain_low(): void
    {
        $superAdmin = $this->user('super_admin');

        foreach (['journey_flow', 'mind_map', 'technical_map'] as $view) {
            $this->actingAs($superAdmin)
                ->getJson(route('super-admin.operations.data', ['view' => $view]))
                ->assertOk()
                ->assertJsonPath('metrics.query_count', 0);
        }

        $company = Company::create([
            'name' => 'Threshold Garage',
            'email' => 'threshold@example.test',
            'phone' => '971500000007',
            'status' => 'active',
        ]);

        $this->actingAs($this->user('manager', $company->id))
            ->getJson(route('manager.operations.data'))
            ->assertOk()
            ->assertJsonPath('metrics.query_count', 0);
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
