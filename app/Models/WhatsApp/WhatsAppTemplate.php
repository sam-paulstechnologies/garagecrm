<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'company_id',
        'name',
        'provider_template',
        'language',
        'category',
        'header',
        'body',
        'footer',
        'buttons',
        'variables',
        'status',
        'provider',
        'last_synced_at',
        // 'media', // optional future field
    ];

    protected $casts = [
        'buttons'        => 'array',
        'variables'      => 'array',
        'last_synced_at' => 'datetime',
        // 'media'        => 'array',
    ];

    public function messages()
    {
        return $this->hasMany(WhatsAppMessage::class, 'template_id');
    }

    public function extractVariables(): array
    {
        $text = implode("\n", array_filter([(string) $this->header, (string) $this->body, (string) $this->footer]));
        preg_match_all('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', $text, $m);
        return array_values(array_unique($m[1] ?? []));
    }

    public function scopeForCompany($q, $companyId)
    {
        return $q->where('company_id', $companyId);
    }
}
