<?php

namespace Database\Seeders;

use App\Services\Vehicles\VehicleMasterDataImporter;
use Illuminate\Database\Seeder;

class VehicleBrandLineSeeder extends Seeder
{
    public function run(): void
    {
        app(VehicleMasterDataImporter::class)->apply(
            base_path('storage/app/private/vehicle_brand_lines.csv')
        );
    }
}
