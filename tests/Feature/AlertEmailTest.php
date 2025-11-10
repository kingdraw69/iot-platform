<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\SensorReading;
use App\Models\Alert;
use App\Mail\DangerAlertMail;

class AlertEmailTest extends TestCase
{
    use RefreshDatabase;

    // Nota: la conexión de base de datos para tests se controla desde .env.testing o phpunit.xml

    public function testDangerAlertEmailIsSent()
    {
        Mail::fake();

        $sensorReading = SensorReading::factory()->create([
            'value' => 100,
        ]);

        $alertDetails = [
            'device' => $sensorReading->sensor->device->name,
            'location' => $sensorReading->sensor->device->classroom->name,
            'sensor' => $sensorReading->sensor->name,
            'alert_message' => 'Valor fuera de rango',
            'value' => $sensorReading->value,
        ];

        $sent = Alert::sendDangerAlertEmail($alertDetails);

        // Asegurarnos de que la función intente enviar el correo (devuelve true cuando lo logra)
        $this->assertTrue($sent);
    }
}
