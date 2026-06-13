<?php

namespace App\Services\Retention;

use App\Models\Client\RetentionAction;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\WhatsApp\WhatsAppTemplateMapping;
use Illuminate\Support\Facades\Schema;

class RetentionTemplateResolver
{
    /** @var array<int, array<string, WhatsAppTemplateMapping|null>> */
    private array $mappingCache = [];

    /** @var array<int, array<string, WhatsAppTemplate|null>> */
    private array $templateCache = [];

    public function resolve(RetentionAction $action): array
    {
        $config = $this->segmentConfig($action->segment_code);
        $variables = $this->variables($action);
        $templateKey = $config['template_key'] ?? null;
        $fallbackMessage = $config['fallback_message'] ?? ($action->suggested_message ?: '');
        $mapping = $this->mappedTemplate($action, $config);
        $template = $mapping?->template ?: $this->templateByKey((int) $action->company_id, $templateKey);
        $requiredVariables = $config['required_variables'] ?? [];
        $missingVariables = $this->missingVariables($requiredVariables, $variables);
        $renderedFallback = $this->render($fallbackMessage, $variables);
        $renderedTemplate = $template ? $this->renderTemplate($template, $variables) : null;
        $phone = $this->phone($action);
        $isOptedOut = $this->isOptedOut($action);
        $templateStatus = $this->templateStatus($template);

        [$readiness, $label, $class] = $this->readiness(
            $action,
            $config,
            $template,
            $templateStatus,
            $phone,
            $isOptedOut,
            $missingVariables,
            $renderedFallback
        );

        return [
            'template_key' => $templateKey,
            'template_label' => $config['template_label'] ?? str($action->segment_code)->headline()->toString(),
            'mapping_event_key' => $mapping?->event_key,
            'mapped_template_id' => $template?->id,
            'mapped_template_name' => $template?->name,
            'provider_template' => $template?->provider_template,
            'template_status' => $template?->status,
            'template_status_label' => $templateStatus['label'],
            'template_status_class' => $templateStatus['class'],
            'required_variables' => $requiredVariables,
            'variables' => $variables,
            'missing_variables' => $missingVariables,
            'fallback_message' => $renderedFallback,
            'template_preview' => $renderedTemplate,
            'final_message_preview' => $renderedTemplate ?: $renderedFallback,
            'phone' => $phone,
            'readiness' => $readiness,
            'readiness_label' => $label,
            'readiness_class' => $class,
            'warnings' => $this->warnings($config, $template, $phone, $isOptedOut, $missingVariables),
        ];
    }

    private function segmentConfig(?string $segmentCode): array
    {
        return (array) config("retention_templates.segments.{$segmentCode}", []);
    }

    private function variables(RetentionAction $action): array
    {
        $client = $action->client;
        $vehicle = $action->vehicle;
        $company = $action->company;
        $vehicleMake = $vehicle?->make?->name;
        $vehicleModel = $vehicle?->model?->name;
        $vehicleName = trim(($vehicleMake ?? '') . ' ' . ($vehicleModel ?? ''));

        if ($vehicleName === '') {
            $vehicleName = $vehicle?->plate_number ? 'vehicle ' . $vehicle->plate_number : null;
        }

        return [
            'client_name' => $client?->name,
            'vehicle_name' => $vehicleName,
            'vehicle_make' => $vehicleMake,
            'vehicle_model' => $vehicleModel,
            'garage_name' => $company?->name,
            'company_name' => $company?->name,
            'last_service_type' => $action->last_service_type,
            'last_service_date' => $action->last_service_date?->format('d M Y'),
            'follow_up_date' => $action->suggested_follow_up_date?->format('d M Y'),
            'suggested_message' => $action->suggested_message,
            'segment_label' => $action->segment_label ?: str((string) $action->segment_code)->headline()->toString(),
        ];
    }

