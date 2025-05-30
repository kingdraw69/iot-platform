<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = ['sensor_reading_id', 'alert_rule_id', 'resolved', 'resolved_at'];

    public function sensorReading()
    {
        return $this->belongsTo(SensorReading::class);
    }

    public function alertRule()
    {
        return $this->belongsTo(AlertRule::class);
    }

    public static function sendDangerAlertEmail($alertDetails)
    {
        $emailData = [
            'device' => $alertDetails['device'],
            'location' => $alertDetails['location'],
            'sensor' => $alertDetails['sensor'],
            'message' => $alertDetails['message'],
            'value' => $alertDetails['value'],
        ];

        Mail::send('emails.alert', $emailData, function ($message) use ($alertDetails) {
            $message->to(env('recipient_email'))
                    ->subject('Alerta de Peligro Detectada');
        });
    }
}
