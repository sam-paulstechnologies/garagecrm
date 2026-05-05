<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplateMapping extends Model
{
    protected $table = 'whatsapp_template_mappings';

    protected $fillable = [
        'company_id',
        'event_key',
        'template_id',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | ROUTE MODEL BINDING SAFETY
    |--------------------------------------------------------------------------
    */

    public function resolveRouteBinding($value, $field = null)
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        if (!$companyId) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', $companyId)
            ->first();
    }

    public function template()
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'template_id');
    }
}