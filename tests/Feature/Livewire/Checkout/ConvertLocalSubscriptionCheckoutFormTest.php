<?php

namespace Tests\Feature\Livewire\Checkout;

use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Constants\SessionConstants;
use App\Constants\SubscriptionStatus;
use App\Constants\SubscriptionType;
use App\Dto\SubscriptionCheckoutDto;
use App\Livewire\Checkout\ConvertLocalSubscriptionCheckoutForm;
use App\Models\Currency;
use App\Models\Interval;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
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

class ConvertLocalSubscriptionCheckoutFormTest extends FeatureTest
{
    public function test_can_checkout()
    {
        $sessionDto = new SubscriptionCheckoutDto;

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
        $user = $this->createUser($tenant, [], [
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
        ]);

        $sessionDto->subscriptionId = $subscription->id;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user is logged in
        $this->assertAuthenticated();
    }

    public function test_can_checkout_adjust_quantity_to_current_number_of_users_in_tenant()
    {
        $sessionDto = new SubscriptionCheckoutDto;

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'type' => PlanType::SEAT_BASED->value,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email1 = 'existing+'.rand(1, 1000000).'@gmail.com';

        $tenant = $this->createTenant();
        $user1 = $this->createUser($tenant, [], [
            'email' => $email1,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $email2 = 'existing+'.rand(1, 1000000).'@gmail.com';
        $user2 = $this->createUser($tenant, [], [
            'email' => $email2,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user1->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
            'quantity' => 1,  // 1 user
        ]);

        $sessionDto->subscriptionId = $subscription->id;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->withArgs([Mockery::any(), Mockery::any(), Mockery::any(), 2])  // 2 users
            ->andReturn([]);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email1)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user is logged in
        $this->assertAuthenticated();
    }

    public function test_can_not_checkout_if_payment_does_not_support_plan_type()
    {
        $sessionDto = new SubscriptionCheckoutDto;

        $planSlug = 'plan-slug-'.rand(1, 1000000);

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

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [], [
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
        ]);

        $sessionDto->subscriptionId = $subscription->id;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(false);

        $paymentProvider->shouldNotReceive('initSubscriptionCheckout');

        $this->expectException(Exception::class);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout');
    }

