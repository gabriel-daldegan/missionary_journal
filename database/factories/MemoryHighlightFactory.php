<?php

namespace Database\Factories;

use App\Models\MemoryHighlight;
use App\Models\MemoryRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemoryHighlight>
 */
class MemoryHighlightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'memory_record_id' => MemoryRecord::factory(),
            'text' => fake()->sentence(),
            'sort_order' => fake()->unique()->numberBetween(0, 1_000_000_000),
        ];
    }
}
