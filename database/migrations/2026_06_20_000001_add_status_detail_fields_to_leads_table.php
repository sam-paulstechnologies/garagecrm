<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'status_sub_status')) {
                $table->string('status_sub_status')->nullable()->after('status');
            }

            if (! Schema::hasColumn('leads', 'status_reason')) {
                $table->text('status_reason')->nullable()->after('status_sub_status');
            }

            if (! Schema::hasColumn('leads', 'follow_up_at')) {
                $table->dateTime('follow_up_at')->nullable()->after('status_reason');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE leads MODIFY status ENUM('new','attempting_contact','contact_on_hold','qualified','disqualified','converted','lost') NULL DEFAULT 'new'");
        }

        DB::table('leads')->where('status', 'converted')->update(['status' => 'qualified']);
        DB::table('leads')->where('status', 'lost')->update(['status' => 'disqualified']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            foreach (['follow_up_at', 'status_reason', 'status_sub_status'] as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
