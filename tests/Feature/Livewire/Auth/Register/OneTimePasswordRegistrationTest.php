<?php

namespace Tests\Feature\Livewire\Auth\Register;

use App\Livewire\Auth\Register\OneTimePasswordRegistration;
use App\Models\User;
use App\Services\OneTimePasswordService;
use App\Services\UserService;
use App\Validator\RegisterValidator;
use Illuminate\Contracts\Validation\Validator;
use Livewire\Livewire;
use Mockery;
use Tests\Feature\FeatureTest;

class OneTimePasswordRegistrationTest extends FeatureTest
{
    private RegisterValidator $mockRegisterValidator;

    private UserService $mockUserService;

    private OneTimePasswordService $mockOtpService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRegisterValidator = Mockery::mock(RegisterValidator::class);
        $this->mockUserService = Mockery::mock(UserService::class);
        $this->mockOtpService = Mockery::mock(OneTimePasswordService::class);

        $this->app->instance(RegisterValidator::class, $this->mockRegisterValidator);
        $this->app->instance(UserService::class, $this->mockUserService);
        $this->app->instance(OneTimePasswordService::class, $this->mockOtpService);
    }

    public function test_renders_registration_form_view()
    {
        Livewire::test(OneTimePasswordRegistration::class)
            ->assertViewIs('livewire.auth.register.registration-form');
    }

    public function test_successful_registration_with_valid_data()
    {
        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'New User';
        $userFields = [
            'email' => $email,
            'name' => $name,
        ];

        $user = User::factory()->make($userFields);

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $this->mockRegisterValidator
            ->shouldReceive('validate')
            ->once()
            ->with($userFields)
            ->andReturn($validator);

        $this->mockUserService
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn(null);

        $this->mockUserService
            ->shouldReceive('createUser')
            ->once()
            ->with($userFields, true)
            ->andReturn($user);

        $this->mockOtpService
            ->shouldReceive('sendCode')
            ->once()
            ->with($user)
            ->andReturn(true);

        Livewire::test(OneTimePasswordRegistration::class)
            ->set('email', $email)
            ->set('name', $name)
            ->call('register')
            ->assertRedirect(route('login', ['email' => $email]))
            ->assertHasNoErrors();
    }

    public function test_registration_with_existing_user_email()
    {
        $email = 'existing'.rand(1, 10000).'@example.com';
        $name = 'New User';
        $userFields = [
            'email' => $email,
            'name' => $name,
        ];

        $existingUser = User::factory()->create(['email' => $email]);

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $this->mockRegisterValidator
            ->shouldReceive('validate')
            ->once()
            ->with($userFields)
            ->andReturn($validator);

        $this->mockUserService
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn($existingUser);

        Livewire::test(OneTimePasswordRegistration::class)
            ->set('email', $email)
            ->set('name', $name)
            ->call('register')
            ->assertHasErrors(['email' => 'This email is already registered. Please log in instead.']);
    }

    public function test_registration_when_otp_service_fails_to_send_code()
    {
        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'New User';
        $userFields = [
            'email' => $email,
            'name' => $name,
        ];

        $user = User::factory()->make($userFields);

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $this->mockRegisterValidator
            ->shouldReceive('validate')
            ->once()
            ->with($userFields)
            ->andReturn($validator);

        $this->mockUserService
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn(null);

        $this->mockUserService
            ->shouldReceive('createUser')
            ->once()
            ->with($userFields, true)
            ->andReturn($user);

        $this->mockOtpService
            ->shouldReceive('sendCode')
            ->once()
            ->with($user)
            ->andReturn(false);

        Livewire::test(OneTimePasswordRegistration::class)
            ->set('email', $email)
            ->set('name', $name)
            ->call('register')
            ->assertHasErrors(['email' => 'Failed to send one-time password. Please try again later.']);
    }

    public function test_registration_with_recaptcha_enabled()
    {
        config(['app.recaptcha_enabled' => true]);

        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'New User';
        $recaptcha = 'test_recaptcha_token';
        $userFields = [
            'email' => $email,
            'name' => $name,
            'g-recaptcha-response' => $recaptcha,
        ];

        $user = User::factory()->make(['email' => $email, 'name' => $name]);

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $this->mockRegisterValidator
            ->shouldReceive('validate')
            ->once()
            ->with($userFields)
            ->andReturn($validator);

        $this->mockUserService
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn(null);

        $this->mockUserService
            ->shouldReceive('createUser')
            ->once()
            ->with($userFields, true)
            ->andReturn($user);

        $this->mockOtpService
            ->shouldReceive('sendCode')
            ->once()
            ->with($user)
            ->andReturn(true);

        Livewire::test(OneTimePasswordRegistration::class)
            ->set('email', $email)
            ->set('name', $name)
            ->set('recaptcha', $recaptcha)
            ->call('register')
            ->assertRedirect(route('login', ['email' => $email]))
            ->assertHasNoErrors();
    }

    public function test_registration_with_recaptcha_disabled()
    {
        config(['app.recaptcha_enabled' => false]);

        $email = 'newuser'.rand(1, 10000).'@example.com';
        $name = 'New User';
        $recaptcha = 'test_recaptcha_token';
        $userFields = [
            'email' => $email,
            'name' => $name,
        ];

        $user = User::factory()->make(['email' => $email, 'name' => $name]);

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $this->mockRegisterValidator
            ->shouldReceive('validate')
            ->once()
            ->with($userFields)
            ->andReturn($validator);

        $this->mockUserService
            ->shouldReceive('findByEmail')
            ->once()
            ->with($email)
            ->andReturn(null);

        $this->mockUserService
            ->shouldReceive('createUser')
            ->once()
            ->with($userFields, true)
            ->andReturn($user);

        $this->mockOtpService
            ->shouldReceive('sendCode')
            ->once()
            ->with($user)
            ->andReturn(true);

        Livewire::test(OneTimePasswordRegistration::class)
            ->set('email', $email)
            ->set('name', $name)
            ->set('recaptcha', $recaptcha)
            ->call('register')
            ->assertRedirect(route('login', ['email' => $email]))
            ->assertHasNoErrors();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
