<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SensorReading>
 */
class SensorReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sensor = \App\Models\Sensor::factory()->create();
        $sensorType = $sensor->sensorType;
        return [
            'sensor_id' => $sensor->id,
            'value' => $this->faker->randomFloat(2, $sensorType->min_range, $sensorType->max_range),
            'reading_time' => $this->faker->dateTimeThisMonth,
        ];
    }
}
