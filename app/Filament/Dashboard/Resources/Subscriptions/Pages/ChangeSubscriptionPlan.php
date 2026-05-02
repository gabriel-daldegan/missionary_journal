<?php

namespace App\Filament\Dashboard\Resources\Subscriptions\Pages;

use App\Filament\Dashboard\Resources\Subscriptions\SubscriptionResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Route;

class ChangeSubscriptionPlan extends Page
{
    protected static string $resource = SubscriptionResource::class;

    protected string $view = 'filament.dashboard.resources.subscription-resource.pages.change-subscription-plan';

    protected function getViewData(): array
    {
        $route = Route::current();
        $subscriptionUuid = $route->parameters['record'];

        return array_merge(parent::getViewData(), [
            'subscriptionUuid' => $subscriptionUuid,
        ]);
    }
}
