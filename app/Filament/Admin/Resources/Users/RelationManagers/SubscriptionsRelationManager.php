<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Constants\SubscriptionStatus;
use App\Filament\Admin\Resources\Subscriptions\Pages\ViewSubscription;
use App\Mapper\SubscriptionStatusMapper;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user_id')
            ->columns([
                TextColumn::make('plan.name')
                    ->label(__('Plan'))->searchable(),
                TextColumn::make('price')
                    ->label(__('Price'))
                    ->formatStateUsing(function (string $state, $record) {
                        return money($state, $record->currency->code).' / '.$record->interval->name;
                    }),
                TextColumn::make('payment_provider_id')
                    ->formatStateUsing(function (string $state, $record) {
                        return $record->paymentProvider->name;
                    })
                    ->label(__('Payment Provider'))
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('Status'))
                    ->colors([
                        'success' => SubscriptionStatus::ACTIVE->value,
                    ])
                    ->formatStateUsing(
                        function (string $state, $record, SubscriptionStatusMapper $mapper) {
                            return $mapper->mapForDisplay($state);
                        })
                    ->searchable(),
                TextColumn::make('created_at')->label(__('Created At'))
                    ->dateTime(config('app.datetime_format'))
                    ->searchable()->sortable(),
                TextColumn::make('updated_at')->label(__('Updated At'))
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
                    ->url(fn ($record) => ViewSubscription::getUrl(['record' => $record]))
                    ->label(__('View'))
                    ->icon('heroicon-o-eye'),
            ])
            ->toolbarActions([
            ]);
    }
}
