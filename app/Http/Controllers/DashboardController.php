<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Alert;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalDevices = Device::count();
        $activeDevices = Device::where('status', true)->count();
        $activeAlerts = Alert::where('resolved', false)->count();
        $latestReadings = \App\Models\SensorReading::with(['sensor.sensorType', 'sensor.device.classroom'])
            ->orderBy('reading_time', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'totalDevices', 
            'activeDevices', 
            'activeAlerts',
            'latestReadings'
        ));
    }
}