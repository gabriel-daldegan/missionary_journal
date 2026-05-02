<?php

namespace App\Filament\Admin\Resources\RoadmapItems\Pages;

use App\Filament\Admin\Resources\RoadmapItems\RoadmapItemResource;
use App\Filament\CrudDefaults;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRoadmapItem extends EditRecord
{
    use CrudDefaults;

    protected static string $resource = RoadmapItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
