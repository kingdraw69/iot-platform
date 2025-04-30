<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\DeviceApiController;
use App\Http\Controllers\API\SensorApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API para dispositivos
Route::prefix('devices')->group(function () {
    Route::get('/', [DeviceApiController::class, 'index']);
    Route::get('/{device}', [DeviceApiController::class, 'show']);
    Route::post('/{device}/status', [DeviceApiController::class, 'updateStatus']);
});

// API para sensores
Route::prefix('sensors')->group(function () {
    Route::get('/{sensor}/readings', [SensorApiController::class, 'readings']);
    Route::post('/{sensor}/readings', [SensorApiController::class, 'storeReading']);
});

Route::post('/sensors/{sensor}/readings', [SensorDataController::class, 'store'])
    ->name('api.sensors.readings.store');