<?php

namespace Tests\Feature\Livewire\Checkout;

use App\Constants\OrderStatus;
use App\Dto\CartDto;
use App\Dto\CartItemDto;
use App\Events\Order\Ordered;
use App\Livewire\Checkout\ProductCheckoutForm;
use App\Models\Currency;
use App\Models\OneTimeProduct;
use App\Models\OneTimeProductPrice;
use App\Models\Order;
use App\Models\PaymentProvider;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoginService;
use App\Services\OneTimePasswordService;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Services\PaymentProviders\PaymentService;
use App\Services\SessionService;
use App\Services\UserService;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Spatie\OneTimePasswords\Actions\ConsumeOneTimePasswordAction;
use Spatie\OneTimePasswords\Enums\ConsumeOneTimePasswordResult;
use Tests\Feature\FeatureTest;

class ProductCheckoutFormTest extends FeatureTest
{
    public function test_can_checkout_new_user()
    {
        Event::fake();

        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-6',
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $this->addPaymentProvider();

        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);

            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

        // get number of orders before checkout
        $ordersBefore = Order::count();

        $tenantsBefore = Tenant::count();

        Livewire::test(ProductCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', 'something@gmail.com')
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        Event::assertDispatched(Registered::class);

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => 'something@gmail.com',
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($ordersBefore + 1, Order::count());
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_can_checkout_existing_user()
    {
        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-7',
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $user = User::factory()->create([
            'email' => 'existing@gmail.com',
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $this->addPaymentProvider();

        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);

            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

        // get number of orders before checkout
        $ordersBefore = Order::count();
        $tenantsBefore = Tenant::count();

        Livewire::test(ProductCheckoutForm::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => 'something@gmail.com',
        ]);

        // assert order has been created
        $this->assertEquals($ordersBefore + 1, Order::count());
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
    }

    public function test_checkout_free_product()
    {
        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-'.str()->random(5),
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 0,
        ]);

