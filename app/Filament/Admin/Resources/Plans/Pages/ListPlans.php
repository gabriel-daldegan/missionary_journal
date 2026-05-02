<?php

namespace App\Filament\Admin\Resources\Plans\Pages;

use App\Filament\Admin\Resources\Plans\PlanResource;
use App\Filament\ListDefaults;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlans extends ListRecords
{
    use ListDefaults;

    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
