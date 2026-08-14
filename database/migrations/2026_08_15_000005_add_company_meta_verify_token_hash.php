<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('companies') && ! Schema::hasColumn('companies', 'meta_verify_token_hash')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->string('meta_verify_token_hash', 64)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'meta_verify_token_hash')) {
            Schema::table('companies', function (Blueprint $table): void {
                $table->dropColumn('meta_verify_token_hash');
            });
        }
    }
};
