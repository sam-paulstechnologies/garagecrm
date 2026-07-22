<?php

namespace App\Services\Operations;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class OperationsCatalogueService
{
    private const CACHE_VERSION = 'ops-tree-v1';

    public function catalogue(string $view = 'journey'): array
    {
        $startedAt = microtime(true);
        $tree = $this->tree($view);
        $initial = $this->initialNodes($tree);

        return $this->payload($view, $tree['layout_mode'], $initial['nodes'], $initial['edges'], $startedAt, [
            'reference_mode' => 'off',
            'root_id' => $tree['root_id'],
        ]);
    }

    public function managerCatalogue(): array
    {
        $payload = $this->catalogue('manager');
        $payload['nodes'] = collect($payload['nodes'])->map(fn ($node) => $this->managerSafeNode($node))->values()->all();
        $payload['metrics']['payload_bytes'] = strlen(json_encode($payload));

        return $payload;
    }

    public function branch(string $view, string $parentId, bool $manager = false): ?array
    {
        $tree = $this->tree($manager ? 'manager' : $view);
        $parent = collect($tree['nodes'])->firstWhere('id', $parentId);

        if (! $parent) {
            return null;
        }

        $nodes = collect($tree['nodes'])
            ->where('parent_id', $parentId)
            ->values()
            ->all();

        $nodeIds = collect($nodes)->pluck('id')->all();
        $edges = collect($tree['edges'])
            ->filter(fn ($edge) => in_array($edge['target'], $nodeIds, true) && $edge['source'] === $parentId)
            ->values()
            ->all();

        if ($manager) {
            $nodes = collect($nodes)->map(fn ($node) => $this->managerSafeNode($node))->values()->all();
        }

        return [
            'parent_id' => $parentId,
            'nodes' => $nodes,
            'edges' => $edges,
            'metrics' => [
                'query_count' => 0,
                'node_count' => count($nodes),
                'edge_count' => count($edges),
                'payload_bytes' => strlen(json_encode(['nodes' => $nodes, 'edges' => $edges])),
                'cache_version' => self::CACHE_VERSION,
            ],
        ];
    }

    public function search(string $view, string $term, bool $manager = false): array
    {
        $tree = $this->tree($manager ? 'manager' : $view);
        $normalized = Str::lower(trim($term));

        if ($normalized === '') {
            return ['nodes' => [], 'edges' => [], 'matched_node_ids' => [], 'ancestor_node_ids' => []];
        }

        $allMatches = collect($tree['nodes'])
            ->filter(fn ($node) => str_contains(Str::lower($node['label'].' '.$node['summary'].' '.implode(' ', $node['keywords'] ?? [])), $normalized))
            ->values();
        $exactMatches = $allMatches->filter(fn ($node) => Str::lower($node['label']) === $normalized)->values();
        $matches = ($exactMatches->isNotEmpty() ? $exactMatches : $allMatches)->take(1)->values();

        $ids = [];
        foreach ($matches as $match) {
            $ids = array_merge($ids, $this->ancestorIds($tree['nodes'], $match['id']), [$match['id']]);
        }

        $ids = array_values(array_unique($ids));
        $nodes = collect($tree['nodes'])->filter(fn ($node) => in_array($node['id'], $ids, true))->values()->all();
        $edges = collect($tree['edges'])
            ->filter(fn ($edge) => in_array($edge['source'], $ids, true) && in_array($edge['target'], $ids, true))
            ->values()
            ->all();

        if ($manager) {
            $nodes = collect($nodes)->map(fn ($node) => $this->managerSafeNode($node))->values()->all();
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'matched_node_ids' => $matches->pluck('id')->all(),
            'ancestor_node_ids' => array_values(array_diff($ids, $matches->pluck('id')->all())),
            'metrics' => [
                'query_count' => 0,
                'node_count' => count($nodes),
                'edge_count' => count($edges),
                'payload_bytes' => strlen(json_encode(['nodes' => $nodes, 'edges' => $edges])),
                'cache_version' => self::CACHE_VERSION,
            ],
        ];
    }

