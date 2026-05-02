<?php

namespace App\Filament\Dashboard\Pages\TwoFactorAuth;

use App\Constants\SessionConstants;
use Filament\Pages\Page;

class EnableTwoFactorAuth extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.dashboard.pages.two-factor-auth.enable-two-factor-auth';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return config('app.two_factor_auth_enabled') && auth()->user() && ! auth()->user()->hasTwoFactorEnabled();
    }

    protected function getViewData(): array
    {
        if (auth()->user()->twoFactorAuth()->exists()) {
            $secret = auth()->user()->twoFactorAuth;
        } else {
            $secret = auth()->user()->createTwoFactorAuth();
        }

        return [
            'qrCode' => $secret->toQr(),     // As QR Code
            'uri' => $secret->toUri(),    // As "otpauth://" URI.
            'stringCode' => $secret->toString(), // As a string
        ];
    }

    public function confirmEnableTwoFactorAuth()
    {
        session()->put(SessionConstants::TWO_FACTOR_AUTH_ACTION, 'enable');

        $this->redirect(ConfirmTwoFactorAuth::getUrl());
    }
}
