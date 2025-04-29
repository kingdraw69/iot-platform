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
}
