<?php

namespace Database\Factories;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        $loggedAt = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'type' => fake()->randomElement(['call', 'sms', 'email', 'note', 'meeting']),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(2),
            'logged_at' => $loggedAt,
            'created_at' => $loggedAt,
            'updated_at' => $loggedAt,
        ];
    }
}
