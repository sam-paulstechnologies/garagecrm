<?php

namespace App\Services\Operations;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class OperationsCatalogueService
{
    private const CACHE_VERSION = 'ops-v1';

    public function catalogue(string $view = 'journey'): array
    {
        $startedAt = microtime(true);
        $all = $this->buildCatalogue();
        $filtered = $this->filterForView($all, $view);

        $payload = [
            'view' => $view,
            'generated_at' => now()->toIso8601String(),
            'nodes' => $filtered['nodes'],
            'edges' => $filtered['edges'],
            'filters' => $this->filters($filtered['nodes']),
            'metrics' => [
                'query_count' => 0,
                'response_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'node_count' => count($filtered['nodes']),
                'edge_count' => count($filtered['edges']),
                'valid_page_references' => collect($filtered['nodes'])->whereNotNull('url')->count(),
                'cache_version' => self::CACHE_VERSION,
            ],
        ];

        $payload['metrics']['payload_bytes'] = strlen(json_encode($payload));

        return $payload;
    }

    public function node(string $id): ?array
    {
        $catalogue = $this->buildCatalogue();

        $node = collect($catalogue['nodes'])->firstWhere('id', $id);

        if (! $node) {
            return null;
        }

        $related = collect($catalogue['edges'])
            ->filter(fn ($edge) => $edge['source'] === $id || $edge['target'] === $id)
            ->values()
            ->all();

        $details = [
            'node' => $node,
            'relationships' => $related,
            'source_excerpt' => $this->sourceExcerpt($node['file'] ?? null),
            'payload_bytes' => 0,
            'query_count' => 0,
        ];

        $details['payload_bytes'] = strlen(json_encode($details));

        return $details;
    }

