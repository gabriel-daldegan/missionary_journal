<?php

namespace Database\Factories;

use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MemoryRecord>
 */
class MemoryRecordFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (MemoryRecord $record): void {
            $userIds = collect([
                $record->author_user_id,
                $record->last_edited_by_user_id,
            ])->filter()->unique()->all();

            $record->tenant->users()->syncWithoutDetaching($userIds);
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'author_user_id' => User::factory(),
            'last_edited_by_user_id' => User::factory(),
            'type' => MemoryRecord::TYPE_DIARY,
            'title' => fake()->optional()->sentence(4),
            'body' => fake()->paragraphs(2, true),
            'notes' => fake()->optional()->paragraph(),
            'experience_date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'period_start_date' => null,
            'period_end_date' => null,
            'location_name' => fake()->optional()->city(),
            'people' => [],
            'source' => null,
            'source_metadata' => null,
        ];
    }
}
