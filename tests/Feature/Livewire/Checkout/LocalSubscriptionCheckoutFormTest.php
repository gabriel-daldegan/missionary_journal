<?php

namespace Tests\Feature\Livewire\Checkout;

use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Constants\SessionConstants;
use App\Constants\SubscriptionStatus;
use App\Constants\SubscriptionType;
use App\Dto\SubscriptionCheckoutDto;
use App\Events\Subscription\Subscribed;
use App\Exceptions\CouldNotCreateLocalSubscriptionException;
use App\Livewire\Checkout\LocalSubscriptionCheckoutForm;
use App\Models\Currency;
use App\Models\Interval;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserSubscriptionTrial;
use App\Services\LoginService;
use App\Services\OneTimePasswordService;
use App\Services\UserService;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Mockery;
use Spatie\OneTimePasswords\Actions\ConsumeOneTimePasswordAction;
use Spatie\OneTimePasswords\Enums\ConsumeOneTimePasswordResult;
use Tests\Feature\FeatureTest;

class LocalSubscriptionCheckoutFormTest extends FeatureTest
{
    public function test_can_checkout_new_user()
    {
        config(['app.trial_without_payment.enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

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

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        Event::fake();

        $email = 'something+'.rand(1, 1000000).'@gmail.com';
        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout')
            ->assertRedirect(route('checkout.subscription.success'));

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());

        $latestSubscription = Subscription::latest()->first();
        Event::assertDispatched(Subscribed::class);
        $this->assertEquals($latestSubscription->type, SubscriptionType::LOCALLY_MANAGED);
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_can_checkout_existing_user()
    {
        config(['app.trial_without_payment.enabled' => true]);

        $sessionDto = new SubscriptionCheckoutDto;

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

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

        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        Event::fake();

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout')
            ->assertRedirect(route('checkout.subscription.success'));

        // assert user is logged in
        $this->assertAuthenticated();

        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());

        $latestSubscription = Subscription::latest()->first();
        Event::assertDispatched(Subscribed::class);
        $this->assertEquals($latestSubscription->type, SubscriptionType::LOCALLY_MANAGED);
    }

    public function test_can_checkout_existing_user_redirect_to_verify_user_with_sms()
    {
        config(['app.trial_without_payment.enabled' => true]);
        config(['app.trial_without_payment.sms_verification_enabled' => true]);

        $sessionDto = new SubscriptionCheckoutDto;

        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

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

        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        Event::fake();

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout')
            ->assertRedirect(route('user.phone-verify'));

        // assert user is logged in
        $this->assertAuthenticated();

        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());

        $latestSubscription = Subscription::latest()->first();
        Event::assertDispatched(Subscribed::class);
        $this->assertEquals($latestSubscription->type, SubscriptionType::LOCALLY_MANAGED);
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_can_not_checkout_when_plan_has_no_trial()
    {
        config(['app.trial_without_payment.enabled' => true]);

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

        $this->expectException(CouldNotCreateLocalSubscriptionException::class);
        $this->expectExceptionMessage('Could not determine local subscription end date');

        $email = 'something+'.rand(1, 1000000).'@gmail.com';
        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout');
    }

    public function test_can_not_checkout_when_user_can_have_no_trial()
    {
        config()->set('app.limit_user_trials.enabled', true);
        config()->set('app.limit_user_trials.max_count', 1);

        config(['app.trial_without_payment.enabled' => true]);

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

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout')
            ->assertRedirect(route('home'));
    }

    public function test_checkout_success_if_plan_type_is_usage_based()
    {
        config(['app.trial_without_payment.enabled' => true]);

        $sessionDto = new SubscriptionCheckoutDto;
        $planSlug = 'plan-slug-'.rand(1, 1000000);

        $sessionDto->planSlug = $planSlug;

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

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

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();
        $tenantsBefore = Tenant::count();

        Event::fake();

        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout')
            ->assertRedirect(route('checkout.subscription.success'));

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());

        // get session
        $sessionDto = session(SessionConstants::SUBSCRIPTION_CHECKOUT_DTO);
        $subscription = Subscription::find($sessionDto->subscriptionId);
        Event::assertDispatched(Subscribed::class);

        $this->assertEquals($subscription->type, SubscriptionType::LOCALLY_MANAGED);
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_can_not_checkout_if_trial_without_payment_is_disabled()
    {
        config(['app.trial_without_payment.enabled' => false]);

        $sessionDto = new SubscriptionCheckoutDto;
        $planSlug = 'plan-slug-'.rand(1, 1000000);

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

        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', $email)
            ->set('password', 'password')
            ->call('checkout')
            ->assertRedirect(route('home'));

        // assert user has not been created
        $this->assertDatabaseMissing('users', [
            'email' => $email,
        ]);

        // assert user is not logged in
        $this->assertGuest();
    }

    public function test_send_otp_code_for_existing_user()
    {
        config(['app.otp_login_enabled' => true, 'app.trial_without_payment.enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'existing'.rand(1, 10000).'@example.com';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true, 'has_trial' => true, 'trial_interval_count' => 7, 'trial_interval_id' => Interval::where('slug', 'day')->first()->id]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 0,
        ]);

        $user = User::factory()->create(['email' => $email]);

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

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    public function test_send_otp_code_for_new_user()
    {
        config(['app.otp_login_enabled' => true, 'app.trial_without_payment.enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'Test User';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true, 'has_trial' => true, 'trial_interval_count' => 7, 'trial_interval_id' => Interval::where('slug', 'day')->first()->id]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 0,
        ]);

        $newUser = User::factory()->make(['email' => $email, 'name' => $name]);

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

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->set('name', $name)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    public function test_verify_otp_and_proceed_with_valid_code()
    {
        config(['app.otp_login_enabled' => true, 'app.trial_without_payment.enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'existing'.rand(1, 10000).'@example.com';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true, 'has_trial' => true, 'trial_interval_count' => 7, 'trial_interval_id' => Interval::where('slug', 'day')->first()->id]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 0,
        ]);

        $user = User::factory()->create(['email' => $email]);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn($user);

        $this->app->instance(UserService::class, $mockUserService);

        // Mock the OTP action to pass validation
        $mockOtpAction = Mockery::mock(ConsumeOneTimePasswordAction::class);
        $mockOtpAction->shouldReceive('execute')
            ->with($user, '123456', Mockery::any())
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

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->set('oneTimePassword', '123456')
            ->call('verifyOtpAndProceed')
            ->assertHasNoErrors();

        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
    }

    public function test_send_otp_code_for_existing_user_with_recaptcha_enabled()
    {
        config(['app.otp_login_enabled' => true, 'app.trial_without_payment.enabled' => true]);
        config(['app.recaptcha_enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'existing'.rand(1, 10000).'@example.com';
        $recaptcha = 'test_recaptcha_token';

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true, 'has_trial' => true, 'trial_interval_count' => 7, 'trial_interval_id' => Interval::where('slug', 'day')->first()->id]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 0,
        ]);

        $user = User::factory()->create(['email' => $email]);

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

        Livewire::test(LocalSubscriptionCheckoutForm::class)
            ->set('email', $email)
            ->set('recaptcha', $recaptcha)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    public function test_send_otp_code_for_new_user_with_recaptcha_enabled()
    {
        config(['app.otp_login_enabled' => true, 'app.trial_without_payment.enabled' => true]);
        config(['app.recaptcha_enabled' => true]);

        $planSlug = 'plan-slug-'.rand(1, 1000000);
        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'New User';
        $recaptcha = 'test_recaptcha_token';
        $userFields = [
            'email' => $email,
            'name' => $name,
            'g-recaptcha-response' => $recaptcha,
        ];

        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = $planSlug;
        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create(['slug' => $planSlug, 'is_active' => true, 'has_trial' => true, 'trial_interval_count' => 7, 'trial_interval_id' => Interval::where('slug', 'day')->first()->id]);
        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 0,
        ]);

        $user = User::factory()->make(['email' => $email, 'name' => $name]);

        $mockUserService = Mockery::mock(UserService::class);
        $mockUserService->shouldReceive('findByEmail')
            ->with($email)
            ->andReturn(null);
        $mockUserService->shouldReceive('createUser')
            ->with(['name' => $name, 'email' => $email], true)
            ->andReturn($user);

        $mockRegisterValidator = Mockery::mock(RegisterValidator::class);
        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);
        $mockRegisterValidator->shouldReceive('validate')
            ->with($userFields, false)
            ->andReturn($validator);

        $mockOtpService = Mockery::mock(OneTimePasswordService::class);
        $mockOtpService->shouldReceive('sendCode')
            ->with($user)
            ->andReturn(true);

        $this->app->instance(UserService::class, $mockUserService);
        $this->app->instance(RegisterValidator::class, $mockRegisterValidator);
        $this->app->instance(OneTimePasswordService::class, $mockOtpService);

        Livewire::test(LocalSubscriptionCheckoutForm::class)
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
