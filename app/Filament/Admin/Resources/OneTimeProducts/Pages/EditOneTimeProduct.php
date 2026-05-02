<?php

namespace App\Filament\Admin\Resources\OneTimeProducts\Pages;

use App\Filament\Admin\Resources\OneTimeProducts\OneTimeProductResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOneTimeProduct extends EditRecord
{
    protected static string $resource = OneTimeProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
