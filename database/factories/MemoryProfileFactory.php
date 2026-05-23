<?php

namespace Database\Factories;

use App\Models\MemoryProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemoryProfile>
 */
class MemoryProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'display_name' => fake()->name(),
            'preferred_locale' => 'en',
            'mission_context' => fake()->optional()->sentence(),
            'completed_at' => now(),
        ];
    }

    public function incomplete(): static
    {
        return $this->state(fn (array $attributes): array => [
            'completed_at' => null,
        ]);
    }
}
