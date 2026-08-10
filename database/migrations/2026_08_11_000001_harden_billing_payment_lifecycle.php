<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_checkout_sessions', function (Blueprint $table): void {
            $table->string('provider_subscription_id', 191)->nullable()->after('provider_checkout_id');
            $table->string('provider_price_id', 191)->nullable()->after('provider_subscription_id');
            $table->index(
                ['payment_provider', 'provider_subscription_id'],
                'billing_checkout_provider_subscription_index'
            );
        });

        Schema::table('billing_invoices', function (Blueprint $table): void {
            $table->foreignId('billing_checkout_session_id')
                ->nullable()
                ->after('subscription_id')
                ->constrained('billing_checkout_sessions')
                ->nullOnDelete();
            $table->foreignId('price_id')
                ->nullable()
                ->after('billing_checkout_session_id')
                ->constrained('prices')
                ->nullOnDelete();
            $table->boolean('test_mode')->default(false)->after('payment_provider');
            $table->index(
                ['company_id', 'test_mode', 'created_at'],
                'billing_invoice_company_test_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('billing_invoices', function (Blueprint $table): void {
            $table->dropIndex('billing_invoice_company_test_index');
            $table->dropConstrainedForeignId('price_id');
            $table->dropConstrainedForeignId('billing_checkout_session_id');
            $table->dropColumn('test_mode');
        });

        Schema::table('billing_checkout_sessions', function (Blueprint $table): void {
            $table->dropIndex('billing_checkout_provider_subscription_index');
            $table->dropColumn(['provider_subscription_id', 'provider_price_id']);
        });
    }
};
