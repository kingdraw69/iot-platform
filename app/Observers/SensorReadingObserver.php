<?php

namespace App\Observers;

use App\Models\SensorReading;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;

class SensorReadingObserver
{
    public function created(SensorReading $sensorReading)
    {
        $triggeredRules = $sensorReading->checkForAlert();

        if ($triggeredRules->isEmpty()) {
            return;
        }

        foreach ($triggeredRules as $rule) {
            $device = $sensorReading->sensor->device;
            $location = $device && $device->classroom ? $device->classroom->name : 'Ubicación desconocida';

            $alertDetails = [
                'device' => $device?->name ?? 'Dispositivo desconocido',
                'location' => $location,
                'sensor' => $sensorReading->sensor->name,
                'alert_message' => $rule->message,
                'value' => $sensorReading->value,
            ];

            Alert::sendDangerAlertEmail($alertDetails);

            Log::info('Alerta activada para la lectura del sensor: ' . $sensorReading->id);
        }
    }
}
