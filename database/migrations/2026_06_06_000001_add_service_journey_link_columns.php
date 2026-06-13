<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'lead_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->unsignedBigInteger('lead_id')->nullable()->after('opportunity_id');
                $table->index('lead_id', 'bookings_lead_id_index');
            });
        }

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                if (! Schema::hasColumn('jobs', 'lead_id')) {
                    $table->unsignedBigInteger('lead_id')->nullable()->after('booking_id');
                    $table->index('lead_id', 'jobs_lead_id_index');
                }

                if (! Schema::hasColumn('jobs', 'opportunity_id')) {
                    $table->unsignedBigInteger('opportunity_id')->nullable()->after('lead_id');
                    $table->index('opportunity_id', 'jobs_opportunity_id_index');
                }
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'lead_id')) {
                    $table->unsignedBigInteger('lead_id')->nullable()->after('job_id');
                    $table->index('lead_id', 'invoices_lead_id_index');
                }

                if (! Schema::hasColumn('invoices', 'opportunity_id')) {
                    $table->unsignedBigInteger('opportunity_id')->nullable()->after('lead_id');
                    $table->index('opportunity_id', 'invoices_opportunity_id_index');
                }

                if (! Schema::hasColumn('invoices', 'booking_id')) {
                    $table->unsignedBigInteger('booking_id')->nullable()->after('opportunity_id');
                    $table->index('booking_id', 'invoices_booking_id_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (Schema::hasColumn('invoices', 'booking_id')) {
                    $table->dropIndex('invoices_booking_id_index');
                    $table->dropColumn('booking_id');
                }

                if (Schema::hasColumn('invoices', 'opportunity_id')) {
                    $table->dropIndex('invoices_opportunity_id_index');
                    $table->dropColumn('opportunity_id');
                }

                if (Schema::hasColumn('invoices', 'lead_id')) {
                    $table->dropIndex('invoices_lead_id_index');
                    $table->dropColumn('lead_id');
                }
            });
        }

        if (Schema::hasTable('jobs')) {
            Schema::table('jobs', function (Blueprint $table) {
                if (Schema::hasColumn('jobs', 'opportunity_id')) {
                    $table->dropIndex('jobs_opportunity_id_index');
                    $table->dropColumn('opportunity_id');
                }

                if (Schema::hasColumn('jobs', 'lead_id')) {
                    $table->dropIndex('jobs_lead_id_index');
                    $table->dropColumn('lead_id');
                }
            });
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'lead_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropIndex('bookings_lead_id_index');
                $table->dropColumn('lead_id');
            });
        }
    }
};
