<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 64);
            $table->string('source', 32)->default('application');
            $table->json('properties')->nullable();
            $table->char('dedupe_key', 64)->unique();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['event_type', 'occurred_at'], 'product_event_type_time_idx');
            $table->index(['company_id', 'event_type', 'occurred_at'], 'product_event_tenant_type_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_events');
    }
};
