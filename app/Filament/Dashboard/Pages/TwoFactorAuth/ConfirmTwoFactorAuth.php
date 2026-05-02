<?php

namespace App\Filament\Dashboard\Pages\TwoFactorAuth;

use App\Constants\SessionConstants;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class ConfirmTwoFactorAuth extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.dashboard.pages.two-factor-auth.confirm-two-factor-auth';

    protected static bool $shouldRegisterNavigation = false;

    public string $code;

    public function confirmTwoFactorAuth()
    {
        $this->validate([
            'code' => 'required|numeric',
        ]);

        $user = auth()->user();

        if (session()->has(SessionConstants::TWO_FACTOR_AUTH_ACTION)) {
            $action = session()->get(SessionConstants::TWO_FACTOR_AUTH_ACTION);

            if ($action === 'enable') {
                $activated = $user->confirmTwoFactorAuth($this->code);

                if (! $activated) {
                    throw ValidationException::withMessages([
                        'code' => __('Invalid code, please try again.'),
                    ]);
                }

                $user->enableTwoFactorAuth();

                Notification::make()
                    ->title(__('Two-factor authentication enabled'))
                    ->success()
                    ->send();

                $this->redirect(RecoveryCodes::getUrl());

                return;

            } elseif ($action === 'disable') {

                $validated = $user->validateTwoFactorCode($this->code);

                if (! $validated) {
                    throw ValidationException::withMessages([
                        'code' => __('Invalid code, please try again.'),
                    ]);
                }

                $user->disableTwoFactorAuth();

                Notification::make()
                    ->title(__('Two-factor authentication disabled'))
                    ->success()
                    ->send();
            }

            session()->forget(SessionConstants::TWO_FACTOR_AUTH_ACTION);
        }

        $this->redirect(TwoFactorAuth::getUrl());
    }
}
