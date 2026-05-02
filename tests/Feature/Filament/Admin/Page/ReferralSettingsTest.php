<?php

namespace Tests\Feature\Filament\Admin\Page;

use App\Filament\Admin\Pages\ReferralSettings;
use Tests\Feature\FeatureTest;

class ReferralSettingsTest extends FeatureTest
{
    public function test_admin_can_access_referral_settings_page(): void
    {
        config(['app.admin_settings.enabled' => true]);

        $user = $this->createAdminUser();
        $this->actingAs($user);

        $response = $this->get(ReferralSettings::getUrl([], true, 'admin'));

        $response->assertSuccessful();
        $response->assertStatus(200);
    }

    public function test_page_displays_referral_settings_form(): void
    {
        config(['app.admin_settings.enabled' => true]);

        $user = $this->createAdminUser();
        $this->actingAs($user);

        $response = $this->get(ReferralSettings::getUrl([], true, 'admin'));

        $response->assertSuccessful();
        $response->assertSee('Referral Settings');
    }
}
