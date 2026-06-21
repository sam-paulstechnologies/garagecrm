<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('bookings', 'status')) {
            DB::statement(
                "ALTER TABLE bookings MODIFY status ENUM('pending','scheduled','reschedule_required','converted_to_job','lost') NOT NULL DEFAULT 'pending'"
            );
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'reschedule_reason')) {
                $table->text('reschedule_reason')->nullable()->after('lost_reason');
            }

            if (! Schema::hasColumn('bookings', 'reschedule_requested_at')) {
                $table->timestamp('reschedule_requested_at')->nullable()->after('reschedule_reason');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('bookings', 'status')) {
            DB::table('bookings')
                ->where('status', 'reschedule_required')
                ->update(['status' => 'pending']);

            DB::statement(
                "ALTER TABLE bookings MODIFY status ENUM('pending','scheduled','converted_to_job','lost') NOT NULL DEFAULT 'pending'"
            );
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'reschedule_requested_at')) {
                $table->dropColumn('reschedule_requested_at');
            }

            if (Schema::hasColumn('bookings', 'reschedule_reason')) {
                $table->dropColumn('reschedule_reason');
            }
        });
    }
};
