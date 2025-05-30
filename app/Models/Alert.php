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
        try {
            $emailData = [
                'device' => (string) $alertDetails['device'],
                'location' => (string) $alertDetails['location'],
                'sensor' => (string) $alertDetails['sensor'],
                'message' => (string) $alertDetails['message'],
                'value' => (string) $alertDetails['value'],
            ];

            Mail::send('emails.alert', $emailData, function ($message) {
                $message->to(config('mail.recipient_email'))
                        ->subject('Alerta de Peligro Detectada');
            });

            return true;
        } catch (\Exception $e) {
            \Log::error('Error enviando email de alerta: ' . $e->getMessage());
            return false;
        }
    }
}
