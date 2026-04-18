<?php

namespace Tests\Feature\Livewire\Checkout;

use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Constants\SessionConstants;
use App\Constants\SubscriptionStatus;
use App\Constants\TenancyPermissionConstants;
use App\Dto\SubscriptionCheckoutDto;
use App\Livewire\Checkout\SubscriptionCheckoutForm;
use App\Models\Currency;
use App\Models\Interval;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSubscriptionTrial;
use App\Services\LoginService;
use App\Services\OneTimePasswordService;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Services\PaymentProviders\PaymentService;
use App\Services\UserService;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Livewire\Livewire;
use Mockery;
use Spatie\OneTimePasswords\Actions\ConsumeOneTimePasswordAction;
use Spatie\OneTimePasswords\Enums\ConsumeOneTimePasswordResult;
use Tests\Feature\FeatureTest;

class SubscriptionCheckoutFormTest extends FeatureTest
{
    public function test_can_checkout_new_user()
    {
        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();
        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_can_checkout_existing_user()
    {
        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_can_checkout_one_subscription_per_tenant_when_multiple_subscriptions_is_disabled()
    {
        config()->set('app.tenant_multiple_subscriptions_enabled', false);

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [
            TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS,
        ], [
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $sessionDto->tenantUuid = $tenant->uuid;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $existingSubscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'ends_at' => now(),
            'tenant_id' => $tenant->id,
        ]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
        $this->assertEquals($tenantsBefore + 1, Tenant::count());

        $sessionDto = session(SessionConstants::SUBSCRIPTION_CHECKOUT_DTO);

        $this->assertNotEquals($existingSubscription->tenant_id, Subscription::find($sessionDto->subscriptionId)->tenant_id);
    }

    public function test_can_checkout_multiple_subscriptions_per_tenant_when_multiple_subscriptions_is_enabled()
    {
        config()->set('app.tenant_multiple_subscriptions_enabled', true);

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [
            TenancyPermissionConstants::PERMISSION_CREATE_SUBSCRIPTIONS,
        ], [
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $sessionDto->tenantUuid = $tenant->uuid;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $existingSubscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'ends_at' => now(),
            'tenant_id' => $tenant->id,
        ]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
        $this->assertEquals($tenantsBefore, Tenant::count());

        $sessionDto = session(SessionConstants::SUBSCRIPTION_CHECKOUT_DTO);

        $this->assertEquals($existingSubscription->tenant_id, Subscription::find($sessionDto->subscriptionId)->tenant_id);
    }

    public function test_can_checkout_existing_user_no_trial_if_user_is_not_eligible()
    {
        config()->set('app.limit_user_trials.enabled', true);
        config()->set('app.limit_user_trials.max_count', 1);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'has_trial' => true,
            'trial_interval_count' => 1,
            'trial_interval_id' => Interval::where('slug', 'week')->first()->id,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
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

        $this->actingAs($user);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->assertDontSeeHtml('trial');
    }

    public function test_can_not_checkout_if_payment_does_not_support_plan_type()
    {
        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'type' => PlanType::USAGE_BASED->value,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
            'price_per_unit' => 20,
            'type' => PlanPriceType::USAGE_BASED_PER_UNIT->value,
        ]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(false);

        $paymentProvider->shouldNotReceive('initSubscriptionCheckout');

        $this->expectException(Exception::class);

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout');
    }

    public function test_checkout_success_if_plan_type_is_usage_based()
    {
        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'type' => PlanType::USAGE_BASED->value,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
            'price_per_unit' => 20,
            'type' => PlanPriceType::USAGE_BASED_PER_UNIT->value,
        ]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
    }

