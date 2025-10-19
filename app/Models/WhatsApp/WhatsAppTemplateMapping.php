<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplateMapping extends Model
{
    protected $table = 'whatsapp_template_mappings';
    protected $fillable = ['company_id','event_key','template_id','is_active'];

    public function template() { return $this->belongsTo(WhatsAppTemplate::class, 'template_id'); }
}
