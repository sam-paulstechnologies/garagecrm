<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\System\Company;
use App\Models\System\Garage;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use App\Models\Job\Job;
use App\Models\Job\Invoice;
use Carbon\Carbon;

class TestSeed extends Seeder
{
    public function run(): void
    {
        // Companies
        $c1 = Company::firstOrCreate(['id'=>1], ['name'=>'Acme Auto','slug'=>'acme']);
        $c2 = Company::firstOrCreate(['id'=>2], ['name'=>'Beta Garage','slug'=>'beta']);

        // Garages
        $g1 = Garage::firstOrCreate(['company_id'=>$c1->id,'name'=>'Acme Main'], ['is_active'=>1]);
        $g2 = Garage::firstOrCreate(['company_id'=>$c2->id,'name'=>'Beta Main'], ['is_active'=>1]);

        // Admin users
        $admin1 = User::firstOrCreate(
            ['email'=>'admin1@example.com'],
            [
                'name'=>'Admin One','password'=>Hash::make('password'),
                'company_id'=>$c1->id,'garage_id'=>$g1->id,'role'=>'admin','status'=>1
            ]
        );
        $admin2 = User::firstOrCreate(
            ['email'=>'admin2@example.com'],
            [
                'name'=>'Admin Two','password'=>Hash::make('password'),
                'company_id'=>$c2->id,'garage_id'=>$g2->id,'role'=>'admin','status'=>1
            ]
        );

        // Company 1 test data
        $client = Client::firstOrCreate(
            ['company_id'=>$c1->id,'email'=>'jane@example.com'],
            ['name'=>'Jane Doe','phone'=>'9990001111','location'=>'HYD','source'=>'seed']
        );

        $lead = Lead::firstOrCreate(
            ['company_id'=>$c1->id,'email'=>'lead@example.com'],
            ['name'=>'Lead A','phone'=>'9990002222','status'=>'new','source'=>'seed','client_id'=>$client->id]
        );

        $opp = Opportunity::firstOrCreate(
            ['company_id'=>$c1->id,'client_id'=>$client->id,'title'=>'AC Service'],
            ['stage'=>'new','expected_value'=>2500]
        );

        $booking = Booking::firstOrCreate(
            ['company_id'=>$c1->id,'client_id'=>$client->id,'opportunity_id'=>$opp->id],
            ['assigned_to'=>$admin1->id,'scheduled_at'=>Carbon::now()->addDay(),'status'=>'scheduled']
        );

        $job = Job::firstOrCreate(
            ['company_id'=>$c1->id,'client_id'=>$client->id,'booking_id'=>$booking->id],
            ['assigned_to'=>$admin1->id,'status'=>'in_progress','description'=>'Diagnose AC cooling']
        );

        Invoice::firstOrCreate(
            ['company_id'=>$c1->id,'job_id'=>$job->id,'client_id'=>$client->id],
            ['amount'=>3500,'status'=>'pending','due_date'=>Carbon::now()->addDays(7),'invoice_number'=>'INV-1001']
        );

        // Company 2 minimal record (for tenant isolation)
        Client::firstOrCreate(
            ['company_id'=>$c2->id,'email'=>'otherco@example.com'],
            ['name'=>'Other Co Client','phone'=>'8887776666','location'=>'DXB','source'=>'seed']
        );
    }
}
