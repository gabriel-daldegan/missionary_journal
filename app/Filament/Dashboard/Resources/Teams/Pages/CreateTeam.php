<?php

namespace App\Filament\Dashboard\Resources\Teams\Pages;

use App\Filament\Dashboard\Resources\Teams\TeamResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = Str::uuid();
        $data['tenant_id'] = Filament::getTenant()->id;

        return $data;
    }
}
