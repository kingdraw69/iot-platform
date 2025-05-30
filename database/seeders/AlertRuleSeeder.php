<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AlertRule;
use App\Models\SensorType;

class AlertRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reglas para temperatura
        $temp = SensorType::where('name', 'Temperatura')->first();
        AlertRule::create([
            'name' => 'Temperatura Alta',
            'sensor_type_id' => $temp->id,
            'min_value' => null,
            'max_value' => 30,
            'severity' => 'warning',
            'message' => 'Temperatura alta en el aula',
        ]);
        AlertRule::create([
            'name' => 'Temperatura Baja',
            'sensor_type_id' => $temp->id,
            'min_value' => 10,
            'max_value' => null,
            'severity' => 'warning',
            'message' => 'Temperatura baja en el aula',
        ]);

        // Reglas para humedad
        $humidity = SensorType::where('name', 'Humedad')->first();
        AlertRule::create([
            'name' => 'Humedad Alta',
            'sensor_type_id' => $humidity->id,
            'min_value' => null,
            'max_value' => 70,
            'severity' => 'warning',
            'message' => 'Humedad alta en el aula',
        ]);
        AlertRule::create([
            'name' => 'Humedad Baja',
            'sensor_type_id' => $humidity->id,
            'min_value' => 30,
            'max_value' => null,
            'severity' => 'warning',
            'message' => 'Humedad baja en el aula',
        ]);
        AlertRule::create([
            'name' => 'Humedad Baja Crítica',
            'message' => 'La humedad está por debajo del nivel mínimo.',
            'sensor_type_id' => $humidity->id,
        ]);

        // Reglas para monóxido de carbono
        $co = SensorType::where('name', 'Monóxido de Carbono')->first();
        AlertRule::create([
            'name' => 'Nivel Peligroso de Monóxido de Carbono',
            'sensor_type_id' => $co->id,
            'min_value' => null,
            'max_value' => 50,
            'severity' => 'danger',
            'message' => 'Nivel peligroso de monóxido de carbono',
        ]);
        AlertRule::create([
            'name' => 'Nivel Moderado de Monóxido de Carbono',
            'sensor_type_id' => $co->id,
            'min_value' => 10,
            'max_value' => null,
            'severity' => 'warning',
            'message' => 'Nivel moderado de monóxido de carbono',
        ]);

        // Reglas para humo
        $smoke = SensorType::where('name', 'Humo')->first();
        AlertRule::create([
            'sensor_type_id' => $smoke->id,
            'min_value' => null,
            'max_value' => 50,
            'severity' => 'danger',
            'message' => 'Detección de humo en el aula - Posible incendio'
        ]);

        // Reglas para oxígeno
        $oxygen = SensorType::where('name', 'Oxígeno')->first();
        AlertRule::create([
            'sensor_type_id' => $oxygen->id,
            'min_value' => null,
            'max_value' => 19.5,
            'severity' => 'danger',
            'message' => 'Niveles bajos de oxígeno en el aula'
        ]);

        // Reglas para vibración
        $vibration = SensorType::where('name', 'Vibración')->first();
        AlertRule::create([
            'sensor_type_id' => $vibration->id,
            'min_value' => null,
            'max_value' => 2,
            'severity' => 'warning',
            'message' => 'Vibraciones inusuales detectadas'
        ]);
        AlertRule::create([
            'sensor_type_id' => $vibration->id,
            'min_value' => null,
            'max_value' => 4,
            'severity' => 'danger',
            'message' => 'Vibraciones fuertes detectadas - Posible terremoto'
        ]);

        // Reglas para presión atmosférica
        $pressure = SensorType::where('name', 'Presión Atmosférica')->first();
        AlertRule::create([
            'sensor_type_id' => $pressure->id,
            'min_value' => null,
            'max_value' => 1010,
            'severity' => 'info',
            'message' => 'Presión atmosférica baja'
        ]);
        AlertRule::create([
            'sensor_type_id' => $pressure->id,
            'min_value' => 1020,
            'max_value' => null,
            'severity' => 'info',
            'message' => 'Presión atmosférica alta'
        ]);

        // Reglas adicionales
        AlertRule::create([
            'name' => 'Movimiento Detectado',
            'message' => 'Se ha detectado movimiento en el área.',
            'sensor_type_id' => $vibration->id,
        ]);
    }
}
