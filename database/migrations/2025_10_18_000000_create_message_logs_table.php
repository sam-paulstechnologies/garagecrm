<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->enum('direction', ['out','in']);
            $table->string('channel', 32)->default('whatsapp');
            $table->string('to_number', 32)->nullable();
            $table->string('from_number', 32)->nullable();
            $table->string('template', 128)->nullable();
            $table->text('body')->nullable();
            $table->string('provider_message_id', 64)->nullable();
            $table->string('provider_status', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'lead_id'], 'idx_company_lead');
            $table->index(['direction', 'created_at'], 'idx_dir_created');
            $table->index('provider_message_id', 'idx_provider_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
