<?php

namespace Tests\Feature\Livewire\Checkout;

use App\Dto\SubscriptionCheckoutDto;
use App\Dto\TotalsDto;
use App\Livewire\Checkout\SubscriptionTotals;
use App\Models\Currency;
use App\Models\Discount;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\FeatureTest;

class SubscriptionTotalsTest extends FeatureTest
{
    public function test_coupon_from_session_is_applied_on_mount(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $plan = $this->createPlanWithPrice();
        $code = $this->createDiscountCodeForPlan($plan);

        $sessionService = app(SessionService::class);
        $sessionService->saveCouponCode($code);

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $plan->slug;
        $sessionService->saveSubscriptionCheckoutDto($sessionDto);

        Livewire::test(SubscriptionTotals::class, [
            'totals' => $this->makeTotals(),
            'plan' => $plan,
            'page' => 'http://localhost/checkout',
        ]);

        // Coupon code should be consumed from session
        $this->assertNull($sessionService->getCouponCode());

        // Discount code should be stored in checkout DTO
        $updatedDto = $sessionService->getSubscriptionCheckoutDto();
        $this->assertEquals($code, $updatedDto->discountCode);
    }

    public function test_invalid_coupon_from_session_is_kept_in_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $plan = $this->createPlanWithPrice();

        $sessionService = app(SessionService::class);
        $sessionService->saveCouponCode('INVALID_CODE');

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $plan->slug;
        $sessionService->saveSubscriptionCheckoutDto($sessionDto);

        Livewire::test(SubscriptionTotals::class, [
            'totals' => $this->makeTotals(),
            'plan' => $plan,
            'page' => 'http://localhost/checkout',
        ]);

        // Coupon code should still be in session (not redeemable, kept for later)
        $this->assertEquals('INVALID_CODE', $sessionService->getCouponCode());

        // Discount code should NOT be stored in checkout DTO
        $updatedDto = $sessionService->getSubscriptionCheckoutDto();
        $this->assertNull($updatedDto->discountCode);
    }

    public function test_coupon_not_redeemable_for_plan_is_kept_in_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $plan = $this->createPlanWithPrice();

        // Create a discount NOT attached to this plan
        $discount = Discount::create([
            'name' => 'other plan discount',
            'description' => 'test',
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => true,
            'valid_until' => null,
            'action_type' => null,
            'max_redemptions' => -1,
            'max_redemptions_per_user' => -1,
            'is_recurring' => false,
            'redemptions' => 0,
        ]);

        $code = Str::random(10);
        $discount->codes()->create(['code' => $code]);

        $sessionService = app(SessionService::class);
        $sessionService->saveCouponCode($code);

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $plan->slug;
        $sessionService->saveSubscriptionCheckoutDto($sessionDto);

        Livewire::test(SubscriptionTotals::class, [
            'totals' => $this->makeTotals(),
            'plan' => $plan,
            'page' => 'http://localhost/checkout',
        ]);

        // Coupon code should still be in session (not redeemable for this plan)
        $this->assertEquals($code, $sessionService->getCouponCode());

        // Discount code should NOT be stored in checkout DTO
        $updatedDto = $sessionService->getSubscriptionCheckoutDto();
        $this->assertNull($updatedDto->discountCode);
    }

    public function test_no_coupon_in_session_does_nothing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $plan = $this->createPlanWithPrice();

        $sessionService = app(SessionService::class);

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $plan->slug;
        $sessionService->saveSubscriptionCheckoutDto($sessionDto);

        Livewire::test(SubscriptionTotals::class, [
            'totals' => $this->makeTotals(),
            'plan' => $plan,
            'page' => 'http://localhost/checkout',
        ]);

        $this->assertNull($sessionService->getCouponCode());

        $updatedDto = $sessionService->getSubscriptionCheckoutDto();
        $this->assertNull($updatedDto->discountCode);
    }

    private function createPlanWithPrice(): Plan
    {
        $plan = Plan::factory()->create([
            'slug' => 'plan-slug-'.Str::random(10),
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 1000,
        ]);

        return $plan;
    }

    private function createDiscountCodeForPlan(Plan $plan): string
    {
        $discount = Discount::create([
            'name' => 'test coupon',
            'description' => 'test',
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => true,
            'valid_until' => null,
            'action_type' => null,
            'max_redemptions' => -1,
            'max_redemptions_per_user' => -1,
            'is_recurring' => false,
            'redemptions' => 0,
        ]);

        $discount->plans()->attach($plan);

        $code = Str::random(10);
        $discount->codes()->create(['code' => $code]);

        return $code;
    }

    private function makeTotals(): TotalsDto
    {
        $totals = new TotalsDto;
        $totals->subtotal = 1000;
        $totals->discountAmount = 0;
        $totals->amountDue = 1000;
        $totals->currencyCode = 'USD';

        return $totals;
    }
}
