<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index()
    {
        $alerts = Alert::with(['sensorReading.sensor.device.classroom', 'alertRule'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('alerts.index', compact('alerts'));
    }

    public function resolve(Alert $alert)
    {
        $alert->update([
            'resolved' => true,
            'resolved_at' => now()
        ]);
        
        return back()->with('success', 'Alerta marcada como resuelta');
    }

    public function unresolved()
    {
        $alerts = Alert::with(['sensorReading.sensor.device.classroom', 'alertRule'])
            ->where('resolved', false)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return view('alerts.unresolved', compact('alerts'));
    }
}