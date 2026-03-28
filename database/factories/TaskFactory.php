<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'title' => fake()->randomElement([
                'Follow up call',
                'Send contract',
                'Schedule property inspection',
                'Review comps',
                'Send offer letter',
                'Follow up on counter offer',
                'Coordinate with title company',
                'Check inspection results',
            ]),
            'due_date' => fake()->dateTimeBetween($createdAt, '+14 days'),
            'is_completed' => fake()->boolean(30),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
