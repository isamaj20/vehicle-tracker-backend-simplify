<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserDeviceController;
use Illuminate\Support\Facades\Route;


Route::controller(UserController::class)->group(function () {
    Route::post('/login', 'login');//login to get api token
    Route::post('register', 'register'); //signup
    Route::post('logout', 'logout'); //sign-out
});
Route::controller(UserDeviceController::class)
    ->middleware(['auth:api'])
    ->group(function () {
        Route::get('/dashboard', 'dashboard');//FRONTEND: get dashboard report from server
        Route::post('/new-device', 'addDevice');//add device
        Route::post('/device/command/{device_id}', 'sendCommand');//FRONTEND: send command from server
        Route::get('/device/{device_id}', 'getDevice');//returns a single device
        Route::patch('/device/{device_id}');//update a device
        Route::get('/location/device/{device_id}','getLocation');//get device location coordinates
        Route::delete('/device/{device_id}','destroy'); //remove device
    });
Route::controller(DeviceController::class)->group(function () {
    Route::post('/device', 'updateReport');// DEVICE: send device report to server
    Route::get('/device/command/{device_id}', 'command');// DEVICE: get command from server
});
Route::get('/ping', function () {
    return response()->json(['status' => 'alive']);
});
