<?php

namespace Tests\Feature\Memory;

use App\Models\MemoryProfile;
use App\Models\Tenant;
use Tests\Feature\FeatureTest;

class MemoryRouteAccessTest extends FeatureTest
{
    public function test_guest_is_redirected_from_memory_route(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();

        $response = $this->get($this->memoryRoute($tenant));

        $response->assertRedirect(route('login'));
    }

    public function test_tenant_member_can_access_memory_shell(): void
    {
        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Santos Family Workspace',
        ]);
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get($this->memoryRoute($tenant));

        $response->assertOk();
        $response->assertSee('data-memory-layout="true"', false);
        $response->assertSee('Memory Timeline');
        $response->assertSee('Santos Family Workspace');
        $response->assertSee('Memory profile');
        $response->assertSee('Account');
        $response->assertSee('Dashboard');
    }

    public function test_authenticated_non_member_receives_generic_not_found(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Private Tenant Details',
        ]);
        $user = $this->createUser();

        $response = $this->actingAs($user)->get($this->memoryRoute($tenant));

        $response->assertNotFound();
        $response->assertDontSee('Private Tenant Details');
        $response->assertDontSee('Memory Timeline');
    }

    public function test_memory_shell_uses_dedicated_product_layout(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get($this->memoryRoute($tenant));

        $response->assertOk();
        $response->assertSee('data-memory-layout="true"', false);
        $response->assertDontSee('All rights reserved.');
        $response->assertDontSee('Get started');
        $response->assertDontSee('fi-sidebar');
        $response->assertDontSee('<table', false);
        $response->assertDontSee('<footer', false);
    }

    public function test_existing_public_and_dashboard_entry_routes_keep_current_behavior(): void
    {
        $homeResponse = $this->get(route('home'));

        $homeResponse->assertOk();
        $homeResponse->assertSee('<footer', false);
        $homeResponse->assertDontSee('data-memory-layout="true"', false);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));

        $dashboardResponse->assertRedirect(route('filament.dashboard.pages.dashboard', [
            'tenant' => $tenant,
        ]));
    }

    private function memoryRoute(Tenant $tenant): string
    {
        return route('memories.timeline', [
            'tenant' => $tenant,
        ]);
    }
}
