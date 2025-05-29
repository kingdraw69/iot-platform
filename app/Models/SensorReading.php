<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    use HasFactory;

    protected $fillable = ['sensor_id', 'value', 'reading_time'];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function checkForAlert()
    {
        $alertRule = AlertRule::where('sensor_type_id', $this->sensor->sensor_type_id)->first();

        if ($alertRule) {
            if ($this->value < $alertRule->min_value || $this->value > $alertRule->max_value) {
                Alert::create([
                    'sensor_reading_id' => $this->id,
                    'alert_rule_id' => $alertRule->id,
                    'resolved' => false,
                ]);
            }
        }
    }
}
