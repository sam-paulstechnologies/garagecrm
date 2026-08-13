<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table): void {
            $table->string('billing_reason', 64)->nullable()->after('status');
            $table->boolean('introductory_cycle_counted')->default(false)->after('billing_reason');
            $table->json('provider_line_snapshot')->nullable()->after('introductory_cycle_counted');
            $table->index(
                ['subscription_id', 'price_id', 'status'],
                'billing_invoice_subscription_price_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table): void {
            $table->dropIndex('billing_invoice_subscription_price_status_index');
            $table->dropColumn([
                'billing_reason', 'introductory_cycle_counted', 'provider_line_snapshot',
            ]);
        });
    }
};
