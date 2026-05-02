<?php

namespace App\Filament\Admin\Resources\Transactions\Pages;

use App\Filament\Admin\Resources\Transactions\TransactionResource;
use App\Filament\CrudDefaults;
use Filament\Resources\Pages\EditRecord;

class EditTransaction extends EditRecord
{
    use CrudDefaults;

    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
