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
        return [
            'sensor_type_id' => \App\Models\SensorType::factory()->create()->id,
            'min_value' => $this->faker->unique()->randomFloat(2, 0, 50), // Generar valores únicos
            'max_value' => $this->faker->unique()->randomFloat(2, 51, 100),
            'severity' => $this->faker->randomElement(['info', 'warning', 'danger']),
            'message' => $this->faker->unique()->sentence(),
        ];
    }
}
