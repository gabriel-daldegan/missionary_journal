<?php

namespace App\Filament\Admin\Resources\Tenants\RelationManagers;

use App\Constants\OrderStatus;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Mapper\OrderStatusMapper;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->label(__('Status'))
                    ->colors([
                        'success' => OrderStatus::SUCCESS->value,
                    ])
                    ->formatStateUsing(
                        function (string $state, $record, OrderStatusMapper $mapper) {
                            return $mapper->mapForDisplay($state);
                        }),
                TextColumn::make('total_amount')
                    ->label(__('Total Amount'))
                    ->formatStateUsing(function (string $state, $record) {
                        return money($state, $record->currency->code);
                    }),
                TextColumn::make('total_amount_after_discount')
                    ->label(__('Total Amount After Discount'))
                    ->formatStateUsing(function (string $state, $record) {
                        return money($state, $record->currency->code);
                    }),
                TextColumn::make('total_discount_amount')
                    ->label(__('Total Discount Amount'))
                    ->formatStateUsing(function (string $state, $record) {
                        return money($state, $record->currency->code);
                    }),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime(config('app.datetime_format'))
                    ->searchable()->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime(config('app.datetime_format'))
                    ->searchable()->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([

            ])
            ->recordActions([
                Action::make('view')
                    ->url(fn ($record) => ViewOrder::getUrl(['record' => $record]))
                    ->label(__('View'))
                    ->icon('heroicon-o-eye'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
