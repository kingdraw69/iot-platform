<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reading = \App\Models\SensorReading::factory()->create();
        $rule = \App\Models\AlertRule::factory()->create(['sensor_type_id' => $reading->sensor->sensor_type_id]);
        return [
            // 'sensor_id' => $reading->sensor_id,
            'sensor_reading_id' => $reading->id,
            'alert_rule_id' => $rule->id,
            'resolved' => $this->faker->boolean(30),
            'resolved_at' => $this->faker->optional()->dateTimeThisMonth,
        ];
    }
}
