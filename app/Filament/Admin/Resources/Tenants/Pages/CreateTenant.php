<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Events\Tenant\TenantCreated;
use App\Filament\Admin\Resources\Tenants\TenantResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = Str::uuid();

        return $data;
    }

    protected function afterCreate()
    {
        TenantCreated::dispatch($this->record, auth()->user());
    }
}
