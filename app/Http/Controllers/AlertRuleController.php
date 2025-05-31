<?php

namespace App\Http\Controllers;

use App\Models\AlertRule;
use App\Models\Device;
use App\Models\Sensor;
use App\Models\SensorType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AlertRuleController extends Controller
{
    public function create()
    {
        $sensorTypes = SensorType::all();
        $alertRules = AlertRule::with('sensorType')->get();
        $devices = Device::all();
        $sensors = Sensor::with('device')->get();
        
        return view('alerts.rules.create', compact('sensorTypes', 'alertRules', 'devices', 'sensors'));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'sensor_type_id' => 'required|exists:sensor_types,id',
                'device_id' => 'required|exists:devices,id',
                'min_value' => 'required|numeric',
                'max_value' => 'required|numeric|gt:min_value',
                'severity' => 'required|in:info,warning,danger',
                'message' => 'required|string|max:255'
            ]);

            AlertRule::create($validated);

            return redirect()->route('alert-rules.create')->with('success', 'Regla de alerta creada correctamente');
        } catch (\Exception $e) {
            Log::error('Error al crear regla de alerta: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al crear la regla de alerta')->withInput();
        }
    }

    public function destroy(AlertRule $alertRule)
    {
        try {
            $alertRule->delete();
            return redirect()->back()->with('success', 'Regla de alerta eliminada correctamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar regla de alerta: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar la regla de alerta');
        }
    }

    public function index(Request $request)
    {
        $deviceId = $request->query('device_id');
        $devices = Device::all();

        $alertRules = AlertRule::with(['sensorType', 'device'])
            ->when($deviceId, function ($query) use ($deviceId) {
                $query->where('device_id', $deviceId);
            })
            ->get();

        return view('alerts.rules.index', compact('alertRules', 'devices'));
    }
}