<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 64);
            $table->string('field', 96)->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('source', 40)->default('ui');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'lead_id'], 'lead_activity_company_lead_index');
            $table->index(['lead_id', 'created_at'], 'lead_activity_lead_created_index');
            $table->index(['user_id', 'created_at'], 'lead_activity_user_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activity_logs');
    }
};
