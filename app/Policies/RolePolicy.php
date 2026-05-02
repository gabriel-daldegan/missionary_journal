<?php

namespace App\Policies;

use App\Constants\TenancyPermissionConstants;
use App\Models\Role;
use App\Models\User;
use App\Services\TenantPermissionService;
use Filament\Facades\Filament;

class RolePolicy
{
    public function __construct(
        private TenantPermissionService $tenantPermissionService
    ) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view roles') || $this->tenantPermissionService->tenantUserHasPermissionTo(
            Filament::getTenant(),
            $user,
            TenancyPermissionConstants::PERMISSION_VIEW_ROLES,
        );
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('view roles') || $this->tenantPermissionService->tenantUserHasPermissionTo(
            Filament::getTenant(),
            $user,
            TenancyPermissionConstants::PERMISSION_VIEW_ROLES,
        );
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create roles') || $this->tenantPermissionService->tenantUserHasPermissionTo(
            Filament::getTenant(),
            $user,
            TenancyPermissionConstants::PERMISSION_CREATE_ROLES,
        );
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('update roles') || $this->tenantPermissionService->tenantUserHasPermissionTo(
            Filament::getTenant(),
            $user,
            TenancyPermissionConstants::PERMISSION_UPDATE_ROLES,
        );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('delete roles');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Role $role): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        return false;
    }
}
