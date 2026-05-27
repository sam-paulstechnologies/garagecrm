<?php

namespace Database\Seeders;

use App\Models\System\Company;
use App\Models\Tenant\Booking;
use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Lead;
use App\Models\Tenant\Opportunity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $this->abortIfUnsafeEnvironment();

        $demoPassword = $this->generateDemoPassword();

        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */
        $company = Company::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Sample Garage',
                'email' => 'admin@samplegarage.example',
                'phone' => '+971500000000',
                'address' => 'Sample Address, Dubai',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Users
        |--------------------------------------------------------------------------
        | Local/testing only. Never seeded in production.
        |--------------------------------------------------------------------------
        */
        $users = [
            [
                'name' => 'John Admin',
                'email' => 'admin@garage.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Mike Mechanic',
                'email' => 'mechanic@garage.test',
                'role' => 'mechanic',
            ],
            [
                'name' => 'Sarah Manager',
                'email' => 'manager@garage.test',
                'role' => 'manager',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => Hash::make($demoPassword),
                    'role' => $userData['role'],
                    'company_id' => $company->id,
                    'status' => 'active',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Demo Clients
        |--------------------------------------------------------------------------
        */
        $clients = [
            ['name' => 'Alice Johnson', 'email' => 'alice@example.test', 'phone' => '+971500000001'],
            ['name' => 'Bob Smith', 'email' => 'bob@example.test', 'phone' => '+971500000002'],
            ['name' => 'Carol Davis', 'email' => 'carol@example.test', 'phone' => '+971500000003'],
            ['name' => 'David Wilson', 'email' => 'david@example.test', 'phone' => '+971500000004'],
            ['name' => 'Eva Brown', 'email' => 'eva@example.test', 'phone' => '+971500000005'],
        ];

        foreach ($clients as $clientData) {
            Client::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'email' => $clientData['email'],
                ],
                [
                    'name' => $clientData['name'],
                    'email' => $clientData['email'],
                    'phone' => $clientData['phone'],
                    'company_id' => $company->id,
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Demo Leads
        |--------------------------------------------------------------------------
        */
        $leadStatuses = ['new', 'attempting_contact', 'contact_on_hold', 'qualified', 'disqualified'];
        $leadSources = ['website', 'referral', 'social_media', 'walk_in', 'phone'];

        for ($i = 0; $i < 15; $i++) {
            Lead::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'email' => 'lead' . ($i + 1) . '@example.test',
                ],
                [
                    'name' => 'Lead ' . ($i + 1),
                    'email' => 'lead' . ($i + 1) . '@example.test',
                    'phone' => '+971500001' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'status' => $leadStatuses[array_rand($leadStatuses)],
                    'source' => $leadSources[array_rand($leadSources)],
                    'company_id' => $company->id,
                    'created_at' => Carbon::now()->subDays(rand(1, 60)),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Demo Bookings
        |--------------------------------------------------------------------------
        */
        $serviceTypes = ['Oil Change', 'Brake Service', 'Engine Repair', 'AC Service', 'Tire Replacement'];
        $slots = ['Morning', 'Afternoon', 'Evening'];

        for ($i = 0; $i < 20; $i++) {
            $client = Client::where('company_id', $company->id)
                ->inRandomOrder()
                ->first();

            if (! $client) {
                continue;
            }

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

        /*
        |--------------------------------------------------------------------------
        | Demo Opportunities
        |--------------------------------------------------------------------------
        */
        $stages = ['new', 'attempting_contact', 'appointment', 'offer', 'closed_won', 'closed_lost'];
        $priorities = ['low', 'medium', 'high'];

        for ($i = 0; $i < 12; $i++) {
            $client = Client::where('company_id', $company->id)
                ->inRandomOrder()
                ->first();

            if (! $client) {
                continue;
            }

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

        /*
        |--------------------------------------------------------------------------
        | Demo Invoices
        |--------------------------------------------------------------------------
        */
        for ($i = 0; $i < 25; $i++) {
            $client = Client::where('company_id', $company->id)
                ->inRandomOrder()
                ->first();

            if (! $client) {
                continue;
            }

            Invoice::create([
                'client_id' => $client->id,
                'amount' => rand(50, 1500),
                'status' => ['pending', 'paid', 'overdue'][array_rand(['pending', 'paid', 'overdue'])],
                'due_date' => Carbon::now()->addDays(rand(-10, 30)),
                'company_id' => $company->id,
                'created_at' => Carbon::now()->subDays(rand(1, 35)),
            ]);
        }

        $this->command?->info('Dashboard sample data created successfully.');
        $this->command?->warn('Local/testing demo users created:');
        $this->command?->line('admin@garage.test');
        $this->command?->line('mechanic@garage.test');
        $this->command?->line('manager@garage.test');
        $this->command?->warn('Generated demo password for newly created users only: ' . $demoPassword);
    }

    protected function abortIfUnsafeEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('DashboardSeeder is blocked outside local/testing environments.');
        }
    }

    protected function generateDemoPassword(): string
    {
        return Str::password(18);
    }
}