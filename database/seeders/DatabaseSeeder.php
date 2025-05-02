<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            DeviceTypeSeeder::class,
            SensorTypeSeeder::class,
            AlertRuleSeeder::class,
        ]);

        \App\Models\Classroom::factory(10)->create();
        \App\Models\Device::factory(30)->create();
        \App\Models\Sensor::factory(100)->create();
        \App\Models\SensorReading::factory(1000)->create();
        \App\Models\Alert::factory(200)->create();
        \App\Models\DeviceStatusLog::factory(500)->create();
       
    }
}
