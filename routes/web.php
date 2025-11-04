<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\AlertController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SensorTypeController;
use App\Http\Controllers\DashboardPreferenceController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Auth::routes();

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::middleware('auth')->prefix('dashboard')->group(function () {
    Route::get('preferences', [DashboardPreferenceController::class, 'show'])->name('dashboard.preferences.show');
    Route::post('preferences', [DashboardPreferenceController::class, 'store'])->name('dashboard.preferences.store');
});

// Dispositivos
Route::resource('devices', DeviceController::class);
Route::post('devices/{device}/toggle-status', [DeviceController::class, 'toggleStatus'])->name('devices.toggle-status');
Route::post('/devices/{device}/register-communication', [DeviceController::class, 'registerCommunication'])->name('devices.register-communication');

// Sensores
Route::resource('sensors', SensorController::class);
Route::get('sensors/{sensor}/edit', [SensorController::class, 'edit'])->name('sensors.edit');

// Alertas
Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
Route::get('alerts/unresolved', [AlertController::class, 'unresolved'])->name('alerts.unresolved');
Route::put('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');


Route::get('sensors/{sensor}/download', [SensorController::class, 'downloadReadings'])
    ->name('sensors.download'); 

Route::get('sensors/{sensor}/readings/filter', [SensorController::class, 'getReadingsByDateRange'])
    ->name('sensors.readings.filter');


use App\Http\Controllers\AlertRuleController;

Route::get('alert-rules/create', [AlertRuleController::class, 'create'])->name('alert-rules.create');
Route::post('alert-rules', [AlertRuleController::class, 'store'])->name('alert-rules.store');
Route::delete('alert-rules/{alertRule}', [AlertRuleController::class, 'destroy'])->name('alert-rules.destroy');

// Tipos de Sensores
Route::get('sensor-types/create', [SensorTypeController::class, 'create'])->name('sensor-types.create');
Route::post('sensor-types', [SensorTypeController::class, 'store'])->name('sensor-types.store');
Route::get('sensor-types/{sensorType}/edit', [SensorTypeController::class, 'edit'])->name('sensor-types.edit');
Route::put('sensor-types/{sensorType}', [SensorTypeController::class, 'update'])->name('sensor-types.update');
Route::delete('sensor-types/{sensorType}', [SensorTypeController::class, 'destroy'])->name('sensor-types.destroy');
