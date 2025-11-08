<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsApp\WhatsAppTemplate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TemplateRenderer
{
    /**
     * Render a WhatsAppTemplate for a given company + template name.
     * Returns a normalized payload you can use in both sandbox (Twilio text)
     * and production (Meta components).
     *
     * @return array{
     *   template: string,
     *   language: string,
     *   header: string|null,
     *   body: string,
     *   footer: string|null,
     *   buttons: array<int, array{type:string,text?:string,url?:string,phone?:string}>,
     *   text: string,                 // fully assembled text (good for Twilio sandbox)
     *   components: array<int, mixed> // Meta components array (ready to send)
     * }
     */
    public function render(int $companyId, string $templateName, array $placeholders = [], array $links = []): array
    {
        /** @var WhatsAppTemplate|null $tpl */
        $tpl = WhatsAppTemplate::query()
            ->forCompany($companyId)
            ->where(function ($q) use ($templateName) {
                $q->where('name', $templateName)
                  ->orWhere('provider_template', $templateName);
            })
            ->first();

        if (!$tpl) {
            // Fallback minimal payload so sends don’t 500 if a name is off
            Log::warning('[TemplateRenderer] template not found', [
                'company_id' => $companyId, 'template' => $templateName
            ]);

            $text = $this->assembleText($templateName, [], $links);

            return [
                'template'   => $templateName,
                'language'   => 'en',
                'header'     => null,
                'body'       => $text,
                'footer'     => null,
                'buttons'    => [],
                'text'       => $text,
                'components' => [
                    [
                        'type'       => 'body',
                        'parameters' => [['type' => 'text', 'text' => $text]],
                    ],
                ],
            ];
        }

        // variable set to allow both {{var}} and @{{var}}
        $vars   = array_unique(array_merge(
            $tpl->variables ?? [],
            $this->extractVariables((string)$tpl->header . "\n" . (string)$tpl->body . "\n" . (string)$tpl->footer)
        ));

        // normalize placeholders: allow both numeric-indexed and assoc maps
        $map = $this->normalizeMap($vars, $placeholders);

        $header = $this->replaceVars((string) $tpl->header, $map);
        $body   = $this->replaceVars((string) $tpl->body,   $map);
        $footer = $this->replaceVars((string) $tpl->footer, $map);

        $btns = [];
        foreach ((array) $tpl->buttons as $b) {
            $btns[] = [
                'type'  => (string) ($b['type'] ?? 'quick_reply'),
                'text'  => $this->replaceVars((string) ($b['text'] ?? ''),  $map),
                'url'   => $this->replaceVars((string) ($b['url']  ?? ''),  $map),
                'phone' => $this->replaceVars((string) ($b['phone']?? ''),  $map),
            ];
        }

        // final text for Twilio sandbox
        $text = $this->assembleText($header, $body, $footer, $btns, $links);

        // Meta components (body + optional URL button index 0)
        $components = [];
        if ($body !== '') {
            $components[] = [
                'type'       => 'body',
                'parameters' => $this->metaTextParams($body, $vars, $map),
            ];
        }
        if (!empty($btns)) {
            // Only URL buttons are supported via components in this simple helper
            $firstUrl = Arr::first(array_values(array_filter($btns, fn($x) => ($x['type'] ?? '') === 'url')));
            if ($firstUrl) {
                $components[] = [
                    'type'       => 'button',
                    'sub_type'   => 'url',
                    'index'      => '0',
                    'parameters' => [['type' => 'text', 'text' => (string) $firstUrl['url']]],
                ];
            }
        }

        return [
            'template'   => $tpl->provider_template ?: $tpl->name,
            'language'   => $tpl->language ?: 'en',
            'header'     => $header ?: null,
            'body'       => $body,
            'footer'     => $footer ?: null,
            'buttons'    => $btns,
            'text'       => $text,
            'components' => $components,
        ];
    }

    /* ---------------- helpers ---------------- */

    /** Accepts either (vars=>values) or numeric array; returns map keyed by var name */
    protected function normalizeMap(array $vars, array $incoming): array
    {
        // if incoming is numeric-indexed, map to vars order
        if ($this->isList($incoming)) {
            $out = [];
            foreach ($vars as $i => $v) {
                if (array_key_exists($i, $incoming)) $out[$v] = (string) $incoming[$i];
            }
            return $out;
        }
        // otherwise treat as assoc, lowercasing keys
        $out = [];
        foreach ($incoming as $k => $v) {
            $out[strtolower((string)$k)] = (string) $v;
        }
        return $out;
    }

    protected function isList(array $arr): bool
    {
        $i = 0;
        foreach ($arr as $k => $_) {
            if ($k !== $i++) return false;
        }
        return true;
    }

    /** Replace both {{var}} and @{{var}} safely; if value missing, leave token as-is. */
    protected function replaceVars(string $text, array $map): string
    {
        if ($text === '') return '';

        // @{{var}}
        $text = preg_replace_callback('/@\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function ($m) use ($map) {
            $k = strtolower($m[1]);
            return array_key_exists($k, $map) ? (string) $map[$k] : '{{'.$m[1].'}}';
        }, $text) ?? $text;

        // {{var}}
        $text = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function ($m) use ($map) {
            $k = strtolower($m[1]);
            return array_key_exists($k, $map) ? (string) $map[$k] : '{{'.$m[1].'}}';
        }, $text) ?? $text;

        return $text;
    }

    /** Extract {{var}} style tokens from any text. */
    protected function extractVariables(string $text): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', $text, $m1);
        preg_match_all('/@\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', $text, $m2);
        return array_values(array_unique(array_merge($m1[1] ?? [], $m2[1] ?? [])));
    }

    /** Build a readable text for Twilio sandbox (header/body/footer + buttons + links). */
    protected function assembleText(string $header, string $body, string $footer = '', array $buttons = [], array $links = []): string
    {
        // If $header passed as template name (when tpl missing), allow body to be that string
        if ($body === '' && $footer === '' && empty($buttons) && empty($links)) {
            return trim($header);
        }

        $parts = [];
        if (trim($header) !== '') $parts[] = trim($header);
        if (trim($body)   !== '') $parts[] = trim($body);
        if (trim($footer) !== '') $parts[] = trim($footer);

        if (!empty($buttons)) {
            $btnText = array_map(function ($b) {
                $type = strtoupper((string) ($b['type'] ?? ''));
                $label = trim((string) ($b['text'] ?? ''));
                $extra = $type === 'URL' ? (' → ' . (string) ($b['url'] ?? '')) : ($type === 'PHONE' ? (' → ' . (string) ($b['phone'] ?? '')) : '');
                return ($label !== '' ? $label : $type) . $extra;
            }, $buttons);
            $parts[] = implode("\n", $btnText);
        }

        foreach ($links as $label => $url) {
            $label = is_string($label) ? $label : 'Link';
            $parts[] = "{$label}: {$url}";
        }

        return trim(implode("\n\n", array_filter($parts, fn($x) => trim($x) !== '')));
    }

    /** Convert the body into Meta text parameters (we just send as a single text param). */
    protected function metaTextParams(string $body, array $vars, array $map): array
    {
        // If your templates are approved with numbered placeholders, you can instead
        // push each param separately. For now we send as one text block.
        return [['type' => 'text', 'text' => $body]];
    }
}
