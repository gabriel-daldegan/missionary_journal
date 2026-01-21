<?php

namespace App\Filament\Dashboard\Resources\Referrals\Pages;

use App\Filament\Dashboard\Resources\Referrals\ReferralResource;
use App\Filament\Dashboard\Widgets\ReferralLinkWidget;
use App\Filament\Dashboard\Widgets\ReferralStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListReferrals extends ListRecords
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ReferralLinkWidget::class,
            ReferralStatsWidget::class,
        ];
    }

    public function getTitle(): string
    {
        return __('My Referrals');
    }
}
