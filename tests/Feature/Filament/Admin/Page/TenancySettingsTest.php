<?php

namespace Tests\Feature\Filament\Admin\Page;

use App\Filament\Admin\Pages\TenancySettings;
use App\Livewire\Filament\TenancySettings as TenancySettingsLivewire;
use App\Models\Config;
use Livewire\Livewire;
use Tests\Feature\FeatureTest;

class TenancySettingsTest extends FeatureTest
{
    public function test_can_access_tenancy_settings(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        $this->get(TenancySettings::getUrl([], true, 'admin'))
            ->assertSuccessful();
    }

    public function test_can_toggle_allow_user_to_create_tenants(): void
    {
        $user = $this->createAdminUser();
        $this->actingAs($user);

        config()->set('app.allow_user_to_create_tenants_from_dashboard', false);

        Livewire::test(TenancySettingsLivewire::class)
            ->assertSet('data.allow_user_to_create_tenants_from_dashboard', false)
            ->set('data.allow_user_to_create_tenants_from_dashboard', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('1', Config::get('app.allow_user_to_create_tenants_from_dashboard'));
    }
}
