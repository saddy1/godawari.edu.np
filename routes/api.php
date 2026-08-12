<?php

use App\Http\Controllers\Hajiri\DeviceController;
use App\Http\Controllers\Hr\MemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/hr/municipalities-by-district/{district}', [MemberController::class, 'getMunicipalitiesByDistrict']);

Route::post('/hajiri/attendance/sync', [DeviceController::class, 'sync_api'])
    ->middleware('throttle:10,1')
    ->name('api.hajiri.attendance.sync');
