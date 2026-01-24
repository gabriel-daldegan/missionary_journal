<?php

namespace App\Filament\Admin\Resources\Referrals\Pages;

use App\Filament\Admin\Resources\Referrals\ReferralResource;
use App\Filament\Admin\Resources\Referrals\Widgets\ReferralStatsOverview;
use App\Filament\ListDefaults;
use Filament\Resources\Pages\ListRecords;

class ListReferrals extends ListRecords
{
    use ListDefaults;

    protected static string $resource = ReferralResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ReferralStatsOverview::class,
        ];
    }
}
