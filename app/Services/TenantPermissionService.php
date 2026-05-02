<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TenantPermissionService
{
    private static $permissionCache = [];

    public function tenantUserHasPermissionTo(?Tenant $tenant, User $user, string $permission): bool
    {
        if ($tenant === null) {
            return false;
        }

        // we need some kind of cache because filament calls this method multiple times
        // filament tenant switcher is inefficient and tried to build the navigation for all tenants, only to display the menu for the current tenant
        // I'm sure this will be solved in the future, but for now, we need to cache the permissions to reduce the number of queries to the database
        // this cache gets all the permissions of a user for a tenant and stores them in an array for quick access
        // you need to find another way to cache this (or avoid caching altogether) if you want to use FrankenPhp to avoid memory leaks
        // if you decide to avoid caching you can replace the content of this function with:
        // return $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot?->hasPermissionTo($permission) ?? false;

        if (isset(self::$permissionCache[$tenant->id][$user->id]) && is_bool(self::$permissionCache[$tenant->id][$user->id])) { // if the user has no permissions at all
            return self::$permissionCache[$tenant->id][$user->id];
        }

        if (isset(self::$permissionCache[$tenant->id][$user->id][$permission])) {
            return self::$permissionCache[$tenant->id][$user->id][$permission];
        }

        // Get the TenantUser pivot record
        $tenantUser = $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot;

        if (! $tenantUser) {
            self::$permissionCache[$tenant->id][$user->id] = false;

            return false;
        }

        // Get tenant-specific permissions
        $tenantPermissions = $tenantUser->getAllPermissions() ?? collect();

        // Get global permissions (roles without tenant assigned to this TenantUser)
        $globalPermissions = $tenantUser
            ->roles()
            ->withoutGlobalScope(filament()->getTenancyScopeName())
            ->whereNull('tenant_id')
            ->get()->flatMap(function ($role) {
                return $role->permissions;
            });

        // Merge both permission collections
        $allPermissions = $tenantPermissions->merge($globalPermissions)->unique('id');

        if (config('app.teams_enabled')) {
            // Get team-based permissions
            foreach ($tenantUser->teams as $team) {
                $allPermissions = $allPermissions->merge($team->getAllPermissions())->unique('id');
            }
        }

        if ($allPermissions->count() === 0) {
            self::$permissionCache[$tenant->id][$user->id] = false;

            return false;
        }

        foreach ($allPermissions as $onePermission) {
            self::$permissionCache[$tenant->id][$user->id][$onePermission->name] = true;
        }

        return self::$permissionCache[$tenant->id][$user->id][$permission] ?? false;
    }

    public function filterTenantsWhereUserHasPermission(Collection $tenants, string $permission)
    {
        return $tenants->filter(function ($tenant) use ($permission) {
            return $tenant->pivot->hasPermissionTo($permission);
        });
    }

    public function getTenantUserRoles(Tenant $tenant, User $user): array
    {
        $tenantUser = $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot;

        if (! $tenantUser) {
            return [];
        }

        $roles = $tenantUser->roles()
            ->withoutGlobalScope(filament()->getTenancyScopeName())  // to avoid the filament tenancy scope to get global roles as well
            ->where(function ($query) use ($tenant) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->pluck('name')
            ->toArray();

        return $roles;
    }

    public function assignTenantUserRole(Tenant $tenant, User $user, string $role): void
    {
        $roleObject = $this->findTenantRole($tenant, $role);

        if ($roleObject === null) {
            throw new InvalidArgumentException("Role '{$role}' does not exist for tenant '{$tenant->name}'.");
        }

        $this->removeAllTenantUserRoles($tenant, $user);
        $user->tenants()->where('tenant_id', $tenant->id)->first()->pivot->assignRole($roleObject);
    }

    public function removeAllTenantUserRoles(Tenant $tenant, User $user): void
    {
        $user->tenants()->where('tenant_id', $tenant->id)->first()->pivot->syncRoles([]);
    }

    public function findTenantRole(Tenant $tenant, string $name): ?Role
    {
        return Role::query()
            ->withoutGlobalScope(filament()->getTenancyScopeName())  // to avoid the filament tenancy scope to get global roles as well
            ->where('name', $name)
            ->where('is_tenant_role', true)
            ->where(function ($query) use ($tenant) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->first();
    }

    public function getAllAvailableTenantRolesForDisplay(Tenant $tenant, bool $useIdAsIdentifier = false): array
    {
        $roles = Role::query()
            ->withoutGlobalScope(filament()->getTenancyScopeName())  // to avoid the filament tenancy scope to get global roles as well
            ->where('is_tenant_role', true)
            ->where(function ($query) use ($tenant) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            })
            ->pluck('name', $useIdAsIdentifier ? 'id' : 'name')
            ->toArray();

        $result = [];
        foreach ($roles as $identifier => $role) {
            $id = $useIdAsIdentifier ? $identifier : $role;
            $result[$id] = Str::title($role);
        }

        return $result;
    }
}
