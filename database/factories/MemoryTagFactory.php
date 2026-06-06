<?php

namespace Database\Factories;

use App\Models\MemoryTag;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MemoryTag>
 */
class MemoryTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
        ];
    }
}
