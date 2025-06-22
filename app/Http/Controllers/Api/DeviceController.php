<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Location;
use App\Utilities\AppHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeviceController extends Controller
{


    /**
     * Receives report from Device and store in the server
     * @param Request $request
     * @return mixed
     */
    public function report(Request $request)
    {
        try {
            $data = $request->validate([
                'device_id' => 'required|string',
                'lat' => 'required|numeric',
                'lon' => 'required|numeric',
                'speed' => 'nullable|numeric',
                'ignition' => 'required|boolean',
                'timestamp' => 'nullable'
            ]);
            $device = Device::firstOrCreate(
                ['device_id' => $data->device_id, 'user_id' => auth()->id()] // Optional: replace with authenticated user
            );

            $device->update(['ignition' => $data->ignition]);

            Location::create([
                'device_id' => $data->device_id,
                'lat' => $data->lat,
                'lon' => $data->lon,
                'speed' => $data->speed,
                'recorded_at' => now(),
            ]);

            return response()->json(['status' => 'success']);
        } catch (ValidationException $e) {

            return response()->json('Validation error: ');

        } catch (\Throwable $error) {
            return AppHelpers::apiResponse([], true, 500, $error->getMessage());
        }
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
