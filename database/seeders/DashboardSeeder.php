<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant\Client;
use App\Models\Tenant\Lead;
use App\Models\Tenant\Booking;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\Invoice;
use App\Models\System\Company;
use Carbon\Carbon;

class DashboardSeeder extends Seeder
{
    public function run()
    {
        // Create a company if it doesn't exist
        $company = Company::firstOrCreate(['id' => 1], [
            'name' => 'Sample Garage',
            'email' => 'admin@samplegarage.com',
            'phone' => '+1234567890',
            'address' => '123 Main St, City, State',
        ]);

        // Create sample users
        $users = [
            ['name' => 'John Admin', 'email' => 'admin@garage.com', 'role' => 'admin'],
            ['name' => 'Mike Mechanic', 'email' => 'mechanic@garage.com', 'role' => 'mechanic'],
            ['name' => 'Sarah Manager', 'email' => 'manager@garage.com', 'role' => 'manager'],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(['email' => $userData['email']], [
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => bcrypt('password'),
                'role' => $userData['role'],
                'company_id' => $company->id,
                'status' => 'active',
            ]);
        }

        // Create sample clients
        $clients = [
            ['name' => 'Alice Johnson', 'email' => 'alice@email.com', 'phone' => '+1234567891'],
            ['name' => 'Bob Smith', 'email' => 'bob@email.com', 'phone' => '+1234567892'],
            ['name' => 'Carol Davis', 'email' => 'carol@email.com', 'phone' => '+1234567893'],
            ['name' => 'David Wilson', 'email' => 'david@email.com', 'phone' => '+1234567894'],
            ['name' => 'Eva Brown', 'email' => 'eva@email.com', 'phone' => '+1234567895'],
        ];

        foreach ($clients as $clientData) {
            Client::firstOrCreate(['email' => $clientData['email']], [
                'name' => $clientData['name'],
                'email' => $clientData['email'],
                'phone' => $clientData['phone'],
                'company_id' => $company->id,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }

        // Create sample leads
        $leadStatuses = ['new', 'attempting_contact', 'contact_on_hold', 'qualified', 'disqualified'];
        $leadSources = ['website', 'referral', 'social_media', 'walk_in', 'phone'];

        for ($i = 0; $i < 15; $i++) {
            Lead::create([
                'name' => 'Lead ' . ($i + 1),
                'email' => 'lead' . ($i + 1) . '@email.com',
                'phone' => '+1234567' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'status' => $leadStatuses[array_rand($leadStatuses)],
                'source' => $leadSources[array_rand($leadSources)],
                'company_id' => $company->id,
                'created_at' => Carbon::now()->subDays(rand(1, 60)),
            ]);
        }

        // Create sample bookings
        $serviceTypes = ['Oil Change', 'Brake Service', 'Engine Repair', 'AC Service', 'Tire Replacement'];
        $slots = ['Morning', 'Afternoon', 'Evening'];

        for ($i = 0; $i < 20; $i++) {
            $client = Client::inRandomOrder()->first();
            Booking::create([
                'client_id' => $client->id,
                'service_type' => $serviceTypes[array_rand($serviceTypes)],
                'date' => Carbon::now()->addDays(rand(-30, 30)),
                'slot' => $slots[array_rand($slots)],
                'status' => 'confirmed',
                'company_id' => $company->id,
                'created_at' => Carbon::now()->subDays(rand(1, 45)),
            ]);
        }

        // Create sample opportunities
        $stages = ['new', 'attempting_contact', 'appointment', 'offer', 'closed_won', 'closed_lost'];
        $priorities = ['low', 'medium', 'high'];

        for ($i = 0; $i < 12; $i++) {
            $client = Client::inRandomOrder()->first();
            Opportunity::create([
                'client_id' => $client->id,
                'title' => 'Service Opportunity ' . ($i + 1),
                'stage' => $stages[array_rand($stages)],
                'value' => rand(100, 2000),
                'priority' => $priorities[array_rand($priorities)],
                'service_type' => $serviceTypes[array_rand($serviceTypes)],
                'company_id' => $company->id,
                'created_at' => Carbon::now()->subDays(rand(1, 40)),
            ]);
        }

        // Create sample invoices
        for ($i = 0; $i < 25; $i++) {
            $client = Client::inRandomOrder()->first();
            Invoice::create([
                'client_id' => $client->id,
                'amount' => rand(50, 1500),
                'status' => ['pending', 'paid', 'overdue'][array_rand([0, 1, 2])],
                'due_date' => Carbon::now()->addDays(rand(-10, 30)),
                'company_id' => $company->id,
                'created_at' => Carbon::now()->subDays(rand(1, 35)),
            ]);
        }

        $this->command->info('Dashboard sample data created successfully!');
        $this->command->info('You can now login with: admin@garage.com / password');
    }
} 