<?php

namespace Database\Factories;

use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        $estimatedValue = fake()->numberBetween(80000, 500000);
        $repairEstimate = fake()->numberBetween(5000, 80000);
        $arv = $estimatedValue + $repairEstimate + fake()->numberBetween(10000, 100000);
        $askingPrice = fake()->numberBetween(intval($estimatedValue * 0.6), $estimatedValue);
        $ourOffer = fake()->numberBetween(intval($askingPrice * 0.7), $askingPrice);

        $allDistressMarkers = [
            'tax_delinquent', 'code_violation', 'absentee_owner', 'probate',
            'pre_foreclosure', 'divorce', 'out_of_state_owner', 'utility_shutoff',
            'fire_damage', 'vacant',
        ];
        $distressCount = fake()->numberBetween(0, 3);
        $distressMarkers = $distressCount > 0
            ? fake()->randomElements($allDistressMarkers, $distressCount)
            : [];

        return [
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip_code' => fake()->postcode(),
            'property_type' => fake()->randomElement(['single_family', 'multi_family', 'commercial', 'land', 'other']),
            'bedrooms' => fake()->numberBetween(1, 6),
            'bathrooms' => fake()->numberBetween(1, 4),
            'square_footage' => fake()->numberBetween(800, 4000),
            'year_built' => fake()->numberBetween(1950, 2023),
            'lot_size' => fake()->randomFloat(2, 0.1, 5.0),
            'condition' => fake()->randomElement(['excellent', 'good', 'fair', 'poor', 'distressed']),
            'distress_markers' => $distressMarkers,
            'estimated_value' => $estimatedValue,
            'repair_estimate' => $repairEstimate,
            'after_repair_value' => $arv,
            'asking_price' => $askingPrice,
            'our_offer' => $ourOffer,
            'notes' => fake()->optional(0.5)->sentence(8),
        ];
    }
}
