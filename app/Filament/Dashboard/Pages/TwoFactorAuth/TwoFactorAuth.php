<?php

namespace App\Filament\Dashboard\Pages\TwoFactorAuth;

use App\Constants\SessionConstants;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class TwoFactorAuth extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.dashboard.pages.two-factor-auth.index';

    protected static ?string $slug = 'two-factor-auth';

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string|Htmlable
    {
        return __('Two Factor Authentication');
    }

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        return [
            'twoFactorAuthEnabled' => $user->hasTwoFactorEnabled(),
        ];
    }

    public static function canAccess(): bool
    {
        return config('app.two_factor_auth_enabled');
    }

    public function enableTwoFactorAuth()
    {
        $this->redirect(EnableTwoFactorAuth::getUrl());
    }

    public function disableTwoFactorAuth()
    {
        // store the action in the session
        session()->put(SessionConstants::TWO_FACTOR_AUTH_ACTION, 'disable');

        $this->redirect(ConfirmTwoFactorAuth::getUrl());
    }
}
