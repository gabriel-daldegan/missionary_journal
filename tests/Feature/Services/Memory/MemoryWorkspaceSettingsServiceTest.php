<?php

namespace Tests\Feature\Services\Memory;

use App\Models\MemoryWorkspaceSettings;
use App\Services\MemoryWorkspaceSettingsService;
use Tests\Feature\FeatureTest;

class MemoryWorkspaceSettingsServiceTest extends FeatureTest
{
    public function test_resolving_settings_creates_tenant_scoped_shared_editing_defaults(): void
    {
        $tenant = $this->createTenant();

        $settings = $this->service()->resolveForTenant($tenant);

        $this->assertModelExists($settings);
        $this->assertTrue($settings->tenant->is($tenant));
        $this->assertSame(MemoryWorkspaceSettings::MODE_SHARED_EDITING, $settings->collaboration_mode);
    }

    public function test_resolving_settings_twice_does_not_create_duplicate_rows(): void
    {
        $tenant = $this->createTenant();

        $first = $this->service()->resolveForTenant($tenant);
        $second = $this->service()->resolveForTenant($tenant->fresh());

        $this->assertTrue($first->is($second));
        $this->assertSame(1, MemoryWorkspaceSettings::query()->whereBelongsTo($tenant)->count());
    }

    public function test_missing_settings_fall_back_to_shared_editing_mode(): void
    {
        $tenant = $this->createTenant();

        $mode = $this->service()->collaborationMode($tenant);

        $this->assertSame(MemoryWorkspaceSettings::MODE_SHARED_EDITING, $mode);
        $this->assertSame(0, MemoryWorkspaceSettings::query()->whereBelongsTo($tenant)->count());
    }

    public function test_tenant_owner_member_can_create_and_update_future_memory_records(): void
    {
        $tenant = $this->createTenant();
        $owner = $this->createUser($tenant);

        $this->service()->resolveForTenant($tenant);

        $this->assertTrue($this->service()->canCreateMemoryRecord($tenant, $owner));
        $this->assertTrue($this->service()->canUpdateMemoryRecord($tenant, $owner));
    }

    public function test_existing_collaborator_member_can_create_and_update_future_memory_records(): void
    {
        $tenant = $this->createTenant();
        $this->createUser($tenant);
        $collaborator = $this->createUser($tenant);

        $this->service()->resolveForTenant($tenant);

        $this->assertTrue($this->service()->canCreateMemoryRecord($tenant, $collaborator));
        $this->assertTrue($this->service()->canUpdateMemoryRecord($tenant, $collaborator));
    }

    public function test_user_outside_tenant_is_denied_future_memory_record_write_decisions(): void
    {
        $tenant = $this->createTenant();
        $outsideUser = $this->createUser();

        $this->service()->resolveForTenant($tenant);

        $this->assertFalse($this->service()->canCreateMemoryRecord($tenant, $outsideUser));
        $this->assertFalse($this->service()->canUpdateMemoryRecord($tenant, $outsideUser));
    }

    public function test_non_shared_collaboration_mode_denies_future_memory_record_write_decisions(): void
    {
        $tenant = $this->createTenant();
        $member = $this->createUser($tenant);
        $settings = $this->service()->resolveForTenant($tenant);

        $settings->update([
            'collaboration_mode' => 'owner_review',
        ]);
        $tenant->refresh();

        $this->assertFalse($this->service()->canCreateMemoryRecord($tenant, $member));
        $this->assertFalse($this->service()->canUpdateMemoryRecord($tenant, $member));
    }

    public function test_settings_for_one_tenant_do_not_authorize_another_tenant(): void
    {
        $firstTenant = $this->createTenant();
        $secondTenant = $this->createTenant();
        $firstTenantMember = $this->createUser($firstTenant);

        $this->service()->resolveForTenant($firstTenant);
        $this->service()->resolveForTenant($secondTenant);

        $this->assertTrue($this->service()->canCreateMemoryRecord($firstTenant, $firstTenantMember));
        $this->assertFalse($this->service()->canCreateMemoryRecord($secondTenant, $firstTenantMember));
    }

    private function service(): MemoryWorkspaceSettingsService
    {
        return app(MemoryWorkspaceSettingsService::class);
    }
}
