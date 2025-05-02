<?php

namespace App\Http\Controllers\API;

use App\Models\Sensor;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Events\NewSensorReading;

class SensorApiController extends Controller
{
    public function store(Request $request, Sensor $sensor)
    {
        // Validación de los datos
        $validated = $request->validate([
            'value' => 'required|numeric',
            'reading_time' => 'nullable|date_format:Y-m-d H:i:s',
            'api_key' => 'required|string' // Para autenticación
        ]);

        // Verificar API key (configurada en tu .env)
        if ($validated['api_key'] !== config('app.api_key')) {
            return response()->json(['error' => 'Unauthorized'], 401);
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
    public function readings(Sensor $sensor)
    {
        try {
            // Get readings with pagination
            $readings = $sensor->readings()
                ->orderBy('reading_time', 'desc')
                ->paginate(15); // Adjust pagination as needed

            return response()->json([
                'sensor' => $sensor,
                'readings' => $readings
            ]);

        } catch (\Exception $e) {
            Log::error("Error fetching sensor readings: " . $e->getMessage());
            return response()->json([
                'error' => 'Error retrieving readings',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}