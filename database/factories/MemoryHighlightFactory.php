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
    private static int $nextSortOrder = 0;

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
            'sort_order' => self::$nextSortOrder++,
        ];
    }

    public function forRecord(MemoryRecord $memoryRecord, int $sortOrder = 0): static
    {
        return $this
            ->for($memoryRecord, 'memoryRecord')
            ->state(fn (): array => [
                'sort_order' => $sortOrder,
            ]);
    }
}
