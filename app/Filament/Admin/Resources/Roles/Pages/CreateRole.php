<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\CrudDefaults;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    use CrudDefaults;

    protected static string $resource = RoleResource::class;
}