    public function test_can_not_checkout_if_subscription_does_not_belong_to_user()
    {
        $sessionDto = new SubscriptionCheckoutDto;

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'type' => PlanType::USAGE_BASED->value,
            'has_trial' => true,
            'trial_interval_count' => 7,
            'trial_interval_id' => Interval::where('slug', 'day')->first()->id,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
            'price_per_unit' => 20,
            'type' => PlanPriceType::USAGE_BASED_PER_UNIT->value,
        ]);

        $email1 = 'existing+'.rand(1, 1000000).'@gmail.com';

        $tenant = $this->createTenant();
        $user1 = $this->createUser($tenant, [], [
            'email' => $email1,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $email2 = 'existing+'.rand(1, 1000000).'@gmail.com';
        $user2 = $this->createUser($tenant, [], [
            'email' => $email2,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user1->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
        ]);

        $sessionDto->subscriptionId = $subscription->id;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldNotReceive('initSubscriptionCheckout');

        $this->expectException(Exception::class);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email2)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout');
    }

    public function test_can_not_checkout_if_payment_does_not_support_skipping_trial()
    {
        $sessionDto = new SubscriptionCheckoutDto;

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'type' => PlanType::USAGE_BASED->value,
            'has_trial' => true,
            'trial_interval_count' => 7,
            'trial_interval_id' => Interval::where('slug', 'day')->first()->id,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
            'price_per_unit' => 20,
            'type' => PlanPriceType::USAGE_BASED_PER_UNIT->value,
        ]);

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [], [
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
        ]);

        $sessionDto->subscriptionId = $subscription->id;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('supportsSkippingTrial')
            ->andReturn(false);

        $paymentProvider->shouldNotReceive('initSubscriptionCheckout');

        $this->expectException(Exception::class);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout');
    }

    public function test_checkout_success_if_plan_type_is_usage_based()
    {
        $sessionDto = new SubscriptionCheckoutDto;

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'type' => PlanType::USAGE_BASED->value,
            'has_trial' => true,
            'trial_interval_count' => 7,
            'trial_interval_id' => Interval::where('slug', 'day')->first()->id,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
            'price_per_unit' => 20,
            'type' => PlanPriceType::USAGE_BASED_PER_UNIT->value,
        ]);

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [], [
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
        ]);

        $sessionDto->subscriptionId = $subscription->id;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        $paymentProvider->shouldReceive('supportsSkippingTrial')
            ->andReturn(true);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user is logged in
        $this->assertAuthenticated();
    }

    public function test_can_checkout_overlay_payment()
    {
        $sessionDto = new SubscriptionCheckoutDto;

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'has_trial' => true,
            'trial_interval_count' => 7,
            'trial_interval_id' => Interval::where('slug', 'day')->first()->id,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email = 'existing+'.rand(1, 1000000).'@gmail.com';

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant, [], [
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
        ]);

        $sessionDto->subscriptionId = $subscription->id;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $paymentProvider = $this->addPaymentProvider(false);

        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        $paymentProvider->shouldReceive('supportsSkippingTrial')
            ->andReturn(true);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertDispatched('start-overlay-checkout');

        // assert user is logged in
        $this->assertAuthenticated();
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

    public function test_send_otp_code_for_existing_user()
    {
        config(['app.otp_login_enabled' => true]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $tenant = $this->createTenant();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
        ]);

        $planSlug = 'premium-plan-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $sessionDto->subscriptionId = $subscription->id;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'type' => PlanType::FLAT_RATE->value,
        ]);

        $paymentProvider = $this->addPaymentProvider();
        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email = 'existing'.rand(1, 10000).'@example.com';
        $existingUser = User::factory()->create(['email' => $email]);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($existingUser);

        $mockLoginValidator = Mockery::mock(LoginValidator::class);
        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);
        $mockLoginValidator->shouldReceive('validate')
            ->with(['email' => $email])
            ->andReturn($validator);

        $mockOtpService = Mockery::mock(OneTimePasswordService::class);
        $mockOtpService->shouldReceive('sendCode')
            ->with($existingUser)
            ->andReturn(true);

        $this->app->instance(UserService::class, $mockUserService);
        $this->app->instance(LoginValidator::class, $mockLoginValidator);
        $this->app->instance(OneTimePasswordService::class, $mockOtpService);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    public function test_verify_otp_and_proceed_with_valid_code()
    {
        config(['app.otp_login_enabled' => true]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $tenant = $this->createTenant();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
        ]);

        $planSlug = 'premium-plan-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $sessionDto->subscriptionId = $subscription->id;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => $planSlug,
            'is_active' => true,
            'type' => PlanType::FLAT_RATE->value,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $paymentProvider = $this->addPaymentProvider();
        $paymentProvider->shouldReceive('supportsPlan')
            ->andReturn(true);

        $email = 'existing'.rand(1, 10000).'@example.com';
        $existingUser = User::factory()->create(['email' => $email]);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($existingUser);

        $this->app->instance(UserService::class, $mockUserService);

        // Mock the OTP action to pass validation
        $mockOtpAction = Mockery::mock(ConsumeOneTimePasswordAction::class);
        $mockOtpAction->shouldReceive('execute')
            ->with($existingUser, '123456', Mockery::any())
            ->andReturn(ConsumeOneTimePasswordResult::Ok);
        $this->app->instance(ConsumeOneTimePasswordAction::class, $mockOtpAction);

        // Mock LoginService for authentication
        $mockLoginService = Mockery::mock(LoginService::class);
        $mockLoginService->shouldReceive('authenticateUser')
            ->with($existingUser, true)
            ->andReturnUsing(function ($user) {
                auth()->login($user);
            });
        $this->app->instance(LoginService::class, $mockLoginService);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->set('oneTimePassword', '123456')
            ->call('verifyOtpAndProceed')
            ->assertHasNoErrors();

        $this->assertTrue(auth()->check());
        $this->assertEquals($existingUser->id, auth()->id());
    }

    public function test_send_otp_code_for_existing_user_with_recaptcha_enabled()
    {
        config(['app.otp_login_enabled' => true]);
        config(['app.recaptcha_enabled' => true]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $tenant = $this->createTenant();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
        ]);

        $planSlug = 'premium-plan-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email = 'existing'.rand(1, 10000).'@example.com';
        $recaptcha = 'test_recaptcha_token';
        $existingUser = User::factory()->create(['email' => $email]);

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $mockLoginValidator = Mockery::mock(LoginValidator::class);
        $this->app->instance(LoginValidator::class, $mockLoginValidator);

        $mockLoginValidator
            ->shouldReceive('validate')
            ->once()
            ->with(['email' => $email, 'g-recaptcha-response' => $recaptcha])
            ->andReturn($validator);

        $mockUserService = Mockery::mock(UserService::class);
        $this->app->instance(UserService::class, $mockUserService);

        $mockUserService
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($existingUser);

        $mockOtpService = Mockery::mock(OneTimePasswordService::class);
        $this->app->instance(OneTimePasswordService::class, $mockOtpService);

        $mockOtpService
            ->shouldReceive('sendCode')
            ->once()
            ->with($existingUser)
            ->andReturn(true);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
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

        $user = User::factory()->create();
        $this->actingAs($user);

        $tenant = $this->createTenant();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'type' => SubscriptionType::LOCALLY_MANAGED,
            'tenant_id' => $tenant->id,
        ]);

        $planSlug = 'premium-plan-'.rand(1, 1000000);
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'New User';
        $recaptcha = 'test_recaptcha_token';
        $userFields = [
            'email' => $email,
            'name' => $name,
            'g-recaptcha-response' => $recaptcha,
        ];

        $user = User::factory()->make($userFields);

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $mockRegisterValidator = Mockery::mock(RegisterValidator::class);
        $this->app->instance(RegisterValidator::class, $mockRegisterValidator);

        $mockUserService = Mockery::mock(UserService::class);
        $this->app->instance(UserService::class, $mockUserService);
        $mockOtpService = Mockery::mock(OneTimePasswordService::class);

        $this->app->instance(OneTimePasswordService::class, $mockOtpService);

        $mockRegisterValidator
            ->shouldReceive('validate')
            ->once()
            ->with($userFields, false)
            ->andReturn($validator);

        $mockUserService
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn(null);

        $mockUserService
            ->shouldReceive('createUser')
            ->once()
            ->with([
                'email' => $email,
                'name' => $name,
            ], true)
            ->andReturn($user);

        $mockOtpService
            ->shouldReceive('sendCode')
            ->once()
            ->with($user)
            ->andReturn(true);

        Livewire::test(ConvertLocalSubscriptionCheckoutForm::class)
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
}
