<?php

namespace App\Filament\Dashboard\Widgets;

use App\Services\ReferralService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReferralStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $referralService = app(ReferralService::class);
        $user = auth()->user();
        $stats = $referralService->getReferralStats($user);

        return [
            Stat::make(__('Total Referrals'), $stats['total_referrals'])
                ->description(__('Total number of users you\'ve referred')),
            Stat::make(__('Successful'), $stats['rewarded_referrals'])
                ->description(__('Referrals that earned rewards'))
                ->color('success'),
            Stat::make(__('Rewards Earned'), $stats['total_rewards'])
                ->description(__('Total rewards you\'ve received'))
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        return app(ReferralService::class)->isEnabled();
    }
}