    public function trace(string $target = 'opportunity-details'): array
    {
        $tree = $this->tree('technical');
        $trace = $this->technicalTrace($target);
        $ids = collect($trace['nodes'])->pluck('id')->all();

        return [
            'trace_id' => $target,
            'layout_mode' => 'layered-tree',
            'nodes' => $trace['nodes'],
            'edges' => $trace['edges'],
            'references' => $trace['references'],
            'available_actions' => ['Trace this page', 'Trace authorization', 'Trace data storage', 'Show incoming references', 'Show outgoing references', 'Return to architecture overview'],
            'metrics' => [
                'query_count' => 0,
                'node_count' => count($trace['nodes']),
                'edge_count' => count($trace['edges']),
                'payload_bytes' => strlen(json_encode($trace)),
                'cache_version' => self::CACHE_VERSION,
            ],
            'overview_root_id' => $tree['root_id'],
            'visible_node_ids' => $ids,
        ];
    }

    public function node(string $id): ?array
    {
        foreach (['journey', 'mind', 'technical'] as $view) {
            $tree = $this->tree($view);
            $node = collect($tree['nodes'])->firstWhere('id', $id);

            if ($node) {
                return $this->nodeDetails($tree, $node, false);
            }
        }

        foreach (['opportunity-details'] as $trace) {
            $traceTree = $this->technicalTrace($trace);
            $node = collect($traceTree['nodes'])->firstWhere('id', $id);

            if ($node) {
                return $this->nodeDetails($traceTree, $node, false);
            }
        }

        return null;
    }

    public function managerNode(string $id): ?array
    {
        $tree = $this->tree('manager');
        $node = collect($tree['nodes'])->firstWhere('id', $id);

        if (! $node) {
            return null;
        }

        return $this->nodeDetails($tree, $this->managerSafeNode($node), true);
    }

    private function payload(string $view, string $layoutMode, array $nodes, array $edges, float $startedAt, array $extra = []): array
    {
        $payload = array_merge([
            'view' => $view,
            'layout_mode' => $layoutMode,
            'generated_at' => now()->toIso8601String(),
            'nodes' => $nodes,
            'edges' => $edges,
            'references' => [],
            'filters' => $this->filters($nodes),
            'metrics' => [
                'query_count' => 0,
                'response_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'node_count' => count($nodes),
                'edge_count' => count($edges),
                'valid_page_references' => collect($nodes)->whereNotNull('url')->count(),
                'cache_version' => self::CACHE_VERSION,
            ],
        ], $extra);

        $payload['metrics']['payload_bytes'] = strlen(json_encode($payload));

        return $payload;
    }

    private function tree(string $view): array
    {
        return match ($view) {
            'mind' => $this->mindTree(),
            'technical' => $this->technicalTree(),
            'manager' => $this->managerJourneyTree(),
            default => $this->journeyTree(),
        };
    }

