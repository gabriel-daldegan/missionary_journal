<?php

namespace Database\Factories;

use App\Models\OneTimeProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OneTimeProduct>
 */
class OneTimeProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'description' => fake()->sentence(),
            'slug' => fake()->slug(),
            'features' => [],
        ];
    }
}
