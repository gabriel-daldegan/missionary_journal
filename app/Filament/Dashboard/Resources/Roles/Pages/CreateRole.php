<?php

namespace App\Filament\Dashboard\Resources\Roles\Pages;

use App\Filament\CrudDefaults;
use App\Filament\Dashboard\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    use CrudDefaults;

    protected static string $resource = RoleResource::class;

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = Filament::getTenant()->id;
        $data['is_tenant_role'] = true;

        return $data;
    }
}
