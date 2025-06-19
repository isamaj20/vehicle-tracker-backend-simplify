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
     * @param Request $request
     * @return JsonResponse
     */
    public function addDevice(Request $request)
    {
        try {
            $request->validate([
                'device_id' => 'required|string|unique:devices,device_id',
                'device_name' => ['required', 'string',
                    Rule::unique('devices')->where(function ($query) use ($request) {
                        return $query->where('user_id', auth()->id());
                    }),
                ],
                'sim_number' => 'required|string|max:14',
            ]);

            $device = Device::create([
                'device_id' => $request->device_id,
                'device_name' => $request->device_name,
                'user_id' => auth()->id(),
                'sim_number' => $request->sim_number,
                'ignition' => true,
            ]);
            return AppHelpers::apiResponse($device, false, 200, 'Successfully added ' . $request['device_name']);

        } catch (ValidationException $e) {
            return AppHelpers::apiResponse(
                [],
                true,
                422,
                'Validation error',
                $e->errors()  // returns the detailed errors as array
            );

        } catch (\Throwable $error) {
            return AppHelpers::apiResponse([], true, 500, $error->getMessage());
        }
    }

    /**
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

    /**
     * @param Request $request
     * @param $device_id
     * @return mixed
     */
    public function sendCommand(Request $request, $device_id)
    {
        $request->validate([
            'command' => 'required|string|in:#KILL,#START,#STATUS,#INTERVAL',
        ]);

        $device = Device::where('device_id', $device_id)->first();
        if (!$device) return response()->json(['error' => 'Device not found'], 404);

        $device->commands()->create([
            'command' => strtoupper($request->command),
        ]);

        return response()->json(['status' => 'command queued']);
    }
}
