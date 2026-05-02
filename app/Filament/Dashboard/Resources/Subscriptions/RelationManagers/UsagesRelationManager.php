<?php

namespace App\Filament\Dashboard\Resources\Subscriptions\RelationManagers;

use App\Constants\PlanType;
use App\Models\Subscription;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('unit_count')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Unit Usages'))
            ->recordTitleAttribute('unit_count')
            ->columns([
                TextColumn::make('unit_count')->label(function () {
                    return __('Unit Count').' ('.str()->plural(__($this->ownerRecord->plan->meter->name)).')';
                }),
                TextColumn::make('created_at')->label(__('Created At'))
                    ->dateTime(config('app.datetime_format'))
                    ->searchable()->sortable(),
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

    public static function canViewForRecord(Model|Subscription $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->plan->type === PlanType::USAGE_BASED->value;
    }
}
