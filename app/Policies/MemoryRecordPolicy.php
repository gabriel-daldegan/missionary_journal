<?php

namespace App\Policies;

use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MemoryWorkspaceSettingsService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MemoryRecordPolicy
{
    public function __construct(private MemoryWorkspaceSettingsService $workspaceSettingsService) {}

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, MemoryRecord $memoryRecord): bool
    {
        return $memoryRecord->tenant !== null
            && $user->canAccessTenant($memoryRecord->tenant);
    }

    public function create(User $user, Tenant $tenant): bool
    {
        return $this->workspaceSettingsService->canCreateMemoryRecord($tenant, $user);
    }

    public function update(User $user, MemoryRecord $memoryRecord): bool
    {
        if ($memoryRecord->tenant === null) {
            return false;
        }

        return $this->workspaceSettingsService->canUpdateMemoryRecord($memoryRecord->tenant, $user);
    }

    public function viewMedia(User $user, MemoryRecord $memoryRecord, Media $media): bool
    {
        return $media->model_type === $memoryRecord->getMorphClass()
            && (int) $media->model_id === $memoryRecord->id
            && $media->collection_name === $memoryRecord->mediaCollectionName()
            && $this->view($user, $memoryRecord);
    }

    public function delete(User $user, MemoryRecord $memoryRecord): bool
    {
        return false;
    }

    public function restore(User $user, MemoryRecord $memoryRecord): bool
    {
        return false;
    }

    public function forceDelete(User $user, MemoryRecord $memoryRecord): bool
    {
        return false;
    }
}
