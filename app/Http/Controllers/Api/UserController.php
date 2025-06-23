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
     * @return JsonResponse
     */
    public function logout()
    {
        Auth::logout();
        return AppHelpers::apiResponse([]);
    }
}
