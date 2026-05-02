<?php

namespace App\Filament\Admin\Resources\RoadmapItems\RelationManagers;

use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UpvotesRelationManager extends RelationManager
{
    protected static string $relationship = 'userUpvotes';

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
                TextColumn::make('user_id')
                    ->label(__('User'))
                    ->formatStateUsing(function ($state) {
                        return User::find($state)->name;
                    }),
                TextColumn::make('ip_address')
                    ->label(__('IP Address')),
                TextColumn::make('created_at')
                    ->dateTime(config('app.datetime_format'))
                    ->label(__('Created At')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
            ])
            ->recordActions([
            ])
            ->toolbarActions([

            ]);
    }
}
