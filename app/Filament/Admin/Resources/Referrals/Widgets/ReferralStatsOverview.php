<?php

namespace App\Filament\Admin\Resources\Referrals\Widgets;

use App\Constants\ReferralConstants;
use App\Models\Referral;
use App\Models\ReferralReward;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReferralStatsOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totalReferrals = Referral::count();
        $pendingReferrals = Referral::where('status', ReferralConstants::STATUS_PENDING)->count();
        $rewardedReferrals = Referral::where('status', ReferralConstants::STATUS_REWARDED)->count();
        $totalRewardsGiven = ReferralReward::count();

        return [
            Stat::make(__('Total Referrals'), $totalReferrals)
                ->description(__('All referrals in the system')),
            Stat::make(__('Pending'), $pendingReferrals)
                ->description(__('Awaiting trigger completion'))
                ->color('warning'),
            Stat::make(__('Rewarded'), $rewardedReferrals)
                ->description(__('Successfully completed referrals'))
                ->color('success'),
            Stat::make(__('Rewards Given'), $totalRewardsGiven)
                ->description(__('Total rewards distributed'))
                ->color('success'),
        ];
    }
}