    private function initialNodes(array $tree): array
    {
        $nodes = collect($tree['nodes'])
            ->filter(fn ($node) => (bool) ($node['initial'] ?? false))
            ->values()
            ->all();
        $ids = collect($nodes)->pluck('id')->all();
        $edges = collect($tree['edges'])
            ->filter(fn ($edge) => in_array($edge['source'], $ids, true) && in_array($edge['target'], $ids, true))
            ->values()
            ->all();

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    private function journeyTree(): array
    {
        $nodes = [
            $this->nodeItem('journey-enquiry', 'Enquiry Received', 'journey', null, 'Entry point from WhatsApp, web, phone, referral, or manual intake.', ['initial' => true, 'badge' => 'Start', 'role' => 'System / Reception', 'url' => $this->routeUrl('admin.leads.index'), 'details' => ['entry_criteria' => 'A customer enquiry reaches the garage.', 'exit_criteria' => 'A lead record exists with customer and service intent.']]),
            $this->nodeItem('journey-lead', 'Lead', 'journey', 'journey-enquiry', 'Lead contact and qualification state.', ['initial' => true, 'badge' => 'Active', 'role' => 'Admin / Manager', 'url' => $this->routeUrl('admin.leads.index'), 'keywords' => ['New', 'Attempting Contact', 'Qualified', 'Disqualified']]),
            $this->nodeItem('journey-qualified-decision', 'Lead qualified?', 'decision', 'journey-lead', 'Decision branch for whether service intent is ready for pipeline.', ['initial' => true, 'badge' => 'Decision']),
            $this->nodeItem('journey-opportunity', 'Opportunity', 'journey', 'journey-qualified-decision', 'Pipeline stage for service intent, vehicle, offer, and booking likelihood.', ['initial' => true, 'badge' => 'Pipeline', 'role' => 'Admin / Manager', 'url' => $this->routeUrl('admin.opportunities.index'), 'keywords' => ['Appointment', 'Offer', 'Booking Confirmed']]),
            $this->nodeItem('journey-booking-decision', 'Customer confirms?', 'decision', 'journey-opportunity', 'Decision branch for customer commitment to a booking slot.', ['initial' => true, 'badge' => 'Decision']),
            $this->nodeItem('journey-booking', 'Booking', 'journey', 'journey-booking-decision', 'Confirmed or pending workshop appointment.', ['initial' => true, 'badge' => 'Schedule', 'role' => 'Admin / Manager', 'url' => $this->routeUrl('admin.bookings.index')]),
            $this->nodeItem('journey-manager-decision', 'Manager confirms?', 'decision', 'journey-booking', 'Manager confirms, reschedules, rejects, or revises the booking.', ['initial' => true, 'badge' => 'Decision']),
            $this->nodeItem('journey-job', 'Job', 'journey', 'journey-manager-decision', 'Workshop execution and job tracking.', ['initial' => true, 'badge' => 'Workshop', 'role' => 'Manager / Mechanic', 'url' => $this->routeUrl('admin.jobs.index')]),
            $this->nodeItem('journey-invoice', 'Invoice', 'journey', 'journey-job', 'Estimate, invoice, paid/unpaid state, and job closure.', ['initial' => true, 'badge' => 'Billing', 'role' => 'Admin / Manager', 'url' => $this->routeUrl('admin.invoices.index')]),
            $this->nodeItem('journey-delivery', 'Delivery', 'journey', 'journey-invoice', 'Customer handover after service completion.', ['initial' => true, 'badge' => 'Handover']),
            $this->nodeItem('journey-retention', 'Follow-up / Retention', 'journey', 'journey-delivery', 'Retention reminders, review requests, and repeat service loops.', ['initial' => true, 'badge' => 'Retain', 'url' => $this->routeUrl('admin.retention-actions.index')]),

            $this->nodeItem('lead-new', 'New', 'status', 'journey-lead', 'Fresh lead awaiting first action.', ['badge' => 'Lead']),
            $this->nodeItem('lead-attempting', 'Attempting Contact', 'status', 'journey-lead', 'Manager or admin is trying to reach the customer.', ['badge' => 'Lead']),
            $this->nodeItem('lead-hold', 'Contact On Hold', 'status', 'journey-lead', 'Lead is paused with follow-up reason/date.', ['badge' => 'Lead']),
            $this->nodeItem('lead-qualified', 'Qualified', 'status', 'journey-lead', 'Ready to create or reuse an opportunity.', ['badge' => 'Lead']),
            $this->nodeItem('lead-disqualified', 'Disqualified / Nurture', 'status', 'journey-qualified-decision', 'Not ready or not viable; may return through retention later.', ['badge' => 'No']),

            $this->nodeItem('opp-new', 'New', 'status', 'journey-opportunity', 'Pipeline record is opened.', ['badge' => 'Stage']),
            $this->nodeItem('opp-attempting', 'Attempting Contact', 'status', 'journey-opportunity', 'Collect missing service and customer details.', ['badge' => 'Stage']),
            $this->nodeItem('opp-appointment', 'Appointment', 'status', 'journey-opportunity', 'Customer has a candidate visit slot.', ['badge' => 'Stage']),
            $this->nodeItem('opp-offer', 'Offer', 'status', 'journey-opportunity', 'Quotation or service offer is being followed up.', ['badge' => 'Stage']),
            $this->nodeItem('opp-manager-confirm', 'Manager Confirmation Pending', 'status', 'journey-manager-decision', 'Booking awaits manager operational confirmation.', ['badge' => 'Pending']),
            $this->nodeItem('opp-booking-confirmed', 'Booking Confirmed', 'status', 'journey-booking-decision', 'Customer and garage have agreed on the booking.', ['badge' => 'Yes']),
            $this->nodeItem('opp-closed-lost', 'Closed Lost', 'status', 'journey-booking-decision', 'Opportunity ended with a required reason.', ['badge' => 'No']),

            $this->nodeItem('booking-pending', 'Manager Confirmation', 'status', 'journey-booking', 'Booking is pending operational confirmation.', ['badge' => 'Booking']),
            $this->nodeItem('booking-scheduled', 'Booking Confirmed', 'status', 'journey-manager-decision', 'Booking is scheduled and ready for receiving.', ['badge' => 'Yes']),
            $this->nodeItem('booking-reschedule', 'Hold / Revision', 'status', 'journey-manager-decision', 'Booking needs a new slot, reason, or customer confirmation.', ['badge' => 'Revise']),
            $this->nodeItem('booking-converted', 'Converted To Job', 'status', 'journey-job', 'Booking moved into workshop execution.', ['badge' => 'Done']),

            $this->nodeItem('job-pending', 'Pending', 'status', 'journey-job', 'Job awaits workshop start.', ['badge' => 'Job']),
            $this->nodeItem('job-in-progress', 'In Progress', 'status', 'journey-job', 'Work is underway.', ['badge' => 'Job']),
            $this->nodeItem('job-completed', 'Completed', 'status', 'journey-job', 'Work is complete and invoice-ready.', ['badge' => 'Job']),
            $this->nodeItem('invoice-unpaid', 'Unpaid', 'status', 'journey-invoice', 'Payment remains open.', ['badge' => 'Invoice']),
            $this->nodeItem('invoice-paid', 'Paid', 'status', 'journey-invoice', 'Payment complete.', ['badge' => 'Invoice']),
        ];

        return $this->treeResult('journey', 'flow-tree', 'journey-enquiry', $nodes);
    }

    private function managerJourneyTree(): array
    {
        $tree = $this->journeyTree();
        $allowedPageMap = [
            'journey-lead' => $this->routeUrl('manager.leads.index'),
            'journey-opportunity' => $this->routeUrl('manager.opportunities.index'),
            'journey-booking' => $this->routeUrl('manager.bookings.index'),
            'journey-job' => $this->routeUrl('manager.jobs.index'),
            'journey-invoice' => $this->routeUrl('manager.invoices.index'),
        ];

        $tree['nodes'] = collect($tree['nodes'])->map(function ($node) use ($allowedPageMap) {
            $node['url'] = $allowedPageMap[$node['id']] ?? null;
            $node['responsible_role'] = str_replace('Admin / ', '', (string) ($node['responsible_role'] ?? 'Manager'));
            $node['details']['role_guidance'] = 'Manager can act only inside the current garage workspace.';
            return $node;
        })->all();

        return $tree;
    }

    private function mindTree(): array
    {
        $nodes = [
            $this->nodeItem('mind-root', 'SayaraForce', 'root', null, 'Garage growth CRM operations map.', ['initial' => true, 'badge' => 'Root']),
            $this->nodeItem('mind-clients', 'Clients & Vehicles', 'module', 'mind-root', 'Customer profiles, vehicles, service history, and notes.', ['initial' => true, 'side' => 'left', 'url' => $this->routeUrl('admin.clients.index')]),
            $this->nodeItem('mind-leads', 'Leads', 'module', 'mind-root', 'Lead capture, assignment, qualification, and imports.', ['initial' => true, 'side' => 'left', 'url' => $this->routeUrl('admin.leads.index')]),
            $this->nodeItem('mind-opportunities', 'Opportunities', 'module', 'mind-root', 'Pipeline stages and booking conversion.', ['initial' => true, 'side' => 'left', 'url' => $this->routeUrl('admin.opportunities.index')]),
            $this->nodeItem('mind-bookings', 'Bookings', 'module', 'mind-root', 'Appointment scheduling, confirmation, reschedule, and conversion.', ['initial' => true, 'side' => 'left', 'url' => $this->routeUrl('admin.bookings.index')]),
            $this->nodeItem('mind-inbox', 'WhatsApp Inbox', 'module', 'mind-root', 'Connected channel conversations and human replies.', ['initial' => true, 'side' => 'left', 'url' => $this->routeUrl('admin.inbox.index')]),
            $this->nodeItem('mind-calendar', 'Calendar', 'module', 'mind-root', 'Booking calendar and appointment planning.', ['initial' => true, 'side' => 'left', 'url' => $this->routeUrl('admin.calendar.index')]),
            $this->nodeItem('mind-jobs', 'Jobs & Workshop', 'module', 'mind-root', 'Workshop jobs, mechanics, media, and completion.', ['initial' => true, 'side' => 'right', 'url' => $this->routeUrl('admin.jobs.index')]),
            $this->nodeItem('mind-team', 'Team & Mechanics', 'module', 'mind-root', 'Users, managers, mechanics, and assignment.', ['initial' => true, 'side' => 'right', 'url' => $this->routeUrl('admin.users.index')]),
            $this->nodeItem('mind-invoices', 'Invoices & Payments', 'module', 'mind-root', 'Invoices, estimates, paid/unpaid follow-up.', ['initial' => true, 'side' => 'right', 'url' => $this->routeUrl('admin.invoices.index')]),
            $this->nodeItem('mind-reports', 'Reports & Growth', 'module', 'mind-root', 'Operational summary, growth, retention, and funnel reporting.', ['initial' => true, 'side' => 'right', 'url' => $this->routeUrl('admin.reports.garage-summary')]),
            $this->nodeItem('mind-access', 'Access & Permissions', 'module', 'mind-root', 'Role boundaries and manager-safe operations.', ['initial' => true, 'side' => 'right']),
            $this->nodeItem('mind-platform', 'Platform Administration', 'module', 'mind-root', 'Super Admin controls, plans, diagnostics, and audit.', ['initial' => true, 'side' => 'right', 'url' => $this->routeUrl('super-admin.dashboard')]),

            $this->nodeItem('mind-jobs-list', 'Jobs List', 'page', 'mind-jobs', 'Operational job queue.', ['url' => $this->routeUrl('admin.jobs.index')]),
            $this->nodeItem('mind-jobs-details', 'Job Details', 'page', 'mind-jobs', 'Customer, vehicle, service, assignment, and invoice readiness.'),
            $this->nodeItem('mind-jobs-mechanics', 'Assigned Mechanics', 'capability', 'mind-jobs', 'Mechanic assignment and accountability.'),
            $this->nodeItem('mind-jobs-statuses', 'Job Statuses', 'status', 'mind-jobs', 'Pending, in progress, completed.'),
            $this->nodeItem('mind-jobs-media', 'Inspection / Media', 'capability', 'mind-jobs', 'Media evidence and inspection context.'),
            $this->nodeItem('mind-jobs-invoice-ready', 'Invoice Readiness', 'capability', 'mind-jobs', 'Job completion can create or reuse an invoice.'),

            $this->nodeItem('mind-leads-queue', 'Lead Queue', 'page', 'mind-leads', 'Open and follow-up lead work.', ['url' => $this->routeUrl('admin.leads.index')]),
            $this->nodeItem('mind-leads-import', 'Lead Import', 'page', 'mind-leads', 'Review imported leads and duplicate context.'),
            $this->nodeItem('mind-leads-statuses', 'Lead Statuses', 'status', 'mind-leads', 'New, attempting, hold, qualified, disqualified.'),
            $this->nodeItem('mind-bookings-statuses', 'Booking Statuses', 'status', 'mind-bookings', 'Manager confirmation, confirmed, reschedule, converted, lost.'),
            $this->nodeItem('mind-inbox-channel', 'Connected Channel', 'capability', 'mind-inbox', 'Read-only sending channel visibility for Meta review.'),
            $this->nodeItem('mind-platform-garages', 'Garages', 'page', 'mind-platform', 'Tenant and company control center.', ['url' => $this->routeUrl('super-admin.garages.index')]),
            $this->nodeItem('mind-platform-health', 'System Health', 'page', 'mind-platform', 'Queue, WhatsApp, and platform diagnostics.', ['url' => $this->routeUrl('super-admin.system.health')]),
        ];

        return $this->treeResult('mind', 'radial-tree', 'mind-root', $nodes);
    }

    private function technicalTree(): array
    {
        $nodes = [
            $this->nodeItem('tech-root', 'Technical Architecture', 'root', null, 'Category overview. Select a page or trace to inspect one focused path.', ['initial' => true, 'badge' => 'Overview']),
            $this->nodeItem('tech-pages', 'Application Pages', 'technical', 'tech-root', 'Blade/Inertia surfaces used by Admin, Manager, and Super Admin.', ['initial' => true, 'badge' => 'Pages']),
            $this->nodeItem('tech-routes', 'Routes', 'technical', 'tech-root', 'Named web routes and middleware boundaries.', ['initial' => true, 'badge' => 'Routes']),
            $this->nodeItem('tech-controllers', 'Controllers', 'technical', 'tech-root', 'HTTP request orchestration.', ['initial' => true, 'badge' => 'HTTP']),
            $this->nodeItem('tech-services', 'Services', 'technical', 'tech-root', 'Business logic and external service clients.', ['initial' => true, 'badge' => 'Logic']),
            $this->nodeItem('tech-models', 'Models', 'technical', 'tech-root', 'Eloquent records and relationships.', ['initial' => true, 'badge' => 'ORM']),
            $this->nodeItem('tech-tables', 'Tables', 'technical', 'tech-root', 'Persistence tables and fields.', ['initial' => true, 'badge' => 'Storage']),
            $this->nodeItem('tech-jobs', 'Jobs & Queues', 'technical', 'tech-root', 'Database queue and background workers.', ['initial' => true, 'badge' => 'Queue']),
            $this->nodeItem('tech-policies', 'Policies & Permissions', 'technical', 'tech-root', 'Role middleware, tenant checks, and access control.', ['initial' => true, 'badge' => 'Access']),
            $this->nodeItem('tech-integrations', 'Integrations', 'technical', 'tech-root', 'Meta WhatsApp, Twilio, Google leads, and NLP fallback.', ['initial' => true, 'badge' => 'API']),
            $this->nodeItem('tech-tests', 'Tests', 'technical', 'tech-root', 'Feature and regression coverage.', ['initial' => true, 'badge' => 'QA']),

            $this->nodeItem('tech-page-opportunity-details', 'Opportunity Details', 'page', 'tech-pages', 'Admin opportunity detail page trace target.', ['url' => $this->routeUrl('admin.opportunities.index'), 'trace_id' => 'opportunity-details']),
            $this->nodeItem('tech-route-admin-opportunities-show', 'admin.opportunities.show', 'route', 'tech-routes', 'Parameterized detail route for opportunity records.'),
            $this->nodeItem('tech-controller-opportunity-show', 'OpportunityController@show', 'controller', 'tech-controllers', 'Loads opportunity detail context.'),
            $this->nodeItem('tech-service-opportunity-workflow', 'Opportunity Workflow', 'service', 'tech-services', 'Stage updates, booking conversion, and validation flow.'),
            $this->nodeItem('tech-model-opportunity', 'Opportunity Model', 'model', 'tech-models', 'Pipeline model in app/Models/Client/Opportunity.php.'),
            $this->nodeItem('tech-table-opportunities', 'opportunities Table', 'table', 'tech-tables', 'Stores stage, company, lead, client, vehicle, and booking context.'),
            $this->nodeItem('tech-policy-manager-scope', 'Tenant Scope', 'policy', 'tech-policies', 'Company-scoped access and role middleware.'),
            $this->nodeItem('tech-test-manager-lifecycle', 'Manager Lifecycle Tests', 'test', 'tech-tests', 'Feature tests protect manager workflow and tenant isolation.'),
        ];

        return $this->treeResult('technical', 'layered-tree', 'tech-root', $nodes);
    }

    private function technicalTrace(string $target): array
    {
        $nodes = [
            $this->nodeItem('trace-page-opportunity-details', 'Opportunity Details', 'page', null, 'Focused trace for one application page.', ['initial' => true, 'url' => $this->routeUrl('admin.opportunities.index')]),
            $this->nodeItem('trace-route-admin-opportunities-show', 'admin.opportunities.show', 'route', 'trace-page-opportunity-details', 'Route with opportunity parameter; trace uses a real route name but does not fabricate a record URL.'),
            $this->nodeItem('trace-controller-opportunity-show', 'OpportunityController@show', 'controller', 'trace-route-admin-opportunities-show', 'Loads opportunity, linked bookings, jobs, invoices, activity, and safe actions.', ['file' => 'app/Http/Controllers/Admin/OpportunityController.php']),
            $this->nodeItem('trace-service-opportunity-workflow', 'Opportunity Workflow', 'service', 'trace-controller-opportunity-show', 'Stage and booking conversion rules used by Admin and Manager flows.'),
            $this->nodeItem('trace-model-opportunity', 'Opportunity Model', 'model', 'trace-service-opportunity-workflow', 'Opportunity stage source of truth and relationships.', ['file' => 'app/Models/Client/Opportunity.php']),
            $this->nodeItem('trace-table-opportunities', 'opportunities Table', 'table', 'trace-model-opportunity', 'Stores company_id, lead_id, client_id, vehicle_id, stage, status, lost reason, and conversion timestamps.'),
            $this->nodeItem('trace-policy-role', 'Role + Tenant Guard', 'policy', 'trace-route-admin-opportunities-show', 'Admin/Manager middleware and company scope protect direct record access.'),
            $this->nodeItem('trace-test-manager-lifecycle', 'ManagerLifecycleInvariantTest', 'test', 'trace-policy-role', 'Confirms direct record access and manager status transitions remain tenant isolated.'),
        ];

        return [
            'nodes' => $nodes,
            'edges' => $this->parentEdges($nodes),
            'references' => [
                ['id' => 'ref-trace-controller-test', 'source' => 'trace-controller-opportunity-show', 'target' => 'trace-test-manager-lifecycle', 'label' => 'covered by'],
                ['id' => 'ref-trace-model-policy', 'source' => 'trace-model-opportunity', 'target' => 'trace-policy-role', 'label' => 'scoped by'],
            ],
        ];
    }

    private function treeResult(string $view, string $mode, string $rootId, array $nodes): array
    {
        $nodes = $this->hydrateChildCounts($nodes);

        return [
            'view' => $view,
            'layout_mode' => $mode,
            'root_id' => $rootId,
            'nodes' => $nodes,
            'edges' => $this->parentEdges($nodes),
        ];
    }

    private function nodeItem(string $id, string $label, string $group, ?string $parentId, string $summary, array $extra = []): array
    {
        return array_merge([
            'id' => $id,
            'label' => $label,
            'title' => $label,
            'group' => $group,
            'parent_id' => $parentId,
            'level' => 0,
            'side' => $extra['side'] ?? null,
            'summary' => $summary,
            'status_badge' => $extra['badge'] ?? 'Ready',
            'completion' => $extra['completion'] ?? null,
            'child_count' => 0,
            'has_children' => false,
            'initial' => (bool) ($extra['initial'] ?? false),
            'url' => $extra['url'] ?? null,
            'route_name' => $extra['route_name'] ?? null,
            'uri' => null,
            'method' => null,
            'controller' => $extra['controller'] ?? null,
            'middleware' => [],
            'permissions' => $extra['permissions'] ?? null,
            'file' => $extra['file'] ?? null,
            'keywords' => $extra['keywords'] ?? [],
            'responsible_role' => $extra['role'] ?? null,
            'inputs' => $extra['inputs'] ?? [],
            'outputs' => $extra['outputs'] ?? [],
            'blockers' => $extra['blockers'] ?? [],
            'next_stage' => $extra['next_stage'] ?? null,
            'details' => $extra['details'] ?? [],
            'trace_id' => $extra['trace_id'] ?? null,
        ], $extra);
    }

    private function hydrateChildCounts(array $nodes): array
    {
        $counts = collect($nodes)->groupBy('parent_id')->map->count();
        $levels = [];

        $resolveLevel = function ($node) use (&$resolveLevel, &$levels, $nodes) {
            if (isset($levels[$node['id']])) {
                return $levels[$node['id']];
            }

            if (! $node['parent_id']) {
                return $levels[$node['id']] = 0;
            }

            $parent = collect($nodes)->firstWhere('id', $node['parent_id']);

            return $levels[$node['id']] = $parent ? $resolveLevel($parent) + 1 : 0;
        };

        return collect($nodes)->map(function ($node) use ($counts, $resolveLevel) {
            $node['child_count'] = (int) ($counts[$node['id']] ?? 0);
            $node['has_children'] = $node['child_count'] > 0;
            $node['level'] = $resolveLevel($node);
            return $node;
        })->all();
    }

    private function parentEdges(array $nodes): array
    {
        return collect($nodes)
            ->filter(fn ($node) => filled($node['parent_id']))
            ->map(fn ($node) => [
                'id' => 'edge-'.$node['parent_id'].'-'.$node['id'],
                'source' => $node['parent_id'],
                'target' => $node['id'],
                'label' => 'contains',
                'type' => 'parent',
            ])
            ->values()
            ->all();
    }

    private function nodeDetails(array $tree, array $node, bool $manager): array
    {
        $relationships = collect($tree['edges'] ?? [])
            ->filter(fn ($edge) => $edge['source'] === $node['id'] || $edge['target'] === $node['id'])
            ->values()
            ->all();

        $details = [
            'node' => $manager ? $this->managerSafeNode($node) : $node,
            'relationships' => $relationships,
            'source_excerpt' => $manager ? [] : $this->sourceExcerpt($node['file'] ?? null),
            'payload_bytes' => 0,
            'query_count' => 0,
            'access_note' => $manager ? 'Manager Journey Flow hides platform, code, storage, and route internals.' : null,
        ];

        $details['payload_bytes'] = strlen(json_encode($details));

        return $details;
    }

    private function ancestorIds(array $nodes, string $id): array
    {
        $byId = collect($nodes)->keyBy('id');
        $ancestors = [];
        $current = $byId[$id] ?? null;

        while ($current && $current['parent_id']) {
            $ancestors[] = $current['parent_id'];
            $current = $byId[$current['parent_id']] ?? null;
        }

        return array_reverse($ancestors);
    }

    private function filters(array $nodes): array
    {
        return [
            'groups' => collect($nodes)->pluck('group')->unique()->values()->all(),
            'sections' => [],
        ];
    }

    private function routeUrl(string $name): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        $route = Route::getRoutes()->getByName($name);

        if (! $route || count($route->parameterNames()) > 0 || ! in_array('GET', $route->methods(), true)) {
            return null;
        }

        try {
            return route($name);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sourceExcerpt(?string $file): array
    {
        if (! $file || ! File::exists(base_path($file))) {
            return [];
        }

        return collect(file(base_path($file), FILE_IGNORE_NEW_LINES))
            ->take(24)
            ->values()
            ->all();
    }

    private function managerSafeNode(array $node): array
    {
        $safe = $node;
        $safe['permissions'] = 'Manager only, current garage data only';
        unset($safe['controller'], $safe['middleware'], $safe['file'], $safe['route_name'], $safe['uri'], $safe['method']);

        return $safe;
    }
}
