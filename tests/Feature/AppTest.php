<?php

namespace Tests\Feature;

use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Models\Currency;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Product;

class AppTest extends FeatureTest
{
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_can_see_tracking_scripts_when_cookie_consent_bar_is_disabled()
    {
        config(['app.tracking_scripts' => '<javascript>Google Analytics</javascript>']);
        config(['cookie-consent.enabled' => false]);

        $response = $this->get('/');

        $response->assertSeeHtml('<javascript>Google Analytics</javascript>');
    }

    public function test_can_not_see_tracking_scripts_when_cookie_consent_bar_is_enabled_but_not_accepted()
    {
        config(['app.tracking_scripts' => '<javascript>Google Analytics</javascript>']);
        config(['cookie-consent.enabled' => true]);

        $response = $this->get('/');

        $response->assertDontSeeHtml('<javascript>Google Analytics</javascript>');
    }

    public function test_can_see_tracking_scripts_when_cookie_consent_bar_is_enabled_and_accepted()
    {
        config(['app.tracking_scripts' => '<javascript>Google Analytics</javascript>']);
        config(['cookie-consent.enabled' => true]);

        $response = $this->withCookie(config('cookie-consent.cookie_name'), 'accepted')->get('/');

        $response->assertSeeHtml('<javascript>Google Analytics</javascript>');
    }

    public function test_usage_based_pricing_without_meter_does_not_break_home_page(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();
        $product = Product::factory()->create([
            'name' => 'Usage Product',
        ]);
        $plan = Plan::factory()->create([
            'name' => 'Usage Plan',
            'product_id' => $product->id,
            'type' => PlanType::USAGE_BASED->value,
            'is_visible' => true,
            'is_active' => true,
        ]);

        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'type' => PlanPriceType::USAGE_BASED_PER_UNIT->value,
            'price' => 0,
            'price_per_unit' => 10,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Usage Product');
    }
}
