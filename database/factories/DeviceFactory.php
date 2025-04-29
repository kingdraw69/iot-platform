<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            /** 
            *    'name' => $this->faker->unique()->word(),
            *    'serial_number' => $this->faker->unique()->numerify('SN-#####'),
            *    'classroom_id' => \App\Models\Classroom::factory(), // Create a new classroom for each device
            *    'device_type_id' => \App\Models\DeviceType::factory(), // Create a new device type for each device
            *    'status' => $this->faker->randomElement(['active', 'inactive']),
            *    'ip_address' => $this->faker->ipv4(),
            *    'mac_address' => $this->faker->unique()->macAddress(),
            *    'last_communication' => $this->faker->dateTimeBetween('-1 year', 'now'),
            */
            'name' => 'Dispositivo ' . $this->faker->word,
            'serial_number' => $this->faker->unique()->uuid,
            'device_type_id' => \App\Models\DeviceType::factory(),
            'classroom_id' => \App\Models\Classroom::factory(),
            'status' => $this->faker->boolean(90), // 90% de probabilidad de estar activo
            'ip_address' => $this->faker->ipv4,
            'mac_address' => $this->faker->macAddress,
            'last_communication' => $this->faker->dateTimeThisMonth,
        ];
    }
}
