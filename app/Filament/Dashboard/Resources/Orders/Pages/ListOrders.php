<?php

namespace App\Filament\Dashboard\Resources\Orders\Pages;

use App\Constants\OrderStatus;
use App\Filament\Dashboard\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'success' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::SUCCESS)),
            'pending' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::PENDING)),
            'failed' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::FAILED)),
        ];
    }
}
