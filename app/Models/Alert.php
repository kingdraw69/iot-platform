<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
