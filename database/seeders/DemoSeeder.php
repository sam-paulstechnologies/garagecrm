<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Plan
        $planId = DB::table('plans')->insertGetId([
            'name' => 'Starter',
            'price_monthly' => 499,
            'price_yearly' => 4990,
            'features' => json_encode(['Jobs','Invoices','WhatsApp updates']),
            'max_users' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Company
        $companyId = DB::table('companies')->insertGetId([
            'name' => 'Demo Garage Co',
            'subdomain' => 'demo',
            'email' => 'owner@demo.test',
            'phone' => '+971500000000',
            'plan_id' => $planId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Admin user
        $userId = DB::table('users')->insertGetId([
            'name' => 'Admin User',
            'email' => 'admin@demo.test',
            'password' => Hash::make('password'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Client
        $clientId = DB::table('clients')->insertGetId([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+971500000001',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Vehicle make/model
        $makeId = DB::table('vehicle_makes')->insertGetId([
            'name' => 'Toyota',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $modelId = DB::table('vehicle_models')->insertGetId([
            'vehicle_make_id' => $makeId,
            'name' => 'Corolla',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Vehicle
        $vehicleId = DB::table('vehicles')->insertGetId([
            'company_id' => $companyId,
            'client_id' => $clientId,
            'vehicle_make_id' => $makeId,
            'vehicle_model_id' => $modelId,
            'vin' => 'JTDB1234567890000',
            'plate_number' => 'ABC-1234',
            'color' => 'White',
            'year' => 2021,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Job
        $jobId = DB::table('jobsheets')->insertGetId([
            'company_id' => $companyId,
            'client_id' => $clientId,
            'vehicle_id' => $vehicleId,
            'title' => 'Oil change + Inspection',
            'description' => 'Routine maintenance',
            'status' => 'received',
            'estimate_total' => 250.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Job Card
        DB::table('job_cards')->insert([
            'jobsheet_id' => $jobId,
            'checklist' => json_encode(['Oil level check','Brake inspection','Tyre pressure']),
            'photos' => json_encode([]),
            'notes' => 'Everything looks good.',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Invoice
        $invoiceId = DB::table('invoices')->insertGetId([
            'company_id' => $companyId,
            'jobsheet_id' => $jobId,
            'number' => 'INV-1001',
            'subtotal' => 200.00,
            'tax' => 10.00,
            'total' => 210.00,
            'status' => 'sent',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('invoice_items')->insert([
            [
                'invoice_id' => $invoiceId,
                'type' => 'labour',
                'description' => 'Oil change labour',
                'qty' => 1,
                'unit_price' => 100.00,
                'amount' => 100.00,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'invoice_id' => $invoiceId,
                'type' => 'part',
                'description' => 'Engine oil 5W-30',
                'qty' => 1,
                'unit_price' => 100.00,
                'amount' => 100.00,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }
}
