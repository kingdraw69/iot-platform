<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use App\Models\SensorReading;
use App\Models\Alert;

class AlertEmailTest extends TestCase
{
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

        Mail::assertSent(function ($mail) use ($alertDetails) {
            return $mail->hasTo(env('recipient_email')) &&
                   $mail->subject === 'Alerta de Peligro Detectada';
        });
    }
}
