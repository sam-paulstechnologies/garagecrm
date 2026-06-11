<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicle_service_histories')) {
            return;
        }

        Schema::create('vehicle_service_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->string('source_type')->default('client_import_row');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('service_type')->nullable();
            $table->date('service_date')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->string('currency', 8)->default('AED');
            $table->text('notes')->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('company_id', 'vehicle_service_histories_company_id_index');
            $table->index('client_id', 'vehicle_service_histories_client_id_index');
            $table->index('vehicle_id', 'vehicle_service_histories_vehicle_id_index');
            $table->index(['source_type', 'source_id'], 'vehicle_service_histories_source_index');
            $table->index('service_date', 'vehicle_service_histories_service_date_index');
            $table->index('service_type', 'vehicle_service_histories_service_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_service_histories');
    }
};
