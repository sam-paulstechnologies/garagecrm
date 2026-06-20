<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_campaign_journey_mappings')) {
            return;
        }

        Schema::create('lead_campaign_journey_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('garage_id')->nullable();
            $table->string('campaign_type');
            $table->string('journey_key')->nullable();
            $table->string('journey_label')->nullable();
            $table->string('journey_trigger_key')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('preview_only')->default(true);
            $table->boolean('whatsapp_enabled')->default(false);
            $table->string('whatsapp_template_name')->nullable();
            $table->string('followup_template_name')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'campaign_type'], 'lead_campaign_mapping_company_type_unique');
            $table->index('company_id', 'lead_campaign_mapping_company_id_index');
            $table->index('garage_id', 'lead_campaign_mapping_garage_id_index');
            $table->index('campaign_type', 'lead_campaign_mapping_type_index');
            $table->index('journey_key', 'lead_campaign_mapping_journey_key_index');
            $table->index('is_active', 'lead_campaign_mapping_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_campaign_journey_mappings');
    }
};
