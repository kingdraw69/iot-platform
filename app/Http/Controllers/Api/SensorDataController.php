<?php

namespace App\Http\Controllers\API;

use App\Models\Sensor;
use App\Models\SensorReading;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Events\NewSensorReading;

class SensorDataController extends Controller
{
    public function store(Request $request, Sensor $sensor)
    {
        // Validación de los datos
        $validated = $request->validate([
            'value' => 'required|numeric',
            'reading_time' => 'nullable|date_format:Y-m-d H:i:s',
            'api_key' => 'required|string' // Para autenticación
        ]);

        // Validar API_KEY
        if (!$request->has('api_key') || 
            $request->api_key !== $sensor->device->api_key) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'API_KEY inválida o faltante'
            ], 401);
        }

        try {
            // Crear nueva lectura
            $reading = $sensor->readings()->create([
                'value' => $validated['value'],
                'reading_time' => $validated['reading_time'] ?? now()
            ]);

            // Disparar evento para actualización en tiempo real
            event(new NewSensorReading($reading));

            return response()->json([
                'message' => 'Reading saved successfully',
                'reading' => $reading
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error saving sensor reading: " . $e->getMessage());
            return response()->json([
                'error' => 'Error processing reading',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
