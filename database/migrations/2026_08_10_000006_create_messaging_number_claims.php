<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messaging_phone_numbers', function (Blueprint $table): void {
            $table->string('phone_e164', 16)->nullable()->after('display_phone_number');
            $table->unique('phone_e164', 'messaging_phone_e164_unique');
        });

        Schema::create('messaging_number_claims', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('messaging_phone_number_id')->nullable()->unique()
                ->constrained('messaging_phone_numbers')->nullOnDelete();
            $table->string('phone_e164', 16)->unique();
            $table->string('label', 80)->nullable();
            $table->string('connection_mode', 64);
            $table->string('status', 40)->default('pending_meta');
            $table->timestamp('meta_verified_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'messaging_number_claim_company_status_index');
        });

        Schema::table('messaging_onboarding_sessions', function (Blueprint $table): void {
            $table->foreignId('messaging_number_claim_id')->nullable()
                ->after('messaging_connection_id')
                ->constrained('messaging_number_claims')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messaging_onboarding_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('messaging_number_claim_id');
        });

        Schema::dropIfExists('messaging_number_claims');

        Schema::table('messaging_phone_numbers', function (Blueprint $table): void {
            $table->dropUnique('messaging_phone_e164_unique');
            $table->dropColumn('phone_e164');
        });
    }
};
