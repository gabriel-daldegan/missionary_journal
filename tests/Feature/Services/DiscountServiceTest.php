<?php

namespace Tests\Feature\Services;

use App\Models\Discount;
use App\Models\OneTimeProduct;
use App\Models\Plan;
use App\Models\User;
use App\Services\DiscountService;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class DiscountServiceTest extends FeatureTest
{
    public function test_is_code_redeemable_for_plan()
    {
        // add a discount
        $discount = Discount::create([
            'name' => 'test',
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

        $plan = Plan::factory()->create([
            'name' => 'Basic Plan',
            'slug' => 'basic-plan-'.Str::random(10),
        ]);

        $discount->plans()->attach($plan);

        $code = Str::random(10);
        $discountCode = $discount->codes()->create([
            'code' => $code,
        ]);

        $user = User::factory()->create();

        $discountService = app()->make(DiscountService::class);

        $this->assertTrue($discountService->isCodeRedeemableForPlan($code, $user, $plan));
    }

    public function test_is_code_redeemable_for_plan_when_enabled_for_all_plans()
    {
        // add a discount
        $discount = Discount::create([
            'name' => 'test',
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
            'is_enabled_for_all_plans' => true,
        ]);

        $plan = Plan::factory()->create([
            'name' => 'Basic Plan',
            'slug' => 'basic-plan-'.Str::random(10),
        ]);

        $code = Str::random(10);
        $discountCode = $discount->codes()->create([
            'code' => $code,
        ]);

        $user = User::factory()->create();

        $discountService = app()->make(DiscountService::class);

        $this->assertTrue($discountService->isCodeRedeemableForPlan($code, $user, $plan));
    }

    public function test_is_code_redeemable_for_one_time_product()
    {
        // add a discount
        $discount = Discount::create([
            'name' => 'test',
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

        $oneTimeProduct = OneTimeProduct::factory()->create();

        $discount->oneTimeProducts()->attach($oneTimeProduct);

        $code = Str::random(10);
        $discountCode = $discount->codes()->create([
            'code' => $code,
        ]);

        $user = User::factory()->create();

        $discountService = app()->make(DiscountService::class);

        $this->assertTrue($discountService->isCodeRedeemableForOneTimeProduct($code, $user, $oneTimeProduct));
    }

    public function test_is_code_redeemable_for_one_time_product_when_enabled_for_all_one_time_products()
    {
        // add a discount
        $discount = Discount::create([
            'name' => 'test',
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
            'is_enabled_for_all_one_time_products' => true,
        ]);

        $oneTimeProduct = OneTimeProduct::factory()->create();

        $code = Str::random(10);
        $discountCode = $discount->codes()->create([
            'code' => $code,
        ]);

        $user = User::factory()->create();

        $discountService = app()->make(DiscountService::class);

        $this->assertTrue($discountService->isCodeRedeemableForOneTimeProduct($code, $user, $oneTimeProduct));
    }

    public function test_is_code_not_redeemable_for_one_time_product_because_product_is_not_included_in_list()
    {
        // add a discount
        $discount = Discount::create([
            'name' => 'test',
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

        // add a one-time product
        $oneTimeProduct1 = OneTimeProduct::factory()->create();
        $oneTimeProduct2 = OneTimeProduct::factory()->create();

        $discount->oneTimeProducts()->attach($oneTimeProduct1);

        $code = Str::random(10);
        $discountCode = $discount->codes()->create([
            'code' => $code,
        ]);

        $user = User::factory()->create();

        $discountService = app()->make(DiscountService::class);

        $this->assertFalse($discountService->isCodeRedeemableForOneTimeProduct($code, $user, $oneTimeProduct2));
    }

    public function test_is_code_not_redeemable_for_plan_because_of_valid_until()
    {
        // add a discount
        $discount = Discount::create([
            'name' => 'test',
            'description' => 'test',
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => true,
            'valid_until' => now()->subDay(),
            'action_type' => null,
            'max_redemptions' => -1,
            'max_redemptions_per_user' => -1,
            'is_recurring' => false,
            'redemptions' => 0,
        ]);

        $code = Str::random(10);
        $discountCode = $discount->codes()->create([
            'code' => $code,
        ]);

        $user = User::factory()->create();

        // find plan from database
        $plan = Plan::take(1)->first();

        $discountService = app()->make(DiscountService::class);

        $this->assertFalse($discountService->isCodeRedeemableForPlan($code, $user, $plan));
    }

    public function test_is_code_not_redeemable_for_plan_because_of_max_redemptions()
    {
        // add a discount
        $discount = Discount::create([
            'name' => 'test',
            'description' => 'test',
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => true,
            'valid_until' => null,
            'action_type' => null,
            'max_redemptions' => 1,
            'max_redemptions_per_user' => -1,
            'is_recurring' => false,
            'redemptions' => 2,
        ]);

        $code = Str::random(10);
        $discountCode = $discount->codes()->create([
            'code' => $code,
        ]);

        $user = User::factory()->create();

        // find plan from database
        $plan = Plan::take(1)->first();

        $discountService = app()->make(DiscountService::class);

        $this->assertFalse($discountService->isCodeRedeemableForPlan($code, $user, $plan));
    }

    public function test_is_code_not_redeemable_for_plan_because_of_is_active()
    {
        // add a discount
        $discount = Discount::create([
            'name' => 'test',
            'description' => 'test',
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => false,
            'valid_until' => null,
            'action_type' => null,
            'max_redemptions' => -1,
            'max_redemptions_per_user' => -1,
            'is_recurring' => false,
            'redemptions' => 0,
        ]);

        $code = Str::random(10);
        $discountCode = $discount->codes()->create([
            'code' => $code,
        ]);

        $user = User::factory()->create();

        // find plan from database
        $plan = Plan::take(1)->first();

        $discountService = app()->make(DiscountService::class);

        $this->assertFalse($discountService->isCodeRedeemableForPlan($code, $user, $plan));
    }

    public function test_is_code_not_redeemable_for_plan_because_of_max_redemptions_per_user()
    {
        // add a discount
        $discount = Discount::create([
            'name' => 'test',
            'description' => 'test',
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => true,
            'valid_until' => null,
            'action_type' => null,
            'max_redemptions' => -1,
            'max_redemptions_per_user' => 1,
            'is_recurring' => false,
            'redemptions' => 0,
        ]);

        $code = Str::random(10);
        $discountCode = $discount->codes()->create([
            'code' => $code,
        ]);

        $user = User::factory()->create();

        // find plan from database
        $plan = Plan::take(1)->first();

        $discountService = app()->make(DiscountService::class);

        $discountCode->redemptions()->create([
            'user_id' => $user->id,
        ]);

        $this->assertFalse($discountService->isCodeRedeemableForPlan($code, $user, $plan));
    }

    public function test_is_code_not_redeemable_because_of_plan()
    {
        // add a discount
        $discount = Discount::create([
            'name' => 'test',
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
        $discountCode = $discount->codes()->create([
            'code' => $code,
        ]);

        $user = User::factory()->create();

        // find plan from database
        $plan = Plan::where('slug', 'basic')->first();

        $discount->plans()->attach($plan);

        $plan2 = Plan::where('slug', 'pro')->first();

        $discountService = app()->make(DiscountService::class);

        $this->assertFalse($discountService->isCodeRedeemableForPlan($code, $user, $plan2));
    }
}
