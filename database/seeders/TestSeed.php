<?php

namespace Database\Seeders;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Job\Invoice;
use App\Models\Job\Job;
use App\Models\System\Company;
use App\Models\System\Garage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestSeed extends Seeder
{
    public function run(): void
    {
        $this->abortIfUnsafeEnvironment();

        $demoPassword = $this->generateDemoPassword();

        /*
        |--------------------------------------------------------------------------
        | Companies
        |--------------------------------------------------------------------------
        */
        $c1 = Company::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Acme Auto',
                'slug' => 'acme',
            ]
        );

        $c2 = Company::firstOrCreate(
            ['id' => 2],
            [
                'name' => 'Beta Garage',
                'slug' => 'beta',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Garages
        |--------------------------------------------------------------------------
        */
        $g1 = Garage::firstOrCreate(
            [
                'company_id' => $c1->id,
                'name' => 'Acme Main',
            ],
            [
                'is_active' => 1,
            ]
        );

        $g2 = Garage::firstOrCreate(
            [
                'company_id' => $c2->id,
                'name' => 'Beta Main',
            ],
            [
                'is_active' => 1,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Admin Users
        |--------------------------------------------------------------------------
        | Local/testing only. Never seeded in production.
        |--------------------------------------------------------------------------
        */
        $admin1 = User::firstOrCreate(
            ['email' => 'admin1@example.test'],
            [
                'name' => 'Admin One',
                'password' => Hash::make($demoPassword),
                'company_id' => $c1->id,
                'garage_id' => $g1->id,
                'role' => 'admin',
                'status' => 1,
            ]
        );

        $admin2 = User::firstOrCreate(
            ['email' => 'admin2@example.test'],
            [
                'name' => 'Admin Two',
                'password' => Hash::make($demoPassword),
                'company_id' => $c2->id,
                'garage_id' => $g2->id,
                'role' => 'admin',
                'status' => 1,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Company 1 Test Data
        |--------------------------------------------------------------------------
        */
        $client = Client::firstOrCreate(
            [
                'company_id' => $c1->id,
                'email' => 'jane@example.test',
            ],
            [
                'name' => 'Jane Doe',
                'phone' => '9990001111',
                'location' => 'HYD',
                'source' => 'seed',
            ]
        );

        $lead = Lead::firstOrCreate(
            [
                'company_id' => $c1->id,
                'email' => 'lead@example.test',
            ],
            [
                'name' => 'Lead A',
                'phone' => '9990002222',
                'status' => 'new',
                'source' => 'seed',
                'client_id' => $client->id,
            ]
        );

        $opp = Opportunity::firstOrCreate(
            [
                'company_id' => $c1->id,
                'client_id' => $client->id,
                'title' => 'AC Service',
            ],
            [
                'stage' => 'new',
                'expected_value' => 2500,
            ]
        );

        $booking = Booking::firstOrCreate(
            [
                'company_id' => $c1->id,
                'client_id' => $client->id,
                'opportunity_id' => $opp->id,
            ],
            [
                'assigned_to' => $admin1->id,
                'scheduled_at' => Carbon::now()->addDay(),
                'status' => 'scheduled',
            ]
        );

        $job = Job::firstOrCreate(
            [
                'company_id' => $c1->id,
                'client_id' => $client->id,
                'booking_id' => $booking->id,
            ],
            [
                'assigned_to' => $admin1->id,
                'status' => 'in_progress',
                'description' => 'Diagnose AC cooling',
            ]
        );

        Invoice::firstOrCreate(
            [
                'company_id' => $c1->id,
                'job_id' => $job->id,
                'client_id' => $client->id,
            ],
            [
                'amount' => 3500,
                'status' => 'pending',
                'due_date' => Carbon::now()->addDays(7),
                'invoice_number' => 'INV-1001',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Company 2 Minimal Record
        |--------------------------------------------------------------------------
        | Used to test tenant isolation.
        |--------------------------------------------------------------------------
        */
        Client::firstOrCreate(
            [
                'company_id' => $c2->id,
                'email' => 'otherco@example.test',
            ],
            [
                'name' => 'Other Co Client',
                'phone' => '8887776666',
                'location' => 'DXB',
                'source' => 'seed',
            ]
        );

        $this->command?->info('Test seed data created successfully.');
        $this->command?->warn('Local/testing demo users created:');
        $this->command?->line('admin1@example.test');
        $this->command?->line('admin2@example.test');
        $this->command?->warn('Generated demo password for newly created users only: ' . $demoPassword);
    }

    protected function abortIfUnsafeEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('TestSeed is blocked outside local/testing environments.');
        }
    }

    protected function generateDemoPassword(): string
    {
        return Str::password(18);
    }
}