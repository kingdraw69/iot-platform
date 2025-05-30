<?php

namespace App\Observers;

use App\Models\SensorReading;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;

class SensorReadingObserver
{
    public function created(SensorReading $sensorReading)
    {
        $alertRules = $sensorReading->sensor->sensorType->alertRules;

        foreach ($alertRules as $rule) {
            if ($sensorReading->value < $rule->min_value || $sensorReading->value > $rule->max_value) {
                Alert::create([
                    'sensor_reading_id' => $sensorReading->id,
                    'alert_rule_id' => $rule->id,
                    'resolved' => false,
                ]);

                Log::info('Alerta activada para la lectura del sensor: ' . $sensorReading->id);
            }
        }
    }
}
