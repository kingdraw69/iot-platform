<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'sensor_type_id', 'min_value', 'max_value', 
        'severity', 'message'
    ];

    public function sensorType()
    {
        return $this->belongsTo(SensorType::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }
}
