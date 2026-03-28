<?php

namespace Database\Factories;

use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;

class DealFactory extends Factory
{
    protected $model = Deal::class;

    public function definition(): array
    {
        $stages = array_keys(Deal::stages());
        $stage = fake()->randomElement($stages);
        $contractPrice = fake()->numberBetween(50000, 300000);
        $assignmentFee = fake()->numberBetween(3000, 25000);
        $createdAt = fake()->dateTimeBetween('-5 months', '-3 days');
        $stageChangedAt = fake()->dateTimeBetween($createdAt, 'now');

        return [
            'title' => fake()->streetAddress() . ' Deal',
            'stage' => $stage,
            'stage_changed_at' => $stageChangedAt,
            'contract_price' => $contractPrice,
            'assignment_fee' => $assignmentFee,
            'contract_date' => in_array($stage, ['under_contract', 'closing', 'closed_won', 'assigned', 'dispositions']) ? fake()->dateTimeBetween($createdAt, 'now') : null,
            'closing_date' => in_array($stage, ['closed_won', 'closing']) ? fake()->dateTimeBetween('now', '+30 days') : null,
            'notes' => fake()->optional(0.5)->sentence(8),
            'created_at' => $createdAt,
            'updated_at' => $stageChangedAt,
        ];
    }
}
