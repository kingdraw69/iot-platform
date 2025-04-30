<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'device_id', 'sensor_type_id', 'status'];
    
    protected $casts = [
        'status' => 'boolean'
    ];

    // Relación con el dispositivo
    public function device()
    {
        return $this->belongsTo(Device::class)->with('classroom');
    }

    // Relación con el tipo de sensor
    public function sensorType()
    {
        return $this->belongsTo(SensorType::class);
    }

    // Relación con las lecturas
    public function readings()
    {
        return $this->hasMany(SensorReading::class)->orderBy('reading_time', 'desc');
    }
}
