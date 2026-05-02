<?php

namespace Tests\Feature\Services;

use App\Constants\TenancyPermissionConstants;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Team;
use App\Services\TenantPermissionService;
use Tests\Feature\FeatureTest;

class TenantPermissionServiceTest extends FeatureTest
{
    public function test_tenant_can_only_see_own_roles_plus_global_roles()
    {
        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        $user1 = $this->createUser($tenant1);
        $user2 = $this->createUser($tenant2);

        $tenantPermissionService = new TenantPermissionService;

        $role1 = Role::query()->firstOrCreate([
            'name' => 'test role 1',
            'is_tenant_role' => true,
            'tenant_id' => $tenant1->id,
        ], [
            'guard_name' => 'web',
        ]);

        $role2 = Role::query()->firstOrCreate([
            'name' => 'test role 2',
            'is_tenant_role' => true,
            'tenant_id' => $tenant2->id,
        ], [
            'guard_name' => 'web',
        ]);

        $permission1 = Permission::findOrCreate('tenancy: test permission 1');

        $permission2 = Permission::findOrCreate('tenancy: test permission 2');

        $role1->givePermissionTo([$permission1]);
        $role2->givePermissionTo([$permission2]);

        $tenantPermissionService->assignTenantUserRole($tenant1, $user1, $role1->name);

        $tenantPermissionService->assignTenantUserRole($tenant2, $user2, $role2->name);

        $result = $tenantPermissionService->getAllAvailableTenantRolesForDisplay($tenant1);

        $this->assertArrayHasKey($role1->name, $result);
        $this->assertArrayNotHasKey($role2->name, $result);
    }

    public function test_can_only_assign_tenant_role_to_owner_tenant_user()
    {
        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();

        $user1 = $this->createUser($tenant1);

        $tenantPermissionService = new TenantPermissionService;

        $role1 = Role::query()->firstOrCreate([
            'name' => 'test role 1',
            'is_tenant_role' => true,
            'tenant_id' => $tenant1->id,
        ], [
            'guard_name' => 'web',
        ]);

        $role2 = Role::query()->firstOrCreate([
            'name' => 'test role 2',
            'is_tenant_role' => true,
            'tenant_id' => $tenant2->id,
        ], [
            'guard_name' => 'web',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $tenantPermissionService->assignTenantUserRole($tenant1, $user1, $role2->name);
    }

    public function test_tenant_user_has_permission(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS]);

        $this->actingAs($user);

        $tenantPermissionService = new TenantPermissionService;

        $this->assertTrue($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS));
        $this->assertFalse($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, TenancyPermissionConstants::PERMISSION_VIEW_SUBSCRIPTIONS));
    }

    public function test_tenant_user_has_permissions_of_team_when_teams_enabled(): void
    {
        config(['app.teams_enabled' => true]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $team = Team::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Team',
        ]);

        $team->givePermissionTo([TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS]);

        // add user to team
        $tenantUser = $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot;
        $team->tenantUsers()->attach($tenantUser->id);

        $this->actingAs($user);

        $tenantPermissionService = new TenantPermissionService;

        $this->assertTrue($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS));
    }

    public function test_tenant_user_has_permissions_of_own_role_and_team_when_teams_enabled(): void
    {
        config(['app.teams_enabled' => true]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_VIEW_SUBSCRIPTIONS]);

        $team = Team::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Team',
        ]);

        $team->givePermissionTo([TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS]);

        // add user to team
        $tenantUser = $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot;
        $team->tenantUsers()->attach($tenantUser->id);

        $this->actingAs($user);

        $tenantPermissionService = new TenantPermissionService;

        $this->assertTrue($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS));
        $this->assertTrue($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, TenancyPermissionConstants::PERMISSION_VIEW_SUBSCRIPTIONS));
    }

    public function test_tenant_user_has_doesnt_get_permissions_of_team_when_teams_disabled(): void
    {
        config(['app.teams_enabled' => false]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $team = Team::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Team',
        ]);

        $team->givePermissionTo([TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS]);

        // add user to team
        $tenantUser = $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot;
        $team->tenantUsers()->attach($tenantUser->id);

        $this->actingAs($user);

        $tenantPermissionService = new TenantPermissionService;

        $this->assertFalse($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS));
    }

    public function test_tenant_assign_role(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'test role',
            'is_tenant_role' => true,
        ], [
            'guard_name' => 'web',
        ]);

        $permission = Permission::findOrCreate('tenancy: test permission');
        $role->givePermissionTo([$permission]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $tenantPermissionService = new TenantPermissionService;

        $tenantPermissionService->assignTenantUserRole($tenant, $user, $role->name);

        $tenantRoles = $tenantPermissionService->getTenantUserRoles($tenant, $user);

        $this->assertContains($role->name, $tenantRoles);
        $this->assertTrue($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, 'tenancy: test permission'));
    }

    public function test_tenant_remove_all_user_roles(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'test role',
            'is_tenant_role' => true,
        ], [
            'guard_name' => 'web',
        ]);

        $permission = Permission::findOrCreate('tenancy: test permission');
        $role->givePermissionTo([$permission]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $tenantPermissionService = new TenantPermissionService;

        $tenantPermissionService->assignTenantUserRole($tenant, $user, $role->name);

        $tenantPermissionService->removeAllTenantUserRoles($tenant, $user);

        $tenantRoles = $tenantPermissionService->getTenantUserRoles($tenant, $user);

        $this->assertEmpty($tenantRoles);
        $this->assertFalse($tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, 'tenancy: test permission'));
    }
}
