<?php

namespace Tests\Feature\Services;

use App\Constants\SubscriptionStatus;
use App\Events\Subscription\Subscribed;
use App\Events\Subscription\SubscriptionCancelled;
use App\Events\Subscription\SubscriptionRenewed;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Models\Currency;
use App\Models\Interval;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\UserSubscriptionTrial;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTest;

class SubscriptionServiceTest extends FeatureTest
{
    #[DataProvider('nonDeadSubscriptionProvider')]
    public function test_can_only_create_subscription_if_no_other_non_dead_subscription_exists($status)
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
        ])->save();

        /** @var SubscriptionService $service */
        $service = app()->make(SubscriptionService::class);

        $this->expectException(SubscriptionCreationNotAllowedException::class);
        $service->create($slug, $user->id, 1, $tenant);
    }

    public function test_calculate_subscription_trial_days()
    {
        $service = app()->make(SubscriptionService::class);

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'has_trial' => true,
            'trial_interval_count' => 1,
            'trial_interval_id' => Interval::where('slug', 'day')->first()->id,
        ]);

        $this->assertEquals(1, $service->calculateSubscriptionTrialDays($plan));

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'has_trial' => true,
            'trial_interval_count' => 1,
            'trial_interval_id' => Interval::where('slug', 'week')->first()->id,
        ]);

        $this->assertEquals(7, $service->calculateSubscriptionTrialDays($plan));

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'has_trial' => true,
            'trial_interval_count' => 2,
            'trial_interval_id' => Interval::where('slug', 'week')->first()->id,
        ]);

        $this->assertEquals(14, $service->calculateSubscriptionTrialDays($plan));

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'has_trial' => true,
            'trial_interval_count' => 1,
            'trial_interval_id' => Interval::where('slug', 'month')->first()->id,
        ]);

        $this->assertContains($service->calculateSubscriptionTrialDays($plan), [28, 29, 30, 31]);

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'has_trial' => true,
            'trial_interval_count' => 1,
            'trial_interval_id' => Interval::where('slug', 'year')->first()->id,
        ]);

        $this->assertContains($service->calculateSubscriptionTrialDays($plan), [365, 366]);
    }

    public function test_create_subscription_in_case_new_subscription_exists()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        $planPrice = PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'price' => 100,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::NEW->value,
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
        ])->save();

        /** @var SubscriptionService $service */
        $service = app()->make(SubscriptionService::class);

        $subscription = $service->create($slug, $user->id, 1, $tenant);

        $this->assertNotNull($subscription);
    }

    public function test_update_subscription_dispatches_subscribed_event()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::PENDING->value,
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
        ]);

        /** @var SubscriptionService $service */
        $service = app()->make(SubscriptionService::class);

        Event::fake();

        $subscription = $service->updateSubscription($subscription, [
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        Event::assertDispatched(Subscribed::class);
    }

    public function test_update_subscription_dispatches_canceled_event()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
        ]);

        /** @var SubscriptionService $service */
        $service = app()->make(SubscriptionService::class);

        Event::fake();

        $subscription = $service->updateSubscription($subscription, [
            'status' => SubscriptionStatus::CANCELED->value,
        ]);

        Event::assertDispatched(SubscriptionCancelled::class);
    }

    public function test_update_subscription_dispatches_renewed_event()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'ends_at' => now(),
            'tenant_id' => $tenant->id,
        ]);

        /** @var SubscriptionService $service */
        $service = app()->make(SubscriptionService::class);

        Event::fake();

        $subscription = $service->updateSubscription($subscription, [
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
        ]);

        Event::assertDispatched(SubscriptionRenewed::class);
    }

    public function test_can_user_have_subscription_trial()
    {

        config()->set('app.limit_user_trials.enabled', true);
        config()->set('app.limit_user_trials.max_count', 1);

        $service = app()->make(SubscriptionService::class);

        $this->assertTrue($service->canUserHaveSubscriptionTrial(null));

        $user = $this->createUser();
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        $tenant = $this->createTenant();

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'ends_at' => now(),
            'trial_ends_at' => now()->addDays(7),
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue($service->canUserHaveSubscriptionTrial($user));
    }

    public function test_can_user_have_subscription_trial_not_allowed()
    {
        config()->set('app.limit_user_trials.enabled', true);
        config()->set('app.limit_user_trials.max_count', 1);

        $service = app()->make(SubscriptionService::class);

        $user = $this->createUser();
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        $tenant = $this->createTenant();

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'ends_at' => now(),
            'trial_ends_at' => now()->addDays(7),
            'tenant_id' => $tenant->id,
        ]);

        UserSubscriptionTrial::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'trial_ends_at' => now()->addDays(7),
        ]);

        $this->assertFalse($service->canUserHaveSubscriptionTrial($user));
    }

    public function test_find_active_tenant_subscriptions()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $tenant = $this->createTenant();

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ])->save();

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ])->save();

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'ends_at' => now()->subDays(30),
            'tenant_id' => $tenant->id,
        ])->save();

        /** @var SubscriptionService $service */
        $service = app()->make(SubscriptionService::class);

        $subscriptions = $service->findActiveTenantSubscriptions($tenant);

        $this->assertCount(2, $subscriptions);
    }

    public function test_find_active_tenant_subscription_products()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $tenant = $this->createTenant();

        $product1Slug = Str::random();
        $product1 = Product::factory()->create([
            'slug' => $product1Slug,
        ]);

        $product2Slug = Str::random();
        $product2 = Product::factory()->create([
            'slug' => $product2Slug,
        ]);

        $plan1Slug = Str::random();
        $plan1 = Plan::factory()->create([
            'slug' => $plan1Slug,
            'is_active' => true,
            'product_id' => $product1->id,
        ]);

        $plan2Slug = Str::random();
        $plan2 = Plan::factory()->create([
            'slug' => $plan2Slug,
            'is_active' => true,
            'product_id' => $product2->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan1->id,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ])->save();

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan2->id,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ])->save();

        /** @var SubscriptionService $service */
        $service = app()->make(SubscriptionService::class);

        $products = $service->findActiveTenantSubscriptionProducts($tenant);

        $this->assertCount(2, $products);
        $this->assertEquals($product1->id, $products[0]->id);
        $this->assertEquals($product2->id, $products[1]->id);
    }

    public function test_is_user_subscribed_returns_false_for_null_user()
    {
        $tenant = $this->createTenant();
        $service = app()->make(SubscriptionService::class);

        $this->assertFalse($service->isUserSubscribed(null, null, $tenant));
    }

    public function test_is_user_subscribed_returns_false_when_user_has_no_subscriptions()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $service = app()->make(SubscriptionService::class);

        $this->assertFalse($service->isUserSubscribed($user, null, $tenant));
    }

    public function test_is_user_subscribed_returns_true_when_user_has_active_subscription()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $plan = Plan::factory()->create(['is_active' => true]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $service = app()->make(SubscriptionService::class);

        $this->assertTrue($service->isUserSubscribed($user, null, $tenant));
    }

    public function test_is_user_subscribed_returns_false_when_subscription_has_expired()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $plan = Plan::factory()->create(['is_active' => true]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->subDays(1),
            'tenant_id' => $tenant->id,
        ]);

        $service = app()->make(SubscriptionService::class);

        $this->assertFalse($service->isUserSubscribed($user, null, $tenant));
    }

    public function test_is_user_subscribed_returns_false_when_subscription_is_not_active()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $plan = Plan::factory()->create(['is_active' => true]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::CANCELED->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $service = app()->make(SubscriptionService::class);

        $this->assertFalse($service->isUserSubscribed($user, null, $tenant));
    }

    public function test_is_user_subscribed_returns_true_when_product_slug_matches()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $productSlug = Str::random();
        $product = Product::factory()->create(['slug' => $productSlug]);
        $plan = Plan::factory()->create([
            'is_active' => true,
            'product_id' => $product->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $service = app()->make(SubscriptionService::class);

        $this->assertTrue($service->isUserSubscribed($user, $productSlug, $tenant));
    }

    public function test_is_user_subscribed_returns_false_when_product_slug_does_not_match()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $productSlug = Str::random();
        $product = Product::factory()->create(['slug' => $productSlug]);
        $plan = Plan::factory()->create([
            'is_active' => true,
            'product_id' => $product->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $service = app()->make(SubscriptionService::class);

        $this->assertFalse($service->isUserSubscribed($user, Str::random(), $tenant));
    }

    public function test_is_user_subscribed_returns_true_when_product_slug_in_array()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $productSlug = Str::random();
        $product = Product::factory()->create(['slug' => $productSlug]);
        $plan = Plan::factory()->create([
            'is_active' => true,
            'product_id' => $product->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $service = app()->make(SubscriptionService::class);

        $this->assertTrue($service->isUserSubscribed($user, [$productSlug, Str::random()], $tenant));
    }

    public function test_is_user_subscribed_returns_false_when_product_slug_not_in_array()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $productSlug = Str::random();
        $product = Product::factory()->create(['slug' => $productSlug]);
        $plan = Plan::factory()->create([
            'is_active' => true,
            'product_id' => $product->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $service = app()->make(SubscriptionService::class);

        $this->assertFalse($service->isUserSubscribed($user, [Str::random(), Str::random()], $tenant));
    }

    public function test_is_user_subscribed_returns_true_with_multiple_active_subscriptions()
    {
        config()->set('app.multiple_subscriptions_enabled', true);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $product1Slug = Str::random();
        $product2Slug = Str::random();
        $product3Slug = Str::random();

        $product1 = Product::factory()->create(['slug' => $product1Slug]);
        $product2 = Product::factory()->create(['slug' => $product2Slug]);

        $plan1 = Plan::factory()->create([
            'is_active' => true,
            'product_id' => $product1->id,
        ]);

        $plan2 = Plan::factory()->create([
            'is_active' => true,
            'product_id' => $product2->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan1->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan2->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $service = app()->make(SubscriptionService::class);

        $this->assertTrue($service->isUserSubscribed($user, null, $tenant));
        $this->assertTrue($service->isUserSubscribed($user, $product1Slug, $tenant));
        $this->assertTrue($service->isUserSubscribed($user, $product2Slug, $tenant));
        $this->assertFalse($service->isUserSubscribed($user, $product3Slug, $tenant));
    }

    public function test_is_user_subscribed_with_mixed_subscription_statuses()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $product1Slug = Str::random();
        $product2Slug = Str::random();
        $product3Slug = Str::random();

        $product1 = Product::factory()->create(['slug' => $product1Slug]);
        $product2 = Product::factory()->create(['slug' => $product2Slug]);
        $product3 = Product::factory()->create(['slug' => $product3Slug]);

        $plan1 = Plan::factory()->create(['is_active' => true, 'product_id' => $product1->id]);
        $plan2 = Plan::factory()->create(['is_active' => true, 'product_id' => $product2->id]);
        $plan3 = Plan::factory()->create(['is_active' => true, 'product_id' => $product3->id]);

        // Active subscription
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan1->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        // Expired subscription
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan2->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->subDays(1),
            'tenant_id' => $tenant->id,
        ]);

        // Canceled subscription
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan3->id,
            'status' => SubscriptionStatus::CANCELED->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $service = app()->make(SubscriptionService::class);

        $this->assertTrue($service->isUserSubscribed($user, null, $tenant));
        $this->assertTrue($service->isUserSubscribed($user, $product1Slug, $tenant));
        $this->assertFalse($service->isUserSubscribed($user, $product2Slug, $tenant));
        $this->assertFalse($service->isUserSubscribed($user, $product3Slug, $tenant));
    }

    public function test_is_user_subscribed_returns_false_when_checking_different_tenant()
    {
        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        $user = $this->createUser($tenant1);

        // Also add user to second tenant
        $user->tenants()->attach($tenant2->id);

        $productSlug = Str::random();
        $product = Product::factory()->create(['slug' => $productSlug]);
        $plan = Plan::factory()->create([
            'is_active' => true,
            'product_id' => $product->id,
        ]);

        // Create subscription for tenant1
        Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant1->id,
        ]);

        $service = app()->make(SubscriptionService::class);

        // Should return true when checking with tenant1
        $this->assertTrue($service->isUserSubscribed($user, null, $tenant1));
        $this->assertTrue($service->isUserSubscribed($user, $productSlug, $tenant1));

        // Should return false when checking with tenant2 (different tenant)
        $this->assertFalse($service->isUserSubscribed($user, null, $tenant2));
        $this->assertFalse($service->isUserSubscribed($user, $productSlug, $tenant2));
    }

    public function test_change_plan_returns_false_when_tenant_exceeds_new_plan_max_users(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->createUser($tenant);
        $this->createUser($tenant); // 3 users

        $currentPlan = Plan::factory()->create([
            'slug' => Str::random(),
            'is_active' => true,
            'max_users_per_tenant' => 5,
        ]);

        $newPlan = Plan::factory()->create([
            'slug' => Str::random(),
            'is_active' => true,
            'max_users_per_tenant' => 2,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $currentPlan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $paymentProvider = \Mockery::mock(PaymentProviderInterface::class);
        $paymentProvider->shouldNotReceive('changePlan');

        $service = app()->make(SubscriptionService::class);

        $result = $service->changePlan($subscription, $paymentProvider, $newPlan->slug);

        $this->assertFalse($result);
    }

    public function test_change_plan_succeeds_when_tenant_within_new_plan_max_users(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant); // 1 user

        $currentPlan = Plan::factory()->create([
            'slug' => Str::random(),
            'is_active' => true,
            'max_users_per_tenant' => 5,
        ]);

        $newPlan = Plan::factory()->create([
            'slug' => Str::random(),
            'is_active' => true,
            'max_users_per_tenant' => 3,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $currentPlan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $paymentProvider = \Mockery::mock(PaymentProviderInterface::class);
        $paymentProvider->shouldReceive('changePlan')
            ->once()
            ->andReturn(true);

        Event::fake();

        $service = app()->make(SubscriptionService::class);

        $result = $service->changePlan($subscription, $paymentProvider, $newPlan->slug);

        $this->assertTrue($result);
    }

    public function test_change_plan_succeeds_when_new_plan_has_unlimited_users(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->createUser($tenant);
        $this->createUser($tenant);
        $this->createUser($tenant); // 4 users

        $currentPlan = Plan::factory()->create([
            'slug' => Str::random(),
            'is_active' => true,
            'max_users_per_tenant' => 10,
        ]);

        $newPlan = Plan::factory()->create([
            'slug' => Str::random(),
            'is_active' => true,
            'max_users_per_tenant' => 0, // unlimited
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $currentPlan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
            'tenant_id' => $tenant->id,
        ]);

        $paymentProvider = \Mockery::mock(PaymentProviderInterface::class);
        $paymentProvider->shouldReceive('changePlan')
            ->once()
            ->andReturn(true);

        Event::fake();

        $service = app()->make(SubscriptionService::class);

        $result = $service->changePlan($subscription, $paymentProvider, $newPlan->slug);

        $this->assertTrue($result);
    }

    public static function nonDeadSubscriptionProvider()
    {
        return [
            'pending' => [
                'pending',
            ],
            'active' => [
                'active',
            ],
            'paused' => [
                'paused',
            ],
            'past_due' => [
                'past_due',
            ],
        ];
    }
}
