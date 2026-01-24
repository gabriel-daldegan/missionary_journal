<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\ReferralService;
use Filament\Widgets\Widget;

class ReferralLinkWidget extends Widget
{
    protected string $view = 'filament.dashboard.widgets.referral-link-widget';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    public function getReferralLink(): string
    {
        $referralService = app(ReferralService::class);

        return $referralService->getReferralLink(auth()->user());
    }

    public static function isDiscovered(): bool
    {
        $referralService = app(ReferralService::class);

        return $referralService->isEnabled();
    }
}