    private function buildCatalogue(): array
    {
        $domains = $this->domainNodes();
        $routeNodes = $this->routeNodes();
        $workflowNodes = $this->workflowNodes();
        $technicalNodes = $this->technicalNodes();
        $nodes = array_merge($domains, $routeNodes, $workflowNodes, $technicalNodes);
        $edges = array_merge(
            $this->routeEdges($routeNodes),
            $this->workflowEdges(),
            $this->technicalEdges()
        );

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    private function domainNodes(): array
    {
        return collect([
            ['id' => 'domain-super-admin', 'label' => 'Super Admin Control Center', 'group' => 'domain', 'views' => ['journey', 'mind', 'technical'], 'summary' => 'Platform owner dashboard, garages, modules, logs, health, and audit controls.'],
            ['id' => 'domain-admin', 'label' => 'Garage Admin Workspace', 'group' => 'domain', 'views' => ['journey', 'mind'], 'summary' => 'Tenant admin operations for leads, opportunities, bookings, jobs, clients, invoices, settings, and WhatsApp.'],
            ['id' => 'domain-manager', 'label' => 'Manager Operations', 'group' => 'domain', 'views' => ['journey', 'mind'], 'summary' => 'Manager-scoped daily garage work with tenant isolation.'],
            ['id' => 'domain-whatsapp', 'label' => 'WhatsApp Automation', 'group' => 'workflow', 'views' => ['journey', 'mind', 'technical'], 'summary' => 'Meta/Twilio inbound webhooks, queue job, conversation engine, message logging, and outbound send.'],
            ['id' => 'domain-lifecycle', 'label' => 'Garage Lifecycle', 'group' => 'workflow', 'views' => ['journey', 'mind'], 'summary' => 'Lead to opportunity, booking, job, invoice, follow-up, and reporting flow.'],
            ['id' => 'domain-technical', 'label' => 'Technical Architecture', 'group' => 'technical', 'views' => ['technical'], 'summary' => 'Routes, controllers, middleware, services, jobs, models, migrations, and queues.'],
        ])->map(fn ($node) => $this->withDefaults($node))->all();
    }

    private function routeNodes(): array
    {
        return collect(Route::getRoutes())
            ->map(function ($route) {
                $name = $route->getName();
                $uri = $route->uri();
                $methods = array_values(array_diff($route->methods(), ['HEAD']));
                $action = $route->getActionName();
                $middleware = $route->gatherMiddleware();
                $pageUrl = $this->pageUrl($methods, $uri, $name);
                $section = $this->routeSection($uri, $name);

                return $this->withDefaults([
                    'id' => 'route-'.Str::slug($name ?: $uri),
                    'label' => $name ?: $uri,
                    'group' => 'route',
                    'section' => $section,
                    'views' => $this->routeViews($uri, $name),
                    'summary' => implode('|', $methods).' /'.$uri,
                    'url' => $pageUrl,
                    'route_name' => $name,
                    'uri' => '/'.$uri,
                    'method' => implode(',', $methods),
                    'controller' => $action,
                    'middleware' => $middleware,
                    'permissions' => $this->permissionSummary($middleware),
                    'file' => $this->controllerFile($action),
                ]);
            })
            ->filter(fn ($node) => $node['route_name'] || ! str_starts_with($node['uri'], '/_'))
            ->values()
            ->all();
    }

    private function workflowNodes(): array
    {
        $items = [
            ['lead-created', 'Lead Captured', 'workflow', 'Website, Meta leads, WhatsApp, import, or manual lead entry.', 'domain-lifecycle'],
            ['lead-qualified', 'Lead Qualified', 'workflow', 'Lead is triaged and can become an opportunity.', 'lead-created'],
            ['opportunity-opened', 'Opportunity Pipeline', 'workflow', 'Service intent, vehicle context, and booking likelihood are tracked.', 'lead-qualified'],
            ['booking-confirmed', 'Booking Confirmed', 'workflow', 'Confirmed service booking and scheduling state.', 'opportunity-opened'],
            ['job-created', 'Job Created', 'workflow', 'Operational job/job card work begins after booking conversion.', 'booking-confirmed'],
            ['invoice-issued', 'Invoice / Estimate', 'workflow', 'Estimate and invoice state linked to job completion.', 'job-created'],
            ['retention-followup', 'Retention Follow-up', 'workflow', 'Journey and retention actions wake later customer communication.', 'invoice-issued'],
            ['wa-webhook', 'Meta WhatsApp Webhook', 'workflow', 'Inbound message payload resolves company and dispatches queue job.', 'domain-whatsapp'],
            ['wa-queue', 'ProcessInboundWhatsApp Job', 'workflow', 'Database/default queue processes inbound automation.', 'wa-webhook'],
            ['wa-conversation', 'Conversation Engine', 'workflow', 'Guard, intent, memory, flow, and fallback produce a reply.', 'wa-queue'],
            ['wa-send', 'Outbound WhatsApp Send', 'workflow', 'Meta/Twilio sender logs outbound provider response.', 'wa-conversation'],
        ];

        return collect($items)->map(fn ($item) => $this->withDefaults([
            'id' => 'workflow-'.$item[0],
            'label' => $item[1],
            'group' => $item[2],
            'views' => ['journey', 'mind'],
            'summary' => $item[3],
            'section' => 'workflow',
        ]))->all();
    }

    private function technicalNodes(): array
    {
        $files = [
            'routes/web.php' => 'Web Route Includes',
            'routes/admin.php' => 'Admin Routes',
            'routes/manager.php' => 'Manager Routes',
            'routes/super_admin.php' => 'Super Admin Routes',
            'routes/api.php' => 'API / Webhook Routes',
            'app/Http/Controllers/Webhooks/MetaWhatsAppWebhookController.php' => 'Meta WhatsApp Webhook Controller',
            'app/Jobs/ProcessInboundWhatsApp.php' => 'Inbound WhatsApp Queue Job',
            'app/Services/Conversation/ConversationEngine.php' => 'Conversation Engine',
            'app/Services/Conversation/ConversationGuard.php' => 'Conversation Guard',
            'app/Services/Conversation/MessageLogger.php' => 'Message Logger',
            'app/Services/WhatsApp/WhatsAppService.php' => 'WhatsApp Service',
            'App_Data/jobs/continuous/sayaraforce-queue/run.sh' => 'Azure Queue WebJob',
        ];

        return collect($files)
            ->filter(fn ($label, $file) => File::exists(base_path($file)))
            ->map(fn ($label, $file) => $this->withDefaults([
                'id' => 'file-'.Str::slug($file),
                'label' => $label,
                'group' => 'file',
                'views' => ['technical'],
                'summary' => $file,
                'file' => $file,
                'section' => 'technical',
            ]))
            ->values()
            ->all();
    }

    private function routeEdges(array $routes): array
    {
        return collect($routes)->map(function ($node) {
            $target = match ($node['section']) {
                'super-admin' => 'domain-super-admin',
                'admin' => 'domain-admin',
                'manager' => 'domain-manager',
                'webhook', 'whatsapp' => 'domain-whatsapp',
                default => 'domain-lifecycle',
            };

            return [
                'id' => 'edge-'.$target.'-'.$node['id'],
                'source' => $target,
                'target' => $node['id'],
                'label' => 'exposes',
            ];
        })->all();
    }

    private function workflowEdges(): array
    {
        $pairs = [
            ['domain-lifecycle', 'workflow-lead-created'],
            ['workflow-lead-created', 'workflow-lead-qualified'],
            ['workflow-lead-qualified', 'workflow-opportunity-opened'],
            ['workflow-opportunity-opened', 'workflow-booking-confirmed'],
            ['workflow-booking-confirmed', 'workflow-job-created'],
            ['workflow-job-created', 'workflow-invoice-issued'],
            ['workflow-invoice-issued', 'workflow-retention-followup'],
            ['domain-whatsapp', 'workflow-wa-webhook'],
            ['workflow-wa-webhook', 'workflow-wa-queue'],
            ['workflow-wa-queue', 'workflow-wa-conversation'],
            ['workflow-wa-conversation', 'workflow-wa-send'],
        ];

        return collect($pairs)->map(fn ($pair) => [
            'id' => 'edge-'.$pair[0].'-'.$pair[1],
            'source' => $pair[0],
            'target' => $pair[1],
            'label' => 'flows to',
        ])->all();
    }

    private function technicalEdges(): array
    {
        $pairs = [
            ['domain-technical', 'file-routes-api-php'],
            ['file-routes-api-php', 'file-app-http-controllers-webhooks-metawhatsappwebhookcontroller-php'],
            ['file-app-http-controllers-webhooks-metawhatsappwebhookcontroller-php', 'file-app-jobs-processinboundwhatsapp-php'],
            ['file-app-jobs-processinboundwhatsapp-php', 'file-app-services-conversation-conversationengine-php'],
            ['file-app-services-conversation-conversationengine-php', 'file-app-services-conversation-conversationguard-php'],
            ['file-app-services-conversation-conversationengine-php', 'file-app-services-conversation-messagelogger-php'],
            ['file-app-jobs-processinboundwhatsapp-php', 'file-app-services-whatsapp-whatsappservice-php'],
            ['domain-technical', 'file-app-data-jobs-continuous-sayaraforce-queue-run-sh'],
        ];

        return collect($pairs)->map(fn ($pair) => [
            'id' => 'edge-'.$pair[0].'-'.$pair[1],
            'source' => $pair[0],
            'target' => $pair[1],
            'label' => 'depends on',
        ])->all();
    }

    private function filterForView(array $catalogue, string $view): array
    {
        $limit = $view === 'technical' ? 42 : 90;
        $nodes = collect($catalogue['nodes'])
            ->filter(fn ($node) => in_array($view, $node['views'], true))
            ->take($limit)
            ->values();

        $nodeIds = $nodes->pluck('id')->all();
        $edges = collect($catalogue['edges'])
            ->filter(fn ($edge) => in_array($edge['source'], $nodeIds, true) && in_array($edge['target'], $nodeIds, true))
            ->values();

        return ['nodes' => $nodes->all(), 'edges' => $edges->all()];
    }

    private function filters(array $nodes): array
    {
        return [
            'groups' => collect($nodes)->pluck('group')->unique()->values()->all(),
            'sections' => collect($nodes)->pluck('section')->filter()->unique()->values()->all(),
        ];
    }

    private function routeSection(string $uri, ?string $name): string
    {
        return match (true) {
            str_starts_with($uri, 'super-admin') => 'super-admin',
            str_starts_with($uri, 'admin') => 'admin',
            str_starts_with($uri, 'manager') => 'manager',
            str_contains($uri, 'webhooks') => 'webhook',
            str_contains($uri, 'whatsapp') => 'whatsapp',
            str_contains((string) $name, 'booking') => 'booking',
            str_contains((string) $name, 'lead') => 'lead',
            default => 'application',
        };
    }

    private function routeViews(string $uri, ?string $name): array
    {
        if (str_contains($uri, 'api/') || str_contains($uri, 'webhooks')) {
            return ['technical', 'mind'];
        }

        if (str_starts_with($uri, 'super-admin')) {
            return ['mind', 'technical'];
        }

        return ['journey', 'mind'];
    }

    private function pageUrl(array $methods, string $uri, ?string $name): ?string
    {
        if (! in_array('GET', $methods, true) || str_contains($uri, '{') || str_contains($uri, 'api/') || str_contains($uri, 'webhooks')) {
            return null;
        }

        return url($uri === '/' ? '/' : '/'.$uri);
    }

    private function permissionSummary(array $middleware): string
    {
        $role = collect($middleware)->first(fn ($item) => str_starts_with((string) $item, 'role:'));

        return $role ? str_replace('role:', '', $role) : (in_array('auth', $middleware, true) ? 'authenticated' : 'public');
    }

    private function controllerFile(string $action): ?string
    {
        if (! str_contains($action, '@') && ! class_exists($action)) {
            return null;
        }

        $class = str_contains($action, '@') ? Str::before($action, '@') : $action;

        if (! class_exists($class)) {
            return null;
        }

        $reflection = new \ReflectionClass($class);

        return str_replace(base_path(DIRECTORY_SEPARATOR), '', $reflection->getFileName() ?: '');
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

    private function withDefaults(array $node): array
    {
        return array_merge([
            'id' => '',
            'label' => '',
            'group' => 'node',
            'section' => null,
            'views' => [],
            'summary' => '',
            'url' => null,
            'route_name' => null,
            'uri' => null,
            'method' => null,
            'controller' => null,
            'middleware' => [],
            'permissions' => null,
            'file' => null,
        ], $node);
    }
}
