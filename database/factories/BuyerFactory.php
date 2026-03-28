<?php

namespace Database\Factories;

use App\Models\Buyer;
use Illuminate\Database\Eloquent\Factories\Factory;

class BuyerFactory extends Factory
{
    protected $model = Buyer::class;

    public function definition(): array
    {
        $allPropertyTypes = ['single_family', 'multi_family', 'commercial', 'land'];
        $allAssetClasses = ['sfr', 'multi_family', 'commercial', 'land'];
        $allStates = [
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
            'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
            'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
            'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
            'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY',
        ];

        $zipCount = fake()->numberBetween(3, 5);
        $zipCodes = [];
        for ($i = 0; $i < $zipCount; $i++) {
            $zipCodes[] = fake()->numerify('#####');
        }

        $stateCount = fake()->numberBetween(2, 4);
        $preferredStates = fake()->randomElements($allStates, $stateCount);

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'max_purchase_price' => fake()->numberBetween(100000, 800000),
            'preferred_property_types' => fake()->randomElements($allPropertyTypes, fake()->numberBetween(1, count($allPropertyTypes))),
            'preferred_zip_codes' => $zipCodes,
            'preferred_states' => $preferredStates,
            'asset_classes' => fake()->randomElements($allAssetClasses, fake()->numberBetween(1, count($allAssetClasses))),
            'buyer_score' => 0,
            'total_deals_closed' => fake()->numberBetween(0, 20),
        ];
    }
}
