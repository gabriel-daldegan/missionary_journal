<?php

namespace Database\Factories;

use App\Models\MemoryWorkspaceSettings;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemoryWorkspaceSettings>
 */
class MemoryWorkspaceSettingsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'collaboration_mode' => MemoryWorkspaceSettings::MODE_SHARED_EDITING,
        ];
    }
}
