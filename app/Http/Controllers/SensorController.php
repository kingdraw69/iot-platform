<?php

namespace App\Http\Controllers;

use App\Models\Sensor;
use App\Models\Device;
use App\Models\SensorType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SensorController extends Controller
{
    public function index()
    {
        $sensors = Sensor::with(['device.classroom', 'sensorType', 'readings'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('sensors.index', compact('sensors'));
    }

    public function create()
    {
        // Obtener dispositivos activos con sus aulas
        $devices = Device::with('classroom')
            ->where('status', true) // Solo dispositivos activos
            ->orderBy('name')
            ->get();
        
        $sensorTypes = SensorType::orderBy('name')->get();

        // Debug: Verificar qué dispositivos se están obteniendo
        Log::debug('Dispositivos disponibles para sensor:', $devices->toArray());

        return view('sensors.create', compact('devices', 'sensorTypes'));
    }

    public function store(Request $request)
    {
        // Validación mejorada
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_id' => [
                'required',
                'exists:devices,id',
                function ($attribute, $value, $fail) {
                    $device = Device::find($value);
                    if (!$device || !$device->status) {
                        $fail('El dispositivo seleccionado no está disponible.');
                    }
                }
            ],
            'sensor_type_id' => 'required|exists:sensor_types,id',
            'status' => 'sometimes|boolean'
        ], [
            'device_id.exists' => 'El dispositivo seleccionado no existe.',
            'sensor_type_id.exists' => 'El tipo de sensor seleccionado no existe.'
        ]);

        try {
            // Crear el sensor con el dispositivo relacionado
            $sensor = new Sensor();
            $sensor->name = $validated['name'];
            $sensor->status = $validated['status'] ?? true;
            
            // Asignar relaciones
            $sensor->device()->associate($validated['device_id']);
            $sensor->sensorType()->associate($validated['sensor_type_id']);
            
            $sensor->save();
            
            return redirect()->route('sensors.index')
                ->with('success', 'Sensor creado exitosamente!');
                
        } catch (\Exception $e) {
            Log::error('Error al crear sensor: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Error al crear el sensor: ' . $e->getMessage());
        }
    }

    public function show(Sensor $sensor)
    {
        $readings = $sensor->readings()
            ->orderBy('reading_time', 'desc')
            ->paginate(10);
            
        return view('sensors.show', compact('sensor', 'readings'));
    }

    public function edit(Sensor $sensor)
    {
        $devices = Device::with('classroom')->get();
        $sensorTypes = SensorType::all();
        return view('sensors.edit', compact('sensor', 'devices', 'sensorTypes'));
    }

    public function update(Request $request, Sensor $sensor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'device_id' => 'required|exists:devices,id',
            'sensor_type_id' => 'required|exists:sensor_types,id',
            'status' => 'boolean',
        ]);

        try {
            $sensor->update($validated);
            
            return redirect()->route('sensors.index')
                ->with('success', 'Sensor actualizado exitosamente');
                
        } catch (\Exception $e) {
            Log::error('Error al actualizar sensor: ' . $e->getMessage());
            return back()->withInput()
                ->with('error', 'Error al actualizar el sensor. Por favor intente nuevamente.');
        }
    }

    public function destroy(Sensor $sensor)
    {
        try {
            $sensor->delete();
            return redirect()->route('sensors.index')
                ->with('success', 'Sensor eliminado exitosamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar sensor: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el sensor. Por favor intente nuevamente.');
        }
    }
}