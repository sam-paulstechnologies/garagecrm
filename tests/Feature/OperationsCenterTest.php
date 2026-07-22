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
                ->assertSee('data-ops-graph-renderer="shared"', false)
                ->assertSee('ops-reference-mode', false);
        }
    }

    public function test_initial_tree_payloads_are_small_and_view_specific(): void
    {
        $superAdmin = $this->user('super_admin');

        $expectations = [
            'journey_flow' => ['mode' => 'flow-tree', 'max_nodes' => 15],
            'mind_map' => ['mode' => 'radial-tree', 'max_nodes' => 15],
            'technical_map' => ['mode' => 'layered-tree', 'max_nodes' => 15],
        ];

        foreach ($expectations as $view => $expected) {
            $payload = $this->actingAs($superAdmin)
                ->getJson(route('super-admin.operations.data', ['view' => $view]))
                ->assertOk()
                ->assertJsonPath('layout_mode', $expected['mode'])
                ->assertJsonPath('metrics.query_count', 0)
                ->json();

            $this->assertLessThanOrEqual($expected['max_nodes'], $payload['metrics']['node_count']);
            $this->assertLessThanOrEqual(20, $payload['metrics']['edge_count']);
            $this->assertLessThan(20000, $payload['metrics']['payload_bytes']);
            $this->assertSame([], $payload['references']);
        }
    }

    public function test_journey_initial_contains_business_lifecycle_only(): void
    {
        $payload = $this->actingAs($this->user('super_admin'))
            ->getJson(route('super-admin.operations.data', ['view' => 'journey_flow']))
            ->assertOk()
            ->json();

        $labels = collect($payload['nodes'])->pluck('label')->all();
        $encoded = json_encode($payload);

        foreach (['Enquiry Received', 'Lead', 'Opportunity', 'Booking', 'Job', 'Invoice', 'Follow-up / Retention'] as $label) {
            $this->assertContains($label, $labels);
        }

        $this->assertStringNotContainsString('route-', $encoded);
        $this->assertStringNotContainsString('Controller', $encoded);
        $this->assertStringNotContainsString('csrf', strtolower($encoded));
        $this->assertStringNotContainsString('login', strtolower($encoded));
        $this->assertStringNotContainsString('/api/', strtolower($encoded));
    }

    public function test_mind_map_initial_excludes_auth_public_api_and_route_nodes(): void
    {
        $payload = $this->actingAs($this->user('super_admin'))
            ->getJson(route('super-admin.operations.data', ['view' => 'mind_map']))
            ->assertOk()
            ->json();

        $labels = collect($payload['nodes'])->pluck('label')->all();
        $encoded = strtolower(json_encode($payload));

        $this->assertContains('SayaraForce', $labels);
        $this->assertContains('Clients & Vehicles', $labels);
        $this->assertContains('Platform Administration', $labels);
        $this->assertStringNotContainsString('api.', $encoded);
        $this->assertStringNotContainsString('password', $encoded);
        $this->assertStringNotContainsString('login', $encoded);
        $this->assertStringNotContainsString('webhook', $encoded);
    }

    public function test_technical_overview_is_category_based_until_trace_is_requested(): void
    {
        $payload = $this->actingAs($this->user('super_admin'))
            ->getJson(route('super-admin.operations.data', ['view' => 'technical_map']))
            ->assertOk()
            ->json();

        $labels = collect($payload['nodes'])->pluck('label')->all();

        foreach (['Application Pages', 'Routes', 'Controllers', 'Services', 'Models', 'Tables', 'Jobs & Queues', 'Tests'] as $label) {
            $this->assertContains($label, $labels);
        }

        $this->assertFalse(collect($payload['nodes'])->contains(fn ($node) => str_starts_with($node['label'], 'admin.')));
    }

    public function test_branch_expansion_loads_only_direct_children(): void
    {
        $payload = $this->actingAs($this->user('super_admin'))
            ->getJson(route('super-admin.operations.branch', ['view' => 'journey_flow', 'parent_id' => 'journey-lead']))
            ->assertOk()
            ->json();

        $this->assertSame('journey-lead', $payload['parent_id']);
        $this->assertLessThanOrEqual(8, $payload['metrics']['node_count']);
        $this->assertTrue(collect($payload['nodes'])->every(fn ($node) => $node['parent_id'] === 'journey-lead'));
        $this->assertContains('Qualified', collect($payload['nodes'])->pluck('label')->all());
    }

    public function test_search_reveals_ancestor_path_only(): void
    {
        $payload = $this->actingAs($this->user('super_admin'))
            ->getJson(route('super-admin.operations.search', ['view' => 'journey_flow', 'q' => 'Booking Confirmed']))
            ->assertOk()
            ->json();

        $this->assertContains('opp-booking-confirmed', $payload['matched_node_ids']);
        $this->assertContains('journey-booking-decision', $payload['ancestor_node_ids']);
        $this->assertLessThanOrEqual(6, $payload['metrics']['node_count']);
        $this->assertFalse(collect($payload['nodes'])->contains('label', 'api.webhooks.meta.whatsapp.handle'));
    }

    public function test_focused_technical_trace_excludes_unrelated_routes_and_exposes_trace_actions(): void
    {
        $payload = $this->actingAs($this->user('super_admin'))
            ->getJson(route('super-admin.operations.trace', ['target' => 'opportunity-details']))
            ->assertOk()
            ->assertJsonPath('layout_mode', 'layered-tree')
            ->json();

        $labels = collect($payload['nodes'])->pluck('label')->all();
        $encoded = json_encode($payload);

        $this->assertContains('Opportunity Details', $labels);
        $this->assertContains('admin.opportunities.show', $labels);
        $this->assertContains('OpportunityController@show', $labels);
        $this->assertContains('opportunities Table', $labels);
        $this->assertContains('Trace authorization', $payload['available_actions']);
        $this->assertStringNotContainsString('admin.bookings.index', $encoded);
        $this->assertStringNotContainsString('api.webhooks', $encoded);
    }

    public function test_manager_has_restricted_progressive_journey_and_is_denied_super_admin_maps(): void
    {
        $company = Company::create([
            'name' => 'Ops Garage',
            'email' => 'ops-garage@example.test',
            'phone' => '971500000001',
            'status' => 'active',
        ]);
        $manager = $this->user('manager', $company->id);

        $this->actingAs($manager)
            ->get(route('manager.operations.journey-flow'))
            ->assertOk()
            ->assertSee('Manager Journey Flow')
            ->assertDontSee('Technical Map');

        $payload = $this->actingAs($manager)
            ->getJson(route('manager.operations.data'))
            ->assertOk()
            ->assertJsonPath('layout_mode', 'flow-tree')
            ->json();

        $encoded = strtolower(json_encode($payload));

        $this->assertLessThanOrEqual(15, $payload['metrics']['node_count']);
        $this->assertStringNotContainsString('controller', $encoded);
        $this->assertStringNotContainsString('source_excerpt', $encoded);
        $this->assertStringNotContainsString('app/', $encoded);
        $this->assertStringNotContainsString('super-admin', $encoded);
        $this->assertStringNotContainsString('database', $encoded);

        $this->actingAs($manager)
            ->get(route('super-admin.operations.view', 'technical-map'))
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
    }

    public function test_open_page_links_are_static_real_routes_without_parameters(): void
    {
        $payload = $this->actingAs($this->user('super_admin'))
            ->getJson(route('super-admin.operations.data', ['view' => 'mind_map']))
            ->assertOk()
            ->json();

        foreach (collect($payload['nodes'])->whereNotNull('url') as $node) {
            $this->assertStringNotContainsString('{', $node['url']);
            $path = parse_url($node['url'], PHP_URL_PATH) ?: '/';
            $matched = Route::getRoutes()->match(Request::create($path, 'GET'));

            $this->assertNotNull($matched->getName());
            $this->assertSame([], $matched->parameters());
        }
    }

    public function test_node_details_are_progressive_and_manager_details_are_sanitized(): void
    {
        $superAdmin = $this->user('super_admin');

        $this->actingAs($superAdmin)
            ->getJson(route('super-admin.operations.node', 'trace-controller-opportunity-show'))
            ->assertOk()
            ->assertJsonPath('query_count', 0)
            ->assertJsonPath('node.file', 'app/Http/Controllers/Admin/OpportunityController.php');

        $company = Company::create([
            'name' => 'Details Garage',
            'email' => 'details@example.test',
            'phone' => '971500000006',
            'status' => 'active',
        ]);

        $this->actingAs($this->user('manager', $company->id))
            ->getJson(route('manager.operations.node', 'journey-lead'))
            ->assertOk()
            ->assertJsonPath('node.controller', null)
            ->assertJsonPath('node.file', null)
            ->assertJsonPath('source_excerpt', [])
            ->assertJsonPath('query_count', 0);
    }

    public function test_shared_renderer_contains_tree_interactions_fullscreen_mobile_and_local_persistence_hooks(): void
    {
        $response = $this->actingAs($this->user('super_admin'))
            ->get(route('super-admin.operations.view', 'journey-flow'))
            ->assertOk();

        $response->assertSee('data-layout-mode="flow-tree"', false)
            ->assertSee('ops-reference-mode', false)
            ->assertSee('function toggleNode(id)', false)
            ->assertSee('function collapseBranch(id)', false)
            ->assertSee('function performSearch()', false)
            ->assertSee('function loadTrace()', false)
            ->assertSee("event.key === 'Escape'", false)
            ->assertSee('ops-scroll-lock', false)
            ->assertSee('localStorage.setItem(storageKey', false)
            ->assertSee('@media (max-width: 900px)', false);
    }

    public function test_layout_drag_hooks_do_not_mutate_application_data_and_query_thresholds_remain_low(): void
    {
        $beforeCompanies = Company::count();

        $this->actingAs($this->user('super_admin'))
            ->get(route('super-admin.operations.view', 'mind-map'))
            ->assertOk()
            ->assertSee('makeDraggable(nodeEl)', false)
            ->assertSee('state.positions', false)
            ->assertSee('localStorage.setItem(storageKey', false);

        $this->assertSame($beforeCompanies, Company::count());

        foreach (['journey_flow', 'mind_map', 'technical_map'] as $view) {
            $this->actingAs($this->user('super_admin'))
                ->getJson(route('super-admin.operations.data', ['view' => $view]))
                ->assertOk()
                ->assertJsonPath('metrics.query_count', 0);
        }
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
