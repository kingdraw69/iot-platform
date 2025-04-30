<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'serial_number', 'device_type_id', 'classroom_id', 
        'status', 'ip_address', 'mac_address', 'last_communication'
    ];

    protected $casts = [
        'status' => 'boolean',
        'last_communication' => 'datetime',
    ];

    public function deviceType()
    {
        return $this->belongsTo(DeviceType::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function sensors()
    {
        return $this->hasMany(Sensor::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(DeviceStatusLog::class);
    }
}