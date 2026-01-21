<?php

namespace App\Filament\Dashboard\Resources\ReferralRewards\Pages;

use App\Filament\Dashboard\Resources\ReferralRewards\ReferralRewardResource;
use Filament\Resources\Pages\ListRecords;

class ListReferralRewards extends ListRecords
{
    protected static string $resource = ReferralRewardResource::class;

    public function getTitle(): string
    {
        return __('My Rewards');
    }
}
