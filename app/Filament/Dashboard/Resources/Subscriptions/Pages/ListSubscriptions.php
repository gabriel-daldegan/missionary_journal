<?php

namespace App\Filament\Dashboard\Resources\Subscriptions\Pages;

use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    public function getView(): string
    {
        if ($this->getTableRecords()->count() === 0) {
            return 'filament.dashboard.resources.subscription-resource.pages.subscriptions';
        }

        return parent::getView();
    }
}
