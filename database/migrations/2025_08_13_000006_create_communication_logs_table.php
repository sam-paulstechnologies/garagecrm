<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('jobsheet_id')->nullable()->constrained('jobsheets')->nullOnDelete();
            $table->enum('channel', ['whatsapp','sms','email','call'])->default('whatsapp');
            $table->string('to')->nullable();
            $table->string('template')->nullable();
            $table->text('content')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('communication_logs');
    }
};
