<?php

namespace App\Filament\Admin\Resources\Subscriptions\Pages;

use App\Filament\Admin\Resources\Subscriptions\SubscriptionResource;
use App\Filament\CrudDefaults;
use Filament\Resources\Pages\CreateRecord;

class CreateSubscription extends CreateRecord
{
    use CrudDefaults;

    protected static string $resource = SubscriptionResource::class;
}
