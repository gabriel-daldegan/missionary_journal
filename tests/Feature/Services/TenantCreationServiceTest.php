<?php

namespace Tests\Feature\Services;

use App\Constants\TenancyPermissionConstants;
use App\Events\Tenant\TenantCreated;
use App\Models\Plan;
use App\Services\TenantCreationService;
use App\Services\TenantPermissionService;
use Illuminate\Support\Facades\Event;
use Tests\Feature\FeatureTest;

class TenantCreationServiceTest extends FeatureTest
{
    private TenantCreationService $tenantCreationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantCreationService = app(TenantCreationService::class);
    }

    public function test_create_tenant(): void
    {
        $user = $this->createUser();

        $tenantPermissionService = \Mockery::mock(TenantPermissionService::class);
        $tenantPermissionService->shouldReceive('assignTenantUserRole')
            ->once()
            ->with(\Mockery::any(), $user, TenancyPermissionConstants::TENANT_CREATOR_ROLE);

        $tenantCreationService = new TenantCreationService($tenantPermissionService);

        Event::fake();

        $tenant = $tenantCreationService->createTenant($user);

        $this->assertEquals(1, $user->tenants()->count());
        Event::assertDispatched(TenantCreated::class);
    }

    public function test_find_tenants_excludes_tenants_exceeding_plan_max_users(): void
    {
        $plan = Plan::factory()->create([
            'max_users_per_tenant' => 2,
            'is_active' => true,
        ]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS]);
        $this->createUser($tenant); // 2 users
        $this->createUser($tenant); // 3 users — exceeds limit

        $this->actingAs($user);

        $tenants = $this->tenantCreationService->findUserTenantsForNewSubscription($user, $plan);

        $this->assertFalse($tenants->contains('id', $tenant->id));
    }

    public function test_find_tenants_includes_tenants_at_or_below_plan_max_users(): void
    {
        $plan = Plan::factory()->create([
            'max_users_per_tenant' => 2,
            'is_active' => true,
        ]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS]);
        $this->createUser($tenant); // 2 users — at limit

        $this->actingAs($user);

        $tenants = $this->tenantCreationService->findUserTenantsForNewSubscription($user, $plan);

        $this->assertTrue($tenants->contains('id', $tenant->id));
    }

    public function test_find_tenants_includes_all_when_plan_has_unlimited_users(): void
    {
        $plan = Plan::factory()->create([
            'max_users_per_tenant' => 0,
            'is_active' => true,
        ]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS]);
        $this->createUser($tenant);
        $this->createUser($tenant);
        $this->createUser($tenant); // 4 users

        $this->actingAs($user);

        $tenants = $this->tenantCreationService->findUserTenantsForNewSubscription($user, $plan);

        $this->assertTrue($tenants->contains('id', $tenant->id));
    }

    public function test_find_tenants_includes_all_when_no_plan_provided(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS]);
        $this->createUser($tenant);
        $this->createUser($tenant);
        $this->createUser($tenant); // 4 users

        $this->actingAs($user);

        $tenants = $this->tenantCreationService->findUserTenantsForNewSubscription($user);

        $this->assertTrue($tenants->contains('id', $tenant->id));
    }

    public function test_find_tenant_by_uuid_returns_null_when_exceeding_plan_max_users(): void
    {
        $plan = Plan::factory()->create([
            'max_users_per_tenant' => 1,
            'is_active' => true,
        ]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS]);
        $this->createUser($tenant); // 2 users — exceeds limit of 1

        $this->actingAs($user);

        $result = $this->tenantCreationService->findUserTenantForNewSubscriptionByUuid($user, $tenant->uuid, $plan);

        $this->assertNull($result);
    }

    public function test_find_tenant_by_uuid_returns_tenant_when_within_plan_max_users(): void
    {
        $plan = Plan::factory()->create([
            'max_users_per_tenant' => 5,
            'is_active' => true,
        ]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS]);

        $this->actingAs($user);

        $result = $this->tenantCreationService->findUserTenantForNewSubscriptionByUuid($user, $tenant->uuid, $plan);

        $this->assertNotNull($result);
        $this->assertEquals($tenant->id, $result->id);
    }
}
