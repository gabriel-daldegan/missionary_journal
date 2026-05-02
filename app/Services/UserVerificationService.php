<?php

namespace App\Services;

use App\Dto\SmsVerificationDto;
use App\Events\User\UserPhoneVerified;
use App\Models\User;
use App\Services\VerificationProviders\VerificationProviderInterface;
use Exception;

class UserVerificationService
{
    private array $verificationProviders = [];

    public function __construct(
        private SessionService $sessionService
    ) {}

    public function setVerificationProviders(VerificationProviderInterface ...$verificationProviders): void
    {
        $this->verificationProviders = $verificationProviders;
    }

    public function getProviderBySlug(string $providerSlug): VerificationProviderInterface
    {
        foreach ($this->verificationProviders as $provider) {
            if ($provider->getSlug() === $providerSlug) {
                return $provider;
            }
        }

        throw new Exception('No verification provider found with slug '.$providerSlug);
    }

    public function phoneAlreadyExists(User $currentUser, string $phoneNumber): bool
    {
        if (User::where('phone_number', $phoneNumber)->where('id', '!=', $currentUser->id)->exists()) {
            return true;
        }

        return false;
    }

    public function generateAndSendSmsVerificationCode(string $phoneNumber): bool
    {
        $code = mt_rand(100000, 999999);
        $verificationDto = new SmsVerificationDto;
        $verificationDto->phoneNumber = $phoneNumber;
        $verificationDto->code = $code;
        $verificationDto->generatedAt = now();

        $this->sessionService->saveSmsVerificationDto($verificationDto);

        return $this->sendSmsVerificationCode($verificationDto->phoneNumber, $code);
    }

    public function verifyCode(User $user, string $code)
    {
        $dto = $this->sessionService->getSmsVerificationDto();
        if (! $dto) {
            return false;
        }

        if ($dto->code !== $code) {
            return false;
        }

        if ($dto->generatedAt->addMinutes(5)->isPast()) {
            return false;
        }

        $user->phone_number = $dto->phoneNumber;
        $user->phone_number_verified_at = now();
        $user->save();

        $this->sessionService->clearSmsVerificationDto();

        UserPhoneVerified::dispatch($user);

        return true;
    }

    private function sendSmsVerificationCode(string $phoneNumber, string $code): bool
    {
        $defaultProvider = config('app.verification.default_provider');

        /** @var VerificationProviderInterface $provider */
        foreach ($this->verificationProviders as $provider) {
            if ($provider->getSlug() === $defaultProvider) {
                $sms = __('Your verification code is: :code', ['code' => $code]);

                return $provider->sendSms($phoneNumber, $sms);
            }
        }

        logger()->error('No verification provider found');

        return false;
    }
}
