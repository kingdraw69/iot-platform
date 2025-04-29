<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AlertRule>
 */
class AlertRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sensorType = \App\Models\SensorType::factory()->create();
        $severities = ['info', 'warning', 'danger'];
        return [
            'sensor_type_id' => $sensorType->id,
            'min_value' => $this->faker->optional()->randomFloat(2, $sensorType->min_range, $sensorType->max_range * 0.8),
            'max_value' => $this->faker->optional()->randomFloat(2, $sensorType->min_range * 1.2, $sensorType->max_range),
            'severity' => $this->faker->randomElement($severities),
            'message' => $this->faker->sentence,
        ];
    }
}
