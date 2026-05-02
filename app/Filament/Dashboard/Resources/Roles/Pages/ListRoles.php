<?php

namespace App\Filament\Dashboard\Resources\Roles\Pages;

use App\Filament\Dashboard\Resources\Roles\RoleResource;
use App\Filament\ListDefaults;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{
    use ListDefaults;

    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
