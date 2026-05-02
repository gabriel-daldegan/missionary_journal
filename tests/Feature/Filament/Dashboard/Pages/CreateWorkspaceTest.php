<?php

namespace Tests\Feature\Filament\Dashboard\Pages;

use App\Filament\Dashboard\Pages\CreateWorkspace;
use App\Livewire\Filament\Dashboard\CreateWorkspace as CreateWorkspaceLivewire;
use App\Models\Tenant;
use Livewire\Livewire;
use Tests\Feature\FeatureTest;

class CreateWorkspaceTest extends FeatureTest
{
    public function test_cannot_access_create_workspace_page_if_disabled(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        config()->set('app.allow_user_to_create_tenants_from_dashboard', false);

        $this->withExceptionHandling();

        $this->get(CreateWorkspace::getUrl(panel: 'dashboard', tenant: $tenant))
            ->assertStatus(403);
    }

    public function test_can_access_create_workspace_page_if_enabled(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        config()->set('app.allow_user_to_create_tenants_from_dashboard', true);

        $this->get(CreateWorkspace::getUrl(panel: 'dashboard', tenant: $tenant))
            ->assertSuccessful();
    }

    public function test_can_create_workspace(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        config()->set('app.allow_user_to_create_tenants_from_dashboard', true);

        Livewire::test(CreateWorkspaceLivewire::class)
            ->set('data.name', 'Test Workspace')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Workspace',
            'created_by' => $user->id,
        ]);

        $newTenant = Tenant::where('name', 'Test Workspace')->first();
        $this->assertTrue($user->fresh()->tenants->contains($newTenant));
    }

    public function test_workspace_name_is_required(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        config()->set('app.allow_user_to_create_tenants_from_dashboard', true);

        Livewire::test(CreateWorkspaceLivewire::class)
            ->set('data.name', '')
            ->call('create')
            ->assertHasErrors(['data.name' => 'required']);
    }
}
