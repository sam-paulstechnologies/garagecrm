<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('opportunities') || ! Schema::hasColumn('opportunities', 'stage')) {
            return;
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE opportunities MODIFY stage ENUM('new','attempting_contact','collecting_details','appointment','offer','manager_confirmation_pending','booking_confirmed','closed_won','closed_lost') NULL DEFAULT 'new'"
            );
        }

        DB::table('opportunities')
            ->where('stage', 'closed_won')
            ->update(['stage' => 'booking_confirmed']);

        DB::table('opportunities')
            ->where('stage', 'collecting_details')
            ->update(['stage' => 'attempting_contact']);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE opportunities MODIFY stage ENUM('new','attempting_contact','appointment','offer','manager_confirmation_pending','booking_confirmed','closed_lost') NULL DEFAULT 'new'"
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('opportunities') || ! Schema::hasColumn('opportunities', 'stage')) {
            return;
        }

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE opportunities MODIFY stage ENUM('new','attempting_contact','collecting_details','appointment','offer','manager_confirmation_pending','booking_confirmed','closed_won','closed_lost') NULL DEFAULT 'new'"
            );
        }

        DB::table('opportunities')
            ->where('stage', 'booking_confirmed')
            ->update(['stage' => 'closed_won']);

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE opportunities MODIFY stage ENUM('new','attempting_contact','collecting_details','manager_confirmation_pending','appointment','offer','closed_won','closed_lost') NULL DEFAULT 'new'"
            );
        }
    }
};
