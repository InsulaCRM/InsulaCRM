<?php

namespace Database\Factories;

use App\Models\Lead;
use App\Services\CustomFieldService;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-6 months', '-1 day');

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'lead_source' => fake()->randomElement(['cold_call', 'direct_mail', 'website', 'referral', 'driving_for_dollars', 'list_import', 'other']),
            'status' => fake()->randomElement(CustomFieldService::getValidSlugs('lead_status')),
            'temperature' => fake()->randomElement(['hot', 'warm', 'cold']),
            'motivation_score' => fake()->numberBetween(0, 100),
            'do_not_contact' => fake()->boolean(5),
            'notes' => fake()->optional(0.7)->sentence(10),
            'created_at' => $createdAt,
            'updated_at' => fake()->dateTimeBetween($createdAt, 'now'),
        ];
    }
}
