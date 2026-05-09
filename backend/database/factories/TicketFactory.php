<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title'       => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(),
            'priority'    => $this->faker->randomElement(['low', 'medium', 'high', 'critical']),
            'status'      => $this->faker->randomElement(['open', 'in_progress', 'resolved', 'closed']),
            'creator_id'  => User::factory(),
            'assigned_to' => null,
            'due_date'    => $this->faker->optional()->dateTimeBetween('-1 month', '+2 months')?->format('Y-m-d'),
        ];
    }
}