    private function mappedTemplate(RetentionAction $action, array $config): ?WhatsAppTemplateMapping
    {
        $keys = array_values(array_filter(array_unique(array_merge(
            [$config['template_key'] ?? null],
            $config['event_aliases'] ?? []
        ))));

        foreach ($keys as $key) {
            $mapping = $this->mappingByEventKey((int) $action->company_id, $key);

            if ($mapping?->is_active && $mapping->template) {
                return $mapping;
            }
        }

        return null;
    }

    private function mappingByEventKey(int $companyId, string $eventKey): ?WhatsAppTemplateMapping
    {
        if (! array_key_exists($companyId, $this->mappingCache)) {
            $this->mappingCache[$companyId] = [];
        }

        if (! array_key_exists($eventKey, $this->mappingCache[$companyId])) {
            $this->mappingCache[$companyId][$eventKey] = WhatsAppTemplateMapping::query()
                ->where('company_id', $companyId)
                ->where('event_key', $eventKey)
                ->with('template')
                ->first();
        }

        return $this->mappingCache[$companyId][$eventKey];
    }

    private function templateByKey(int $companyId, ?string $templateKey): ?WhatsAppTemplate
    {
        if (! $templateKey) {
            return null;
        }

        if (! array_key_exists($companyId, $this->templateCache)) {
            $this->templateCache[$companyId] = [];
        }

        if (! array_key_exists($templateKey, $this->templateCache[$companyId])) {
            $this->templateCache[$companyId][$templateKey] = WhatsAppTemplate::query()
                ->where('company_id', $companyId)
                ->where(function ($query) use ($templateKey) {
                    $query->where('name', $templateKey)
                        ->orWhere('provider_template', $templateKey);
                })
                ->first();
        }

        return $this->templateCache[$companyId][$templateKey];
    }

    private function missingVariables(array $requiredVariables, array $variables): array
    {
        return collect($requiredVariables)
            ->filter(fn (string $key) => blank($variables[$key] ?? null))
            ->values()
            ->all();
    }

    private function renderTemplate(WhatsAppTemplate $template, array $variables): string
    {
        $parts = array_filter([
            $template->header,
            $template->body,
            $template->footer,
        ]);

        return $this->render(implode("\n\n", $parts), $variables);
    }

