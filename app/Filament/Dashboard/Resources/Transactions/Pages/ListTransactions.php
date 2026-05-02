<?php

namespace App\Filament\Dashboard\Resources\Transactions\Pages;

use App\Filament\Dashboard\Resources\Transactions\TransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
