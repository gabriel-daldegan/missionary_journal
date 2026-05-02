<?php

namespace Tests\Feature\Livewire\Verify;

use App\Livewire\Verify\SmsVerification;
use App\Models\VerificationProvider;
use App\Services\SessionService;
use App\Services\UserVerificationService;
use App\Services\VerificationProviders\VerificationProviderInterface;
use Livewire\Livewire;
use Mockery;
use Tests\Feature\FeatureTest;

class SmsVerificationTest extends FeatureTest
{
    public function test_enter_phone_number_and_receive_sms_and_verify_successfully()
    {
        $mock = $this->addVerificationProvider();

        $mock->shouldReceive('sendSms')
            ->once()
            ->andReturn(true);

        $mock->shouldReceive('getSlug')
            ->once()
            ->andReturn('verify-more');

        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        $user = $this->createUser(attributes: [
            'email' => $email,
        ]);

        $this->actingAs($user);

        Livewire::test(SmsVerification::class)
            ->set('phone', '+18482560284')
            ->call('sendVerificationCode');

        $sessionService = app(SessionService::class);

        $dto = $sessionService->getSmsVerificationDto();

        $this->assertNotNull($dto);

        $this->assertEquals($dto->phoneNumber, '+18482560284');

        Livewire::test(SmsVerification::class)
            ->set('phone', '+18482560284')
            ->set('code', $dto->code)
            ->call('verifyCode');

        $user = $user->fresh();
        $this->assertNotNull($user->phone_number_verified_at);
        $this->assertEquals($user->phone_number, '+18482560284');

        $this->assertNull($sessionService->getSmsVerificationDto());
    }

    public function test_invalid_phone_number()
    {
        $this->addVerificationProvider();

        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        $user = $this->createUser(attributes: [
            'email' => $email,
        ]);

        $this->actingAs($user);

        Livewire::test(SmsVerification::class)
            ->set('phone', 'invalid')
            ->call('sendVerificationCode')
            ->assertHasErrors('phone');
    }

    public function test_wrong_code()
    {
        $mock = $this->addVerificationProvider();

        $mock->shouldReceive('sendSms')
            ->once()
            ->andReturn(true);

        $mock->shouldReceive('getSlug')
            ->once()
            ->andReturn('verify-more');

        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        $user = $this->createUser(attributes: [
            'email' => $email,
        ]);

        $this->actingAs($user);

        Livewire::test(SmsVerification::class)
            ->set('phone', '+18482560283')
            ->call('sendVerificationCode');

        $sessionService = app(SessionService::class);

        $dto = $sessionService->getSmsVerificationDto();

        $this->assertNotNull($dto);

        $this->assertEquals($dto->phoneNumber, '+18482560283');

        Livewire::test(SmsVerification::class)
            ->set('phone', '+18482560283')
            ->set('code', '123456')
            ->call('verifyCode');

        $user = $user->fresh();
        $this->assertNull($user->phone_number_verified_at);
    }

    public function test_same_number_twice()
    {
        $this->addVerificationProvider();

        $email = 'something+'.rand(1, 1000000).'@gmail.com';

        $user = $this->createUser(attributes: [
            'email' => $email,
        ]);

        $email2 = 'something+'.rand(1, 1000000).'@gmail.com';
        $user2 = $this->createUser(attributes: [
            'email' => $email2,
            'phone_number' => '+18482560282',
        ]);

        $this->actingAs($user);

        Livewire::test(SmsVerification::class)
            ->set('phone', '+18482560282')
            ->call('sendVerificationCode')
            ->assertHasErrors('phone');
    }

    private function addVerificationProvider(): VerificationProviderInterface|Mockery\MockInterface
    {
        VerificationProvider::updateOrCreate([
            'slug' => 'verify-more',
        ], [
            'name' => 'Verify More',
        ]);

        $mock = Mockery::mock(VerificationProviderInterface::class);

        $this->app->instance(VerificationProviderInterface::class, $mock);

        $this->app->afterResolving(UserVerificationService::class, function (UserVerificationService $service) use ($mock) {
            $service->setVerificationProviders($mock);
        });

        config(['app.verification.default_provider' => 'verify-more']);

        return $mock;
    }
}
