<?php

namespace Tests\Feature\Memory;

use App\Models\MemoryProfile;
use App\Models\Tenant;
use Tests\Feature\FeatureTest;

class MemoryProfileSetupGateTest extends FeatureTest
{
    public function test_member_without_completed_profile_is_redirected_from_default_entry_to_setup(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $response = $this->actingAs($user)->get($this->entryRoute($tenant));

        $response->assertRedirect($this->setupRoute($tenant));
    }

    public function test_member_without_completed_profile_is_redirected_from_timeline_to_setup(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $response = $this->actingAs($user)->get($this->timelineRoute($tenant));

        $response->assertRedirect($this->setupRoute($tenant));
    }

    public function test_member_can_access_setup_before_profile_completion(): void
    {
        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Santos Family Workspace',
        ]);
        $user = $this->createUser($tenant);

        $response = $this->actingAs($user)->get($this->setupRoute($tenant));

        $response->assertOk();
        $response->assertSee('data-memory-layout="true"', false);
        $response->assertSee('Set up your memory profile');
        $response->assertSee('Santos Family Workspace');
        $response->assertSee('Account profile');
    }

    public function test_completed_profile_can_access_timeline(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create([
            'preferred_locale' => 'en',
        ]);

        $response = $this->actingAs($user)->get($this->timelineRoute($tenant));

        $response->assertOk();
        $response->assertSee('Memory Timeline');
        $response->assertSee(__('memory.timeline.ready_heading'));
    }

    public function test_completed_profile_default_entry_redirects_to_timeline(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get($this->entryRoute($tenant));

        $response->assertRedirect($this->timelineRoute($tenant));
    }

    public function test_authenticated_non_member_receives_generic_not_found(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Private Tenant Details',
        ]);
        $user = $this->createUser();

        $response = $this->actingAs($user)->get($this->timelineRoute($tenant));

        $response->assertNotFound();
        $response->assertDontSee('Private Tenant Details');
        $response->assertDontSee('Set up your memory profile');

        $setupResponse = $this->actingAs($user)->get($this->setupRoute($tenant));

        $setupResponse->assertNotFound();
        $setupResponse->assertDontSee('Private Tenant Details');
        $setupResponse->assertDontSee('Set up your memory profile');
    }

    public function test_profile_gate_does_not_block_existing_dashboard_entry_behavior(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('filament.dashboard.pages.dashboard', [
            'tenant' => $tenant,
        ]));
    }

    private function entryRoute(Tenant $tenant): string
    {
        return route('memories.entry', [
            'tenant' => $tenant,
        ]);
    }

    private function setupRoute(Tenant $tenant): string
    {
        return route('memories.profile.setup', [
            'tenant' => $tenant,
        ]);
    }

    private function timelineRoute(Tenant $tenant): string
    {
        return route('memories.timeline', [
            'tenant' => $tenant,
        ]);
    }
}
