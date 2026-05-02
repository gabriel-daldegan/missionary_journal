<?php

namespace App\Livewire\Verify;

use App\Services\SessionService;
use App\Services\UserVerificationService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class SmsVerification extends Component
{
    public $phone;

    public $code;

    public function render(SessionService $sessionService)
    {
        $dto = $sessionService->getSmsVerificationDto();

        $this->phone ??= $dto?->phoneNumber;

        return view(
            'livewire.verify.sms-verification', [
            ]
        );
    }

    public function sendVerificationCode(UserVerificationService $userVerificationService)
    {
        // remove spaces from phone number
        $this->phone = preg_replace('/\s+/', '', $this->phone);

        $this->validate([
            'phone' => 'phone:INTERNATIONAL',
        ], [
            'phone' => __('Invalid phone number. Make sure to include the country code with +.'),
        ]);

        $user = auth()->user();

        $executed = RateLimiter::attempt(
            'send-verification-code:'.$user->id,
            10,
            function () use ($userVerificationService, $user) {

                if ($userVerificationService->phoneAlreadyExists($user, $this->phone)) {
                    $this->addError('phone', __('Phone number already exists.'));

                    return;
                }
                $result = $userVerificationService->generateAndSendSmsVerificationCode($this->phone);

                if (! $result) {
                    $this->addError('phone', __('Failed to send verification code.'));
                }
            }
        );

        if (! $executed) {
            $this->addError('phone', __('Too many attempts. Please wait a minute.'));
        }
    }

    public function verifyCode(UserVerificationService $userVerificationService)
    {
        $this->validate([
            'code' => 'required|digits:6',
        ]);

        $user = auth()->user();

        $result = false;

        $executed = RateLimiter::attempt(
            'verify-phone:'.$user->id,
            10,
            function () use ($userVerificationService, $user, &$result) {
                $result = $userVerificationService->verifyCode($user, $this->code);
            }
        );

        if (! $executed) {
            $this->addError('code', __('Too many attempts. Please wait a minute.'));

            return;
        }

        if (! $result) {
            $this->addError('code', __('Invalid verification code.'));

            return;
        }

        $this->redirect(route('user.phone-verified'));
    }

    public function editPhone(SessionService $sessionService)
    {
        $sessionService->clearSmsVerificationDto();

        $this->phone = null;

    }
}
