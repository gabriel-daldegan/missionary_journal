<?php

namespace App\Services;

use App\Models\MemoryWorkspaceSettings;
use App\Models\Tenant;
use App\Models\User;

class MemoryWorkspaceSettingsService
{
    public function resolveForTenant(Tenant $tenant): MemoryWorkspaceSettings
    {
        /** @var MemoryWorkspaceSettings $settings */
        $settings = $tenant->memoryWorkspaceSettings()->firstOrCreate([], [
            'collaboration_mode' => MemoryWorkspaceSettings::MODE_SHARED_EDITING,
        ]);

        $tenant->setRelation('memoryWorkspaceSettings', $settings);

        return $settings;
    }

    public function collaborationMode(Tenant $tenant): string
    {
        /** @var MemoryWorkspaceSettings|null $settings */
        $settings = $tenant->memoryWorkspaceSettings;

        if ($settings === null) {
            return MemoryWorkspaceSettings::MODE_SHARED_EDITING;
        }

        return $settings->collaboration_mode;
    }

    public function canCreateMemoryRecord(Tenant $tenant, User $user): bool
    {
        return $this->canWriteMemoryRecords($tenant, $user);
    }

    public function canUpdateMemoryRecord(Tenant $tenant, User $user): bool
    {
        return $this->canWriteMemoryRecords($tenant, $user);
    }

    public function canWriteMemoryRecords(Tenant $tenant, User $user): bool
    {
        if (! $user->canAccessTenant($tenant)) {
            return false;
        }

        return $this->collaborationMode($tenant) === MemoryWorkspaceSettings::MODE_SHARED_EDITING;
    }

    public function isActiveCollaborationMode(?string $mode): bool
    {
        return in_array($mode, MemoryWorkspaceSettings::ACTIVE_COLLABORATION_MODES, true);
    }
}