        $user = User::factory()->create([
            'email' => 'existing-'.str()->random(5).'@gmail.com',
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        PaymentProvider::updateOrCreate([
            'slug' => 'paymore',
        ], [
            'name' => 'Paymore',
            'is_active' => true,
            'type' => 'any',
        ]);

        $mock = Mockery::mock(PaymentProviderInterface::class);
        $mock->shouldNotReceive('initProductCheckout');

        $mock->shouldNotReceive('isRedirectProvider');

        $mock->shouldReceive('getSlug')
            ->andReturn('paymore');

        $mock->shouldReceive('getName')
            ->andReturn('Paymore');

        $mock->shouldNotReceive('isOverlayProvider');

        $mock->shouldReceive('supportsOneTimePurchaseProductQuantity')
            ->andReturn(true);

        $this->app->instance(PaymentProviderInterface::class, $mock);

        $this->app->bind(PaymentService::class, function () use ($mock) {
            return new PaymentService($mock);
        });

        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);

            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

        // get number of orders before checkout
        $ordersBefore = Order::count();
        $tenantsBefore = Tenant::count();

        Event::fake();

        Livewire::test(ProductCheckoutForm::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('checkout');

        Event::assertDispatched(Ordered::class);

        // assert order has been created
        $this->assertEquals($ordersBefore + 1, Order::count());

        $latestOrder = Order::latest('id')->first();
        $this->assertEquals(OrderStatus::SUCCESS->value, $latestOrder->status);
        $this->assertEquals(true, $latestOrder->is_local);

        $this->assertEquals($tenantsBefore + 1, Tenant::count());

        $mock->shouldReceive('supportsOneTimePurchaseProductQuantity')
            ->andReturn(true);

        $this->app->instance(PaymentProviderInterface::class, $mock);

        $this->app->bind(PaymentService::class, function () use ($mock) {
            return new PaymentService($mock);
        });
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
        $mock->shouldReceive('initProductCheckout')
            ->once()
            ->andReturn([]);

        $mock->shouldReceive('isRedirectProvider')
            ->andReturn(false);

        $mock->shouldReceive('getSlug')
            ->andReturn('paymore-offline');

        $mock->shouldReceive('getName')
            ->andReturn('Paymore Offline');

        $mock->shouldReceive('isOverlayProvider')
            ->andReturn(false);

        $mock->shouldReceive('supportsOneTimePurchaseProductQuantity')
            ->andReturn(true);

        $this->app->instance(PaymentProviderInterface::class, $mock);

        $this->app->bind(PaymentService::class, function () use ($mock) {
            return new PaymentService($mock);
        });
    }

    public function test_can_checkout_overlay_payment()
    {
        Event::fake();

        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-8',
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $this->addPaymentProvider(false);

        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);

            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

        // get number of orders before checkout
        $ordersBefore = Order::count();
        $tenantsBefore = Tenant::count();

        Livewire::test(ProductCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', 'something2@gmail.com')
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertDispatched('start-overlay-checkout');

        Event::assertDispatched(Registered::class);

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => 'something2@gmail.com',
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($ordersBefore + 1, Order::count());
        $this->assertEquals($tenantsBefore + 1, Tenant::count());
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
        $mock->shouldReceive('initProductCheckout')
            ->once()
            ->andReturn([]);

        $mock->shouldReceive('isRedirectProvider')
            ->andReturn($isRedirect);

        $mock->shouldReceive('getSlug')
            ->andReturn('paymore');

        $mock->shouldReceive('getName')
            ->andReturn('Paymore');

        $mock->shouldReceive('isOverlayProvider')
            ->andReturn(! $isRedirect);

        if ($isRedirect) {
            $mock->shouldReceive('createProductCheckoutRedirectLink')
                ->andReturn('http://paymore.com/checkout');
        }

        $this->app->instance(PaymentProviderInterface::class, $mock);

        $this->app->bind(PaymentService::class, function () use ($mock) {
            return new PaymentService($mock);
        });

        return $mock;
    }

    public function test_can_checkout_offline_payment()
    {
        Event::fake();

        $slug = 'product-slug-'.str()->random(5);
        $product = OneTimeProduct::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $this->addOfflinePaymentProvider();

        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);

            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

        // get number of orders before checkout
        $ordersBefore = Order::count();

        Livewire::test(ProductCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', 'something3@gmail.com')
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore-offline')
            ->call('checkout')
            ->assertRedirectToRoute('checkout.product.success');

        Event::assertDispatched(Registered::class);

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => 'something3@gmail.com',
        ]);

        // assert order has been created
        $this->assertDatabaseHas('orders', [
            'user_id' => auth()->id(),
            'status' => OrderStatus::PENDING->value,
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($ordersBefore + 1, Order::count());
    }

    public function test_can_checkout_quantity()
    {
        Event::fake();

        $slug = 'product-slug-'.str()->random(5);
        $product = OneTimeProduct::factory()->create([
            'slug' => $slug,
            'is_active' => true,
            'max_quantity' => 5,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $this->addOfflinePaymentProvider();

        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->quantity = 3; // Set quantity to 3
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);

            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

        // get number of orders before checkout
        $ordersBefore = Order::count();

        Livewire::test(ProductCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', 'something5@gmail.com')
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore-offline')
            ->call('checkout')
            ->assertRedirectToRoute('checkout.product.success');

        Event::assertDispatched(Registered::class);

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => 'something5@gmail.com',
        ]);

        // assert order has been created
        $this->assertDatabaseHas('orders', [
            'user_id' => auth()->id(),
            'status' => OrderStatus::PENDING->value,
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($ordersBefore + 1, Order::count());

        $checkoutDto = app(SessionService::class)->getCartDto();

        $latestOrder = Order::find($checkoutDto->orderId);
        $this->assertEquals(3, $latestOrder->items->first()->quantity);
    }

    public function test_send_otp_code_for_existing_user()
    {
        config(['app.otp_login_enabled' => true]);

        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-otp-'.rand(1, 1000000),
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 50,
        ]);

        $email = 'existing'.rand(1, 10000).'@example.com';
        $user = User::factory()->create(['email' => $email]);

        $this->addPaymentProviderForRendering();

        // Mock SessionService to provide cart data
        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);
            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

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

        Livewire::test(ProductCheckoutForm::class)
            ->set('email', $email)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    public function test_send_otp_code_for_new_user()
    {
        config(['app.otp_login_enabled' => true]);

        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-otp-'.rand(1, 1000000),
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 50,
        ]);

        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'Test User';

