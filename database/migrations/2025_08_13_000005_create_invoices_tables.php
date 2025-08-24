<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('jobsheet_id')->nullable()->constrained('jobsheets')->nullOnDelete();
            $table->string('number')->unique();
            $table->decimal('subtotal',10,2)->default(0);
            $table->decimal('tax',10,2)->default(0);
            $table->decimal('total',10,2)->default(0);
            $table->enum('status', ['draft','sent','paid','void'])->default('draft');
            $table->timestamp('issued_at')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['labour','part'])->default('labour');
            $table->string('description');
            $table->decimal('qty',10,2)->default(1);
            $table->decimal('unit_price',10,2)->default(0);
            $table->decimal('amount',10,2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
