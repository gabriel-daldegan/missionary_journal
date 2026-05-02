<?php

namespace Tests\Feature\Filament\Dashboard\Resources;

use App\Constants\TenancyPermissionConstants;
use App\Filament\Dashboard\Resources\Teams\TeamResource;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\FeatureTest;

class TeamResourceTest extends FeatureTest
{
    public function test_list(): void
    {
        config(['app.teams_enabled' => true]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [
            TenancyPermissionConstants::PERMISSION_MANAGE_TEAM,
        ]);

        $this->actingAs($user);

        $response = $this->get(TeamResource::getUrl('index', [], true, 'dashboard', tenant: $tenant))->assertSuccessful();

        $response->assertStatus(200);
    }

    public function test_list_fails_when_user_has_no_permission(): void
    {
        config(['app.teams_enabled' => true]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $this->actingAs($user);
        $this->expectException(HttpException::class);

        $this->get(TeamResource::getUrl('index', [], true, 'dashboard', tenant: $tenant));
    }

    public function test_list_fails_when_teams_are_not_enabled(): void
    {
        config(['app.teams_enabled' => false]);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $this->actingAs($user);
        $this->expectException(HttpException::class);

        $this->get(TeamResource::getUrl('index', [], true, 'dashboard', tenant: $tenant));
    }
}
