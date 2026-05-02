<?php

namespace App\Filament\Dashboard\Resources\Subscriptions\Pages;

use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Route;

class AddDiscount extends Page
{
    protected static string $resource = SubscriptionResource::class;

    protected string $view = 'filament.dashboard.resources.subscription-resource.pages.add-discount';

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'subscriptionUuid' => Route::current()->parameters['record'],
        ]);
    }
}
