<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeviceController extends Controller
{


    /**
     * Receives report from Device and store in the server
     * @param Request $request
     * @return mixed
     */
    public function updateReport(Request $request)
    {
        try {
            $request->validate([
                'device_id' => 'required|string',
                'lat' => 'required|numeric',
                'lon' => 'required|numeric',
                'speed' => 'nullable|numeric',
                'ignition' => 'required|string',
                'timestamp' => 'nullable'
            ]);
            $device = Device::where('device_id', $request->device_id)
                ->first();

            if (!$device) {
                return response()->json(['status' => 'Device not found']);
            }
            // Get the latest saved location for this device
            $lastLocation = Location::where('device_id', $request->device_id)
                ->latest()
                ->first();
            $shouldSave = false;
            if (!$lastLocation) {
                // No previous location — save the first one
                $shouldSave = true;
            } else {
                $timeDiff = now()->diffInSeconds($lastLocation->created_at);
                $distance = $this->haversine(
                    $lastLocation->lat,
                    $lastLocation->lon,
                    $request->lat,
                    $request->lon
                );

                // Save if location changed significantly OR 1+ minute passed
                if ($timeDiff > 60 || $distance > 0.05) { // 0.05 km = 50 meters
                    $shouldSave = true;
                }
            }

            if ($shouldSave) {
                $ignition = false;
                if ($request->ignition == 'ON') {
                    $ignition = true;
                }
                $device->update(['ignition' => $ignition]);

                Location::create([
                    'device_id' => $request->device_id,
                    'lat' => $request->lat,
                    'lon' => $request->lon,
                    'speed' => $request->speed,
                    'recorded_at' => now(),
                ]);
            }
            return response()->json(['status' => 'success']);

        } catch (ValidationException $e) {

            return response()->json(['status' => $e->errors()]);

        } catch (\Throwable $error) {
            return response()->json(['status' => $error->getMessage()]);
        }
    }

    /**
     * @param $lat1
     * @param $lon1
     * @param $lat2
     * @param $lon2
     * @return float|int
     */
    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // in km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c; // distance in km
    }

    /**
     * @param $device_id
     * @return mixed
     */
    public function command($device_id)
    {
        $device = Device::where('device_id', $device_id)->first();
        if (!$device) return response()->json(['error' => 'Device not found'], 404);

        $command = DeviceCommand::where('device_id', $device_id)
            ->where('executed', false)->latest()->first();
        if (!$command) return response()->json(['message' => 'No pending command']);

        $command->update(['executed' => true]);

        return response()->json(['command' => $command->command]);
    }

}
