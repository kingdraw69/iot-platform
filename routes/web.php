<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\AlertController;
use Illuminate\Support\Facades\Auth;

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

// Dispositivos
Route::resource('devices', DeviceController::class);
Route::post('devices/{device}/toggle-status', [DeviceController::class, 'toggleStatus'])->name('devices.toggle-status');

// Sensores
Route::resource('sensors', SensorController::class);
Route::get('sensors/{sensor}/edit', [SensorController::class, 'edit'])->name('sensors.edit');

// Alertas
Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
Route::get('alerts/unresolved', [AlertController::class, 'unresolved'])->name('alerts.unresolved');
Route::post('alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');