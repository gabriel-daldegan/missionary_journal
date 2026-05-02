<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\PlanPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanPrice>
 */
class PlanPriceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_id' => null,
            'price' => 0,
            'currency_id' => Currency::factory(),
        ];
    }
}
