<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Constants\OrderStatus;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('update_order')
                ->color('gray')
                ->label(__('Update Order'))
                ->icon('heroicon-m-pencil')
                ->schema([
                    Select::make('status')
                        ->label(__('Order Status'))
                        ->default($this->getRecord()->status)
                        ->options([
                            OrderStatus::SUCCESS->value => __('Success'),
                            OrderStatus::FAILED->value => __('Failed'),
                        ])
                        ->required(),
                    RichEditor::make('comments')
                        ->label(__('Comments'))
                        ->default($this->getRecord()->comments)
                        ->helperText(__('Optional comments about the order.')),
                ])
                ->action(function (Order $order, OrderService $orderService, array $data) {
                    if (! $orderService->canUpdateOrder($order)) {
                        Notification::make()
                            ->title(__('You cannot update this order.'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $orderService->updateOrder(
                        $order,
                        $data,
                    );
                })
                ->visible(fn (Order $record, OrderService $orderService): bool => $orderService->canUpdateOrder($record)),
        ];
    }
}
