<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vehicle_makes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_make_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['vehicle_make_id','name']);
        });
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_make_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_model_id')->nullable()->constrained()->nullOnDelete();
            $table->string('vin')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('color')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('vehicle_makes');
    }
};
