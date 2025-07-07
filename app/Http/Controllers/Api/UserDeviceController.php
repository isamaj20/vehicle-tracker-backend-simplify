<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Location;
use App\Utilities\AppHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Nette\Schema\ValidationException;

class UserDeviceController extends Controller
{
    /**
     * User dashboard consist of devices, locations of the device and other details
     * Send device, locations and command to user dashboard
     * @return JsonResponse
     */
    public function dashboard()
    {

        $devices = Device::where('user_id', auth()->id())
            ->with('locations')->get();

        if (!$devices) {
            return AppHelpers::apiResponse([], true, 404, 'No Device found');
        }

        return AppHelpers::apiResponse($devices);

    }

    /**
     * User: send command to  device via the server
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

        DeviceCommand::create([
            'device_id' => $device_id,
            'command' => strtoupper($request->command),
        ]);

        return AppHelpers::apiResponse([], false, 200, 'command queued');
    }

    /**
     * Add your physical device to this system
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
                'device_category_id' => 'string|exists:device_categories,id'
            ]);

            $device = Device::create([
                'device_id' => $request->device_id,
                'device_name' => $request->device_name,
                'user_id' => auth()->id(),
                'sim_number' => $request->sim_number,
                'ignition' => true,
                'device_category_id' => $request->device_category_id
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
     * Get device coordinates
     * @param $device_id
     * @return JsonResponse
     */
    public function getLocation($device_id)
    {
        //Check if device exist
        $device_location = Location::where('device_id', $device_id)
            ->latest()
            ->take(10)
            ->get();
        if (!$device_location) {
            return AppHelpers::apiResponse([], true, 404, 'No location recorded for this device');
        }
        return AppHelpers::apiResponse($device_location);
    }


    /**
     * Remove this device from this platform
     * @param $device_id
     * @return JsonResponse|void
     */
    public function destroy($device_id)
    {
        try {
            if (Device::where('device_id', $device_id)->delete()) {
                return AppHelpers::apiResponse([], false, 200, 'Device removed');
            }
        } catch (\Throwable $e) {
            return AppHelpers::apiResponse([], true, 500, $e->getMessage());
        }

    }
}
