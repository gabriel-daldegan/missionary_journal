<?php

namespace Tests\Feature\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Roles\RoleResource;
use Tests\Feature\FeatureTest;

class RoleResourceTest extends FeatureTest
{
    public function test_list(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $response = $this->get(RoleResource::getUrl('index', [], true, 'admin'))->assertSuccessful();

        $response->assertStatus(200);
    }
}
