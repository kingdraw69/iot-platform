<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Alert;
use App\Models\SensorType;
use App\Models\SensorReading; // Importar SensorReading
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalDevices = Device::count();
        $activeDevices = Device::where('status', true)->count();
        $activeAlerts = Alert::where('resolved', false)->count(); // Contar alertas activas
        $latestReadings = SensorReading::with(['sensor.sensorType', 'sensor.device.classroom', 'alerts'])
            ->orderBy('reading_time', 'desc')
            ->limit(10)
            ->get();

        $devices = Device::with('classroom')->get();
        $sensorTypes = SensorType::all();

        return view('dashboard', compact('totalDevices', 'activeDevices', 'activeAlerts', 'latestReadings', 'devices', 'sensorTypes'));
    }
    
    public function getSensors(Device $device)
    {
        return response()->json($device->sensors()->with('sensorType')->get());
    }

    public function getSensorReadings($sensorId)
    {
        $sensor = \App\Models\Sensor::findOrFail($sensorId);
        $readings = $sensor->readings()->orderBy('reading_time', 'desc')->limit(100)->get();

        return response()->json($readings);
    }
}