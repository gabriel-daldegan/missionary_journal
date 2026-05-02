<?php

namespace App\Filament\Admin\Resources\Plans\Pages;

use App\Filament\Admin\Resources\Plans\PlanResource;
use App\Filament\CrudDefaults;
use Filament\Resources\Pages\CreateRecord;

class CreatePlan extends CreateRecord
{
    use CrudDefaults;

    protected static string $resource = PlanResource::class;
}
