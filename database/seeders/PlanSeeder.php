<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run()
    {
        DB::table("plans")->updateOrInsert(
            ["name" => "Starter"],
            [
                "name" => "Starter",
                "price" => 499,
                "currency" => "AED",
                "whatsapp_limit" => 500,
                "user_limit" => 5,
                "features" => json_encode(["Jobs","Invoices","WhatsApp updates"]),
                "status" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ]
        );

        DB::table("plans")->updateOrInsert(
            ["name" => "Growth"],
            [
                "name" => "Growth",
                "price" => 999,
                "currency" => "AED",
                "whatsapp_limit" => 2000,
                "user_limit" => 15,
                "features" => json_encode(["All Starter","Journeys","Templates"]),
                "status" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ]
        );

        DB::table("plans")->updateOrInsert(
            ["name" => "Pro"],
            [
                "name" => "Pro",
                "price" => 1999,
                "currency" => "AED",
                "whatsapp_limit" => 10000,
                "user_limit" => 50,
                "features" => json_encode(["Everything","Priority support"]),
                "status" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ]
        );
    }
}