<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price_monthly',10,2)->default(0);
            $table->decimal('price_yearly',10,2)->default(0);
            $table->json('features')->nullable();
            $table->unsignedInteger('max_users')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('plans');
    }
};
