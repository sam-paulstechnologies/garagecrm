<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignAudience extends Model
{
    protected $fillable = ['campaign_id','target_type','target_id','to','status','whatsapp_message_id'];

    public function campaign(){ return $this->belongsTo(Campaign::class); }
    public function waMessage(){ return $this->belongsTo(WhatsAppMessage::class,'whatsapp_message_id'); }
}