    public function test_can_checkout_overlay_payment()
    {
        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $paymentProvider = $this->addPaymentProvider(false);

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertDispatched('start-overlay-checkout');

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_can_checkout_offline_payment()
    {
        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $paymentProvider = $this->addOfflinePaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();

        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore-offline')
            ->call('checkout')
            ->assertRedirectToRoute('checkout.subscription.success');

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
    }

    public function test_send_otp_code_for_existing_user()
    {
        config(['app.otp_login_enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'existing'.rand(1, 10000).'@example.com';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $user = User::factory()->create(['email' => $email]);

        $paymentProvider = $this->addPaymentProvider();
        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($user);

        $mockLoginValidator = Mockery::mock(LoginValidator::class);
        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);
        $mockLoginValidator->shouldReceive('validate')
            ->with(['email' => $email])
            ->andReturn($validator);

        $mockOtpService = Mockery::mock(OneTimePasswordService::class);
        $mockOtpService->shouldReceive('sendCode')
            ->with($user)
            ->andReturn(true);

        $this->app->instance(UserService::class, $mockUserService);
        $this->app->instance(LoginValidator::class, $mockLoginValidator);
        $this->app->instance(OneTimePasswordService::class, $mockOtpService);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    public function test_send_otp_code_for_new_user()
    {
        config(['app.otp_login_enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'Test User';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $newUser = User::factory()->make(['email' => $email, 'name' => $name]);

        $paymentProvider = $this->addPaymentProvider();
        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn(null);
        $mockUserService->shouldReceive('createUser')
            ->with(['name' => $name, 'email' => $email], true)
            ->andReturn($newUser);

        $mockRegisterValidator = Mockery::mock(RegisterValidator::class);
        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);
        $mockRegisterValidator->shouldReceive('validate')
            ->with(['name' => $name, 'email' => $email], false)
            ->andReturn($validator);

        $mockOtpService = Mockery::mock(OneTimePasswordService::class);
        $mockOtpService->shouldReceive('sendCode')
            ->with($newUser)
            ->andReturn(true);

        $this->app->instance(UserService::class, $mockUserService);
        $this->app->instance(RegisterValidator::class, $mockRegisterValidator);
        $this->app->instance(OneTimePasswordService::class, $mockOtpService);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->set('name', $name)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    public function test_send_otp_code_fails_when_service_returns_false()
    {
        config(['app.otp_login_enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'existing'.rand(1, 10000).'@example.com';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $user = User::factory()->create(['email' => $email]);

        $paymentProvider = $this->addPaymentProvider();
        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($user);

        $mockLoginValidator = Mockery::mock(LoginValidator::class);
        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);
        $mockLoginValidator->shouldReceive('validate')
            ->with(['email' => $email])
            ->andReturn($validator);

        $mockOtpService = Mockery::mock(OneTimePasswordService::class);
        $mockOtpService->shouldReceive('sendCode')
            ->with($user)
            ->andReturn(false);

        $this->app->instance(UserService::class, $mockUserService);
        $this->app->instance(LoginValidator::class, $mockLoginValidator);
        $this->app->instance(OneTimePasswordService::class, $mockOtpService);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', false)
            ->assertHasErrors(['email' => 'Failed to send one-time password. Please try again later.']);
    }

    public function test_verify_otp_and_proceed_with_valid_code()
    {
        config(['app.otp_login_enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'existing'.rand(1, 10000).'@example.com';
        $otpCode = '123456';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $user = User::factory()->create(['email' => $email]);

        $paymentProvider = $this->addPaymentProvider();
        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($user);

        $this->app->instance(UserService::class, $mockUserService);

        // Mock the OTP action to pass validation
        $mockOtpAction = Mockery::mock(ConsumeOneTimePasswordAction::class);
        $mockOtpAction->shouldReceive('execute')
            ->with($user, $otpCode, Mockery::any())
            ->andReturn(ConsumeOneTimePasswordResult::Ok);
        $this->app->instance(ConsumeOneTimePasswordAction::class, $mockOtpAction);

        // Mock LoginService for authentication
        $mockLoginService = Mockery::mock(LoginService::class);
        $mockLoginService->shouldReceive('authenticateUser')
            ->with($user, true)
            ->andReturnUsing(function ($user) {
                auth()->login($user);
            });
        $this->app->instance(LoginService::class, $mockLoginService);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->set('oneTimePassword', $otpCode)
            ->call('verifyOtpAndProceed')
            ->assertHasNoErrors();

        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
    }

    public function test_verify_otp_and_proceed_with_user_not_found()
    {
        config(['app.otp_login_enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'nonexistent'.rand(1, 10000).'@example.com';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $paymentProvider = $this->addPaymentProvider();
        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn(null);

        $this->app->instance(UserService::class, $mockUserService);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->set('oneTimePassword', '123456')
            ->call('verifyOtpAndProceed')
            ->assertHasErrors(['oneTimePassword' => 'User not found.']);
    }

    public function test_is_checkout_button_enabled_with_otp_enabled()
    {
        config(['app.otp_login_enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $paymentProvider = $this->addPaymentProvider();
        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        // Test when not showing OTP form
        $component = Livewire::test(SubscriptionCheckoutForm::class);
        $this->assertFalse($component->instance()->isCheckoutButtonEnabled());

        // Test when showing OTP form but no email or OTP
        $component->set('showOtpForm', true);
        $this->assertFalse($component->instance()->isCheckoutButtonEnabled());

        // Test when showing OTP form with email and OTP
        $component->set('email', 'test@example.com')
            ->set('oneTimePassword', '123456');
        $this->assertTrue($component->instance()->isCheckoutButtonEnabled());
    }

    public function test_send_otp_code_for_existing_user_with_recaptcha_enabled()
    {
        config(['app.otp_login_enabled' => true]);
        config(['app.recaptcha_enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'existing'.rand(1, 10000).'@example.com';
        $recaptcha = 'test_recaptcha_token';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $user = User::factory()->create(['email' => $email]);

        $paymentProvider = $this->addPaymentProvider();
        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($user);

        $mockLoginValidator = Mockery::mock(LoginValidator::class);
        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);
        $mockLoginValidator->shouldReceive('validate')
            ->with(['email' => $email, 'g-recaptcha-response' => $recaptcha])
            ->andReturn($validator);

        $mockOtpService = Mockery::mock(OneTimePasswordService::class);
        $mockOtpService->shouldReceive('sendCode')
            ->with($user)
            ->andReturn(true);

        $this->app->instance(UserService::class, $mockUserService);
        $this->app->instance(LoginValidator::class, $mockLoginValidator);
        $this->app->instance(OneTimePasswordService::class, $mockOtpService);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->set('recaptcha', $recaptcha)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    public function test_send_otp_code_for_new_user_with_recaptcha_enabled()
    {
        config(['app.otp_login_enabled' => true]);
        config(['app.recaptcha_enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'Test User';
        $recaptcha = 'test_recaptcha_token';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $newUser = User::factory()->make(['email' => $email, 'name' => $name]);

        $paymentProvider = $this->addPaymentProvider();
        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn(null);
        $mockUserService->shouldReceive('createUser')
            ->with(['name' => $name, 'email' => $email], true)
            ->andReturn($newUser);

        $mockRegisterValidator = Mockery::mock(RegisterValidator::class);
        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);
        $mockRegisterValidator->shouldReceive('validate')
            ->with(['name' => $name, 'email' => $email, 'g-recaptcha-response' => $recaptcha], false)
            ->andReturn($validator);

        $mockOtpService = Mockery::mock(OneTimePasswordService::class);
        $mockOtpService->shouldReceive('sendCode')
            ->with($newUser)
            ->andReturn(true);

        $this->app->instance(UserService::class, $mockUserService);
        $this->app->instance(RegisterValidator::class, $mockRegisterValidator);
        $this->app->instance(OneTimePasswordService::class, $mockOtpService);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->set('name', $name)
            ->set('recaptcha', $recaptcha)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function addOfflinePaymentProvider()
    {
        // find or create payment provider
        PaymentProvider::updateOrCreate([
            'slug' => 'paymore-offline',
        ], [
            'name' => 'Paymore Offline',
            'is_active' => true,
            'type' => 'any',
        ]);

        $mock = Mockery::mock(PaymentProviderInterface::class);

        $mock->shouldReceive('isRedirectProvider')
            ->andReturn(false);

        $mock->shouldReceive('getSlug')
            ->andReturn('paymore-offline');

        $mock->shouldReceive('getName')
            ->andReturn('Paymore Offline');

        $mock->shouldReceive('isOverlayProvider')
            ->andReturn(false);

        $this->app->instance(PaymentProviderInterface::class, $mock);

        $this->app->bind(PaymentService::class, function () use ($mock) {
            return new PaymentService($mock);
        });

        return $mock;
    }

    private function addPaymentProvider(bool $isRedirect = true)
    {
        // find or create payment provider
        PaymentProvider::updateOrCreate([
            'slug' => 'paymore',
        ], [
            'name' => 'Paymore',
            'is_active' => true,
            'type' => 'any',
        ]);

        $mock = Mockery::mock(PaymentProviderInterface::class);

        $mock->shouldReceive('isRedirectProvider')
            ->andReturn($isRedirect);

        $mock->shouldReceive('getSlug')
            ->andReturn('paymore');

        $mock->shouldReceive('getName')
            ->andReturn('Paymore');

        $mock->shouldReceive('isOverlayProvider')
            ->andReturn(! $isRedirect);

        if ($isRedirect) {
            $mock->shouldReceive('createSubscriptionCheckoutRedirectLink')
                ->andReturn('http://paymore.com/checkout');
        }

        $this->app->instance(PaymentProviderInterface::class, $mock);

        $this->app->bind(PaymentService::class, function () use ($mock) {
            return new PaymentService($mock);
        });

        return $mock;
    }
}
