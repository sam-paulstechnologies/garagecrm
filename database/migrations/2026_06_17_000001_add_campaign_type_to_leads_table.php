<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads') || Schema::hasColumn('leads', 'campaign_type')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'campaign_name')) {
                $table->string('campaign_type')->nullable()->after('campaign_name')->index();

                return;
            }

            $table->string('campaign_type')->nullable()->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads') || ! Schema::hasColumn('leads', 'campaign_type')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('campaign_type');
        });
    }
};
