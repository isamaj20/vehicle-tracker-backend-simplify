<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationMail;
use App\Models\Device;
use App\Models\Location;
use App\Models\User;
use App\Utilities\AppHelpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use mysql_xdevapi\Exception;
use Nette\Schema\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{


    /**
     * Sign-up with user data
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'other_names' => 'nullable|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone_number' => 'required|string|min:11|max:15|unique:users',
                'password' => 'required|string|min:6',
            ]);

            $email_verification_token = bin2hex(strval(rand(1000000, 99999999)));

            $email = $request->email;

            $url = env('APP_URL', "") . "/email-verification?email=" . $email . "&token=" . $email_verification_token;
            $name = $request->first_name . ' ' . $request->other_names . ' ' . $request->last_name;
            $user = User::create([
                'name' => $name,
                'phone_number' => $request->phone_number,
                'email' => $email,
                'email_verification_token' => $email_verification_token,
                'password' => Hash::make($request->password),
            ]);

            Mail::to($request->email)->send(new EmailVerificationMail([
                'email' => $request->email,
                'token' => $email_verification_token,
                "url" => $url,
            ]));

            return AppHelpers::apiResponse($user);

        } catch (ValidationException $e) {
            return AppHelpers::apiResponse([], true, 422, 'Validation error', $e->errors());

        } catch (\Throwable $error) {
            return AppHelpers::apiResponse([], true, 500, $error->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        try {
            //return $request;
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);
            $credentials = $request->only('email', 'password');
            if (!$token = JWTAuth::attempt($credentials)) {
                return AppHelpers::apiResponse([], true, 401, 'Unauthorized');
            }
            $user = Auth::user();
            return AppHelpers::apiResponse([
                'user' => $user,
                'authorization' => [
                    'token' => $token,
                    'type' => 'bearer',
                    'expires-in' => JWTAuth::factory()->getTTL()//expires in 1hr. (60min)
                ]
            ]);
        } catch (ValidationException $e) {
            return AppHelpers::apiResponse([], true, 422, 'Validation error', $e->errors());

        } catch (\Throwable $error) {
            return AppHelpers::apiResponse([], true, 500, $error->getMessage());
        }
    }

    /**
     * User dashboard consist of devices, locations of the each device and other details
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

        $device->commands()->create([
            'command' => strtoupper($request->command),
        ]);

        return response()->json(['status' => 'command queued']);
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
                'device_category_id' => 'string|exists:device_category,id'
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
     * Get device with the passed ID.
     * @param $device_id
     * @return JsonResponse
     */
    public function getDevice($device_id)
    {
        $device = Device::find($device_id);
        if (!$device) {
            return AppHelpers::apiResponse([], true, 404, 'Device not found');
        }
        return AppHelpers::apiResponse($device);
    }

    /**
     * Get device with the passed ID,
     * update the device with the provided details
     * @param Request $request
     * @param $device_id
     * @return JsonResponse
     */
    public function updateDevice(Request $request, $device_id)
    {

        DB::beginTransaction();
        try {
            $request->validate([
                // 'device_id' => 'required|string|unique:devices,device_id',
                'device_name' => ['required', 'string',
                    Rule::unique('devices')->where(function ($query) use ($request) {
                        return $query->where('user_id', auth()->id());
                    }),
                ],
                'sim_number' => 'required|string|max:14',
                'device_category_id' => 'string|exists:device_category,id'
            ]);
            $device = Device::find($device_id);
            if (!$device) {
                return AppHelpers::apiResponse([], true, 404, 'Device not found');
            }

            $data = $request->only(['device_name', 'sim_number', 'device_category_id']);

            //update device details
            $device = Device::where('device_id', $device_id)->update($data);

            DB::commit();
            return AppHelpers::apiResponse($device);

        } catch (ValidationException $e) {

            DB::rollBack();
            return AppHelpers::apiResponse([], true, 422, 'Validation error', $e->errors());

        } catch (\Throwable $error) {
            DB::rollBack();
            return AppHelpers::apiResponse([], true, 500, $error->getMessage());
        }
    }

    /**
     * @param $device_id
     * @return JsonResponse|void
     */
    public function getLocation($device_id){
        //Check if device exist
        $device_location = Location::where('device_id',$device_id)->get();
        if (!$device_location) {
            return AppHelpers::apiResponse([], true, 404, 'No location recorded for this device');
        }
        //
    }

    /**
     * @return JsonResponse
     */
    public function logout()
    {
        Auth::logout();
        return AppHelpers::apiResponse([]);
    }
}
