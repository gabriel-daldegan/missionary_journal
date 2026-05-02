<?php

namespace App\Filament\Admin\Resources\Tenants\RelationManagers;

use App\Constants\PlanType;
use App\Constants\SubscriptionStatus;
use App\Filament\Admin\Resources\Subscriptions\Pages\ViewSubscription;
use App\Mapper\SubscriptionStatusMapper;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
            ->recordTitleAttribute('plan.name')
            ->columns([
                TextColumn::make('plan.name')
                    ->label(__('Plan')),
                TextColumn::make('price')
                    ->label(__('Price'))
                    ->formatStateUsing(function (string $state, $record) {
                        if ($record->plan->type === PlanType::FLAT_RATE->value) {
                            return money($state, $record->currency->code).' / '.$record->interval->name;
                        } elseif ($record->plan->type === PlanType::SEAT_BASED->value) {
                            return money($state, $record->currency->code).' / '.$record->interval->name.' / '.__('seat');
                        }

                        return money($state, $record->currency->code);
                    }),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
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
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
