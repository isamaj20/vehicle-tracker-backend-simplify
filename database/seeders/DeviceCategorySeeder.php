<?php

namespace Database\Seeders;

use App\Models\DeviceCategory;
use Illuminate\Database\Seeder;

class DeviceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DeviceCategory::create([
            'name' => 'Vehicle Tracker',
            'description' => 'Devices for tracking vehicles'
        ]);
        DeviceCategory::create([
            'name' => 'Smart Meter',
            'description' => 'Remote electricity metering devices'
        ]);

    }
}
