<?php

use App\Models\Device;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/dashboard', function () {
    $devices = Device::with('locations')->get();
    return view('dashboard', compact('devices'));
});
