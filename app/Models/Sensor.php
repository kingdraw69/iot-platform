<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    use HasFactory;

    protected $fillable = ['device_id', 'sensor_type_id', 'name', 'status'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function sensorType()
    {
        return $this->belongsTo(SensorType::class);
    }

    public function readings()
    {
        return $this->hasMany(SensorReading::class);
    }
}
