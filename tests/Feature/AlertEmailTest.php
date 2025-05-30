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

    /**
     * Fuerza el uso de la conexión MySQL para este test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Cambiar a conexión MySQL explícitamente
        config()->set('database.default', 'mysql');
    }

    public function testDangerAlertEmailIsSent()
    {
        Mail::fake();

        $sensorReading = SensorReading::factory()->create([
            'value' => 100,
        ]);

        $alertDetails = [
            'device' => $sensorReading->sensor->device->name,
            'location' => $sensorReading->sensor->device->location,
            'sensor' => $sensorReading->sensor->name,
            'message' => 'Valor fuera de rango',
            'value' => $sensorReading->value,
        ];

        Alert::sendDangerAlertEmail($alertDetails);

        Mail::assertSent(function (DangerAlertMail $mail) use ($alertDetails) {
            return $mail->hasTo(env('recipient_email')) &&
                   $mail->subject === 'Alerta de Peligro Detectada';
        });
    }
}
