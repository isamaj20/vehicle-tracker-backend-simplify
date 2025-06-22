<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;


Route::controller(UserController::class)->group(function () {
    Route::post('/login', 'login');//login to get api token
    Route::post('register', 'register'); //signup
    Route::post('logout', 'logout'); //sign-out
    Route::get('/dashboard', 'dashboard')->middleware('auth:api');//FRONTEND: get dashboard report from server
    Route::post('/new-device', 'addDevice')->middleware('auth:api');//add device
    Route::post('/device/command/{device_id}', 'sendCommand')->middleware('auth:api');//FRONTEND: send command from server
    Route::get('/device/{device_id}', 'getDevice');
});
Route::controller(DeviceController::class)->group(function () {
    Route::post('/device', 'report');// DEVICE: send device report to server
    Route::get('/device/command/{device_id}', 'command');// DEVICE: get command from server
});