        $this->addPaymentProviderForRendering();

        // Mock SessionService to provide cart data
        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);
            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

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

        Livewire::test(ProductCheckoutForm::class)
            ->set('email', $email)
            ->set('name', $name)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    public function test_verify_otp_and_proceed_with_valid_code()
    {
        config(['app.otp_login_enabled' => true]);

        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-otp-'.rand(1, 1000000),
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 50,
        ]);

        $email = 'existing'.rand(1, 10000).'@example.com';
        $user = User::factory()->create(['email' => $email]);

        $this->addPaymentProviderForRendering();

        // Mock SessionService to provide cart data
        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);
            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

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

        Livewire::test(ProductCheckoutForm::class)
            ->set('email', $email)
            ->set('oneTimePassword', '123456')
            ->call('verifyOtpAndProceed')
            ->assertHasNoErrors();

        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
    }

    public function test_is_checkout_button_enabled_with_otp_enabled()
    {
        config(['app.otp_login_enabled' => true]);

        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-otp-'.rand(1, 1000000),
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 50,
        ]);

        $this->addPaymentProviderForRendering();

        // Mock SessionService to provide cart data
        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);
            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

        // Test when not showing OTP form
        $component = Livewire::test(ProductCheckoutForm::class);
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

        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-recaptcha-'.rand(1, 1000000),
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 50,
        ]);

        $email = 'existing'.rand(1, 10000).'@example.com';
        $recaptcha = 'test_recaptcha_token';
        $user = User::factory()->create(['email' => $email]);

        $this->addPaymentProviderForRendering();

        // Mock SessionService to provide cart data
        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);
            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

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

        Livewire::test(ProductCheckoutForm::class)
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

        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-recaptcha-new-'.rand(1, 1000000),
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 50,
        ]);

        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'Test User';
        $recaptcha = 'test_recaptcha_token';
        $newUser = User::factory()->make(['email' => $email, 'name' => $name]);

        $this->addPaymentProviderForRendering();

        // Mock SessionService to provide cart data
        $this->instance(SessionService::class, Mockery::mock(SessionService::class, function (MockInterface $mock) use ($product) {
            $cartDto = new CartDto;
            $cartItem = new CartItemDto;
            $cartItem->productId = $product->id;
            $cartDto->items = [$cartItem];
            $mock->shouldReceive('getCartDto')->andReturn($cartDto);
            $mock->shouldReceive('saveCartDto');
            $mock->shouldReceive('getCouponCode')->andReturn(null);
            $mock->shouldReceive('clearCouponCode');
        }));

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

        Livewire::test(ProductCheckoutForm::class)
            ->set('email', $email)
            ->set('name', $name)
            ->set('recaptcha', $recaptcha)
            ->call('sendOtpCode')
            ->assertSet('showOtpForm', true)
            ->assertHasNoErrors();
    }

    private function addPaymentProviderForRendering()
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
            ->andReturn(true);
        $mock->shouldReceive('getSlug')
            ->andReturn('paymore');
        $mock->shouldReceive('getName')
            ->andReturn('Paymore');
        $mock->shouldReceive('isOverlayProvider')
            ->andReturn(false);
        $mock->shouldReceive('supportsOneTimePurchaseProductQuantity')
            ->andReturn(true);
        $this->app->instance(PaymentProviderInterface::class, $mock);
        $this->app->bind(PaymentService::class, function () use ($mock) {
            return new PaymentService($mock);
        });

        return $mock;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function addCartSession(OneTimeProduct $product)
    {
        $cartDto = new CartDto;
        $cartDto->items = [
            new CartItemDto($product->slug, 1),
        ];

        session()->put('cart', $cartDto);
    }
}