    private function render(string $message, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $message = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $message);
            $message = str_replace('{{ ' . $key . ' }}', (string) ($value ?? ''), $message);
        }

        return trim(preg_replace('/\s+/', ' ', $message));
    }

    private function phone(RetentionAction $action): ?string
    {
        return $action->client?->whatsapp ?: $action->client?->phone;
    }

    private function isOptedOut(RetentionAction $action): bool
    {
        $client = $action->client;

        if (! $client) {
            return false;
        }

        foreach (['whatsapp_opted_out', 'opted_out', 'is_opted_out', 'do_not_contact'] as $column) {
            if (Schema::hasColumn('clients', $column) && (bool) ($client->{$column} ?? false)) {
                return true;
            }
        }

        return false;
    }

    private function readiness(
        RetentionAction $action,
        array $config,
        ?WhatsAppTemplate $template,
        array $templateStatus,
        ?string $phone,
        bool $isOptedOut,
        array $missingVariables,
        string $fallbackMessage
    ): array {
        if (! in_array($action->status, ['approved', 'scheduled'], true)) {
            return ['needs_review', 'Needs Review', 'bg-slate-500/10 text-slate-200 ring-slate-400/20'];
        }

        if (! $phone) {
            return ['blocked_no_phone', 'Missing Phone', 'bg-rose-500/10 text-rose-200 ring-rose-400/20'];
        }

        if ($isOptedOut) {
            return ['blocked_opted_out', 'Opted Out', 'bg-rose-500/10 text-rose-200 ring-rose-400/20'];
        }

        if (empty($config)) {
            return ['warning_missing_template', 'Missing Template', 'bg-amber-500/10 text-amber-200 ring-amber-400/20'];
        }

        if (! $template) {
            return ['warning_missing_template', 'Missing Template', 'bg-amber-500/10 text-amber-200 ring-amber-400/20'];
        }

        if ($templateStatus['state'] === 'pending') {
            return ['template_pending', 'Template Pending', 'bg-yellow-500/10 text-yellow-200 ring-yellow-400/20'];
        }

        if ($templateStatus['state'] === 'rejected') {
            return ['template_rejected', 'Template Rejected', 'bg-rose-500/10 text-rose-200 ring-rose-400/20'];
        }

        if ($templateStatus['state'] !== 'approved') {
            return ['warning_missing_template', 'Missing Template', 'bg-amber-500/10 text-amber-200 ring-amber-400/20'];
        }

        if (in_array('vehicle_name', $missingVariables, true)) {
            return ['warning_missing_vehicle', 'Missing Vehicle', 'bg-amber-500/10 text-amber-200 ring-amber-400/20'];
        }

        if (! empty($missingVariables)) {
            return ['needs_review', 'Needs Review', 'bg-amber-500/10 text-amber-200 ring-amber-400/20'];
        }

        return ['ready', 'Ready', 'bg-emerald-500/10 text-emerald-200 ring-emerald-400/20'];
    }

    private function templateStatus(?WhatsAppTemplate $template): array
    {
        if (! $template) {
            return [
                'state' => 'missing',
                'label' => 'Missing Template',
                'class' => 'bg-amber-500/10 text-amber-200 ring-amber-400/20',
            ];
        }

        $status = strtolower(trim((string) $template->status));

        if (in_array($status, ['active', 'approved', 'enabled', 'quality_pending'], true)) {
            return [
                'state' => 'approved',
                'label' => str($status ?: 'approved')->headline()->toString(),
                'class' => 'bg-emerald-500/10 text-emerald-200 ring-emerald-400/20',
            ];
        }

        if (in_array($status, ['draft', 'pending', 'pending approval', 'pending_approval', 'in_review', 'review', 'submitted'], true)) {
            return [
                'state' => 'pending',
                'label' => 'Pending Review',
                'class' => 'bg-yellow-500/10 text-yellow-200 ring-yellow-400/20',
            ];
        }

        if (in_array($status, ['rejected', 'failed', 'disabled', 'paused', 'archived', 'inactive'], true)) {
            return [
                'state' => 'rejected',
                'label' => str($status)->headline()->toString(),
                'class' => 'bg-rose-500/10 text-rose-200 ring-rose-400/20',
            ];
        }

        return [
            'state' => 'unknown',
            'label' => $status !== '' ? str($status)->headline()->toString() : 'Unknown Status',
            'class' => 'bg-slate-500/10 text-slate-200 ring-slate-400/20',
        ];
    }

    private function warnings(array $config, ?WhatsAppTemplate $template, ?string $phone, bool $isOptedOut, array $missingVariables): array
    {
        $warnings = [];

        if (! $phone) {
            $warnings[] = 'No phone or WhatsApp number is available.';
        }

        if ($isOptedOut) {
            $warnings[] = 'Client appears opted out or marked do not contact.';
        }

        if (empty($config)) {
            $warnings[] = 'No retention template mapping is configured for this segment.';
        } elseif (! $template) {
            $warnings[] = 'No local WhatsApp template record was found. Fallback preview is not send-ready until an approved WhatsApp template is mapped.';
        } else {
            $status = strtolower(trim((string) $template->status));

            if (! in_array($status, ['active', 'approved', 'enabled', 'quality_pending'], true)) {
                $warnings[] = 'Local WhatsApp template is not approved/active yet. Fallback preview is not send-ready.';
            }
        }

        foreach ($missingVariables as $variable) {
            $warnings[] = str($variable)->headline()->toString() . ' is missing.';
        }

        return $warnings;
    }
}
