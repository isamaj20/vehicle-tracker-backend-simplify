<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public $userId; // 👈 Add this public property

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if userId is set
        if (!$this->userId) {
            throw new \Exception("userId not set in DeviceSeeder");
        }

        $device = Device::create([
            'device_id' => 'DEV12345',
            'device_name' =>'406 Tracker',
            'user_id' => $this->userId,
            'sim_number' => '+23480123456',
            'ignition' => true,
        ]);

        // Add multiple location logs
        for ($i = 0; $i < 5; $i++) {
            Location::create([
                'device_id' => $device->device_id,
                'lat' => 6.5244 + $i * 0.001, // Lagos-like coordinates
                'lon' => 3.3792 + $i * 0.001,
                'speed' => rand(10, 100),
                'recorded_at' => now()->subMinutes($i * 2),
            ]);
        }

        // Add a queued command
        DeviceCommand::create([
            'device_id' => $device->device_id,
            'command' => '#STOP',
            'executed' => false,
        ]);
    }
}
