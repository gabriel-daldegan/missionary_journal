<?php

namespace App\Livewire\Auth\Login;

use App\Services\OneTimePasswordService;
use App\Validator\LoginValidator;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Spatie\OneTimePasswords\Livewire\OneTimePasswordComponent;

class OneTimePasswordLogin extends OneTimePasswordComponent
{
    private OneTimePasswordService $oneTimePasswordService;

    private LoginValidator $loginValidator;

    public $recaptcha;

    public function mount(?string $redirectTo = null, ?string $email = ''): void
    {
        parent::mount($redirectTo, request()->query('email', $email));
    }

    public function render(): View
    {
        return view("livewire.auth.login.{$this->showViewName()}");
    }

    public function boot(
        OneTimePasswordService $oneTimePasswordService,
        LoginValidator $loginValidator
    ) {
        $this->oneTimePasswordService = $oneTimePasswordService;
        $this->loginValidator = $loginValidator;
    }

    public function submitEmail(): void
    {
        $fields = [
            'email' => $this->email,
        ];

        if (config('app.recaptcha_enabled')) {
            $fields[recaptchaFieldName()] = $this->recaptcha;
        }

        $validator = $this->loginValidator->validate($fields);

        if ($validator->fails()) {
            $this->resetReCaptcha();
            throw new ValidationException($validator);
        }

        $user = $this->findUser();

        if (! $user) {
            $this->addError('email', 'We could not find a user with that email address.');

            return;
        }

        if (! $this->oneTimePasswordService->sendCode($user)) {
            return;
        }

        $this->displayingEmailForm = false;
    }

    public function authenticate(Authenticatable $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        auth()->login($user);
    }

    protected function resetReCaptcha()
    {
        $this->dispatch('reset-recaptcha');
    }
}
