<?php

namespace Tests\Feature\Livewire\Auth\Login;

use App\Livewire\Auth\Login\OneTimePasswordLogin;
use App\Models\User;
use App\Services\OneTimePasswordService;
use App\Validator\LoginValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Mockery;
use Tests\Feature\FeatureTest;

class OneTimePasswordLoginTest extends FeatureTest
{
    private OneTimePasswordService $mockOtpService;

    private LoginValidator $mockLoginValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockOtpService = Mockery::mock(OneTimePasswordService::class);
        $this->mockLoginValidator = Mockery::mock(LoginValidator::class);

        $this->app->instance(OneTimePasswordService::class, $this->mockOtpService);
        $this->app->instance(LoginValidator::class, $this->mockLoginValidator);
    }

    public function test_renders_login_form_view()
    {
        Livewire::test(OneTimePasswordLogin::class)
            ->assertViewIs('livewire.auth.login.email-form');
    }

    public function test_mount_with_email_parameter()
    {
        $email = 'test@example.com';

        $component = Livewire::test(OneTimePasswordLogin::class, ['email' => $email]);
        $this->assertEquals($email, $component->get('email'));
    }

    public function test_mount_with_redirect_to_parameter()
    {
        $redirectTo = '/dashboard';

        $component = Livewire::test(OneTimePasswordLogin::class, ['redirectTo' => $redirectTo]);
        $this->assertEquals($redirectTo, $component->get('redirectTo'));
    }

    public function test_submit_email_with_valid_data_and_existing_user()
    {
        $email = 'existing'.rand(1, 10000).'@example.com';
        $user = User::factory()->create(['email' => $email]);

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $this->mockLoginValidator
            ->shouldReceive('validate')
            ->once()
            ->with(['email' => $email])
            ->andReturn($validator);

        $this->mockOtpService
            ->shouldReceive('sendCode')
            ->once()
            ->withArgs(function ($userArg) use ($user) {
                return $userArg->email === $user->email;
            })
            ->andReturn(true);

        Livewire::test(OneTimePasswordLogin::class)
            ->set('email', $email)
            ->call('submitEmail')
            ->assertSet('displayingEmailForm', false)
            ->assertHasNoErrors();
    }

    public function test_submit_email_with_non_existing_user()
    {
        $email = 'nonexistent'.rand(1, 10000).'@example.com';

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $this->mockLoginValidator
            ->shouldReceive('validate')
            ->once()
            ->with(['email' => $email])
            ->andReturn($validator);

        Livewire::test(OneTimePasswordLogin::class)
            ->set('email', $email)
            ->call('submitEmail')
            ->assertHasErrors(['email' => 'We could not find a user with that email address.'])
            ->assertSet('displayingEmailForm', true);
    }

    public function test_submit_email_when_otp_service_fails_to_send_code()
    {
        $email = 'existing'.rand(1, 10000).'@example.com';
        $user = User::factory()->create(['email' => $email]);

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $this->mockLoginValidator
            ->shouldReceive('validate')
            ->once()
            ->with(['email' => $email])
            ->andReturn($validator);

        $this->mockOtpService
            ->shouldReceive('sendCode')
            ->once()
            ->withArgs(function ($userArg) use ($user) {
                return $userArg->email === $user->email;
            })
            ->andReturn(false);

        Livewire::test(OneTimePasswordLogin::class)
            ->set('email', $email)
            ->call('submitEmail')
            ->assertSet('displayingEmailForm', true);
    }

    public function test_authenticate_user_without_email_verification()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $component = Livewire::test(OneTimePasswordLogin::class);
        $component->instance()->authenticate($user);

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_authenticate_user_with_email_verification()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $component = Livewire::test(OneTimePasswordLogin::class);
        $component->instance()->authenticate($user);

        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_submit_email_with_recaptcha_enabled()
    {
        config(['app.recaptcha_enabled' => true]);

        $email = 'existing'.rand(1, 10000).'@example.com';
        $recaptcha = 'test_recaptcha_token';
        $user = User::factory()->create(['email' => $email]);

        $expectedFields = [
            'email' => $email,
            'g-recaptcha-response' => $recaptcha,
        ];

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $this->mockLoginValidator
            ->shouldReceive('validate')
            ->once()
            ->with($expectedFields)
            ->andReturn($validator);

        $this->mockOtpService
            ->shouldReceive('sendCode')
            ->once()
            ->withArgs(function ($userArg) use ($user) {
                return $userArg->email === $user->email;
            })
            ->andReturn(true);

        Livewire::test(OneTimePasswordLogin::class)
            ->set('email', $email)
            ->set('recaptcha', $recaptcha)
            ->call('submitEmail')
            ->assertSet('displayingEmailForm', false)
            ->assertHasNoErrors();
    }

    public function test_submit_email_with_recaptcha_disabled()
    {
        config(['app.recaptcha_enabled' => false]);

        $email = 'existing'.rand(1, 10000).'@example.com';
        $recaptcha = 'test_recaptcha_token';
        $user = User::factory()->create(['email' => $email]);

        // When recaptcha is disabled, only email should be validated
        $expectedFields = [
            'email' => $email,
        ];

        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('fails')->andReturn(false);

        $this->mockLoginValidator
            ->shouldReceive('validate')
            ->once()
            ->with($expectedFields)
            ->andReturn($validator);

        $this->mockOtpService
            ->shouldReceive('sendCode')
            ->once()
            ->withArgs(function ($userArg) use ($user) {
                return $userArg->email === $user->email;
            })
            ->andReturn(true);

        Livewire::test(OneTimePasswordLogin::class)
            ->set('email', $email)
            ->set('recaptcha', $recaptcha)
            ->call('submitEmail')
            ->assertSet('displayingEmailForm', false)
            ->assertHasNoErrors();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
