<?php

namespace App\Livewire\Auth\Register;

use App\Services\OneTimePasswordService;
use App\Services\UserService;
use App\Validator\RegisterValidator;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OneTimePasswordRegistration extends Component
{
    public string $email;

    public string $name;

    public $recaptcha;

    private RegisterValidator $registerValidator;

    private UserService $userService;

    private OneTimePasswordService $oneTimePasswordService;

    public function boot(
        RegisterValidator $registerValidator,
        UserService $userService,
        OneTimePasswordService $oneTimePasswordService,
    ) {
        $this->registerValidator = $registerValidator;
        $this->userService = $userService;
        $this->oneTimePasswordService = $oneTimePasswordService;
    }

    public function render(): View
    {
        return view('livewire.auth.register.registration-form');
    }

    public function register(): void
    {
        $userFields = [
            'email' => $this->email,
            'name' => $this->name,
        ];

        if (config('app.recaptcha_enabled')) {
            $userFields[recaptchaFieldName()] = $this->recaptcha;
        }

        $validator = $this->registerValidator->validate($userFields);

        if ($validator->fails()) {
            $this->resetReCaptcha();
            throw new ValidationException($validator);
        }

        $user = $this->userService->findByEmail($this->email);

        if ($user) {
            $this->addError('email', __('This email is already registered. Please log in instead.'));

            return;
        }

        $user = $this->userService->createUser($userFields, true);

        if (! $this->oneTimePasswordService->sendCode($user)) {
            $this->addError('email', __('Failed to send one-time password. Please try again later.'));

            return;
        }

        $this->redirect(route('login', ['email' => $this->email]));
    }

    protected function resetReCaptcha()
    {
        $this->dispatch('reset-recaptcha');
    }
}
