<?php

namespace App\Filament\Admin\Resources\RoadmapItems;

use App\Constants\RoadmapItemStatus;
use App\Constants\RoadmapItemType;
use App\Filament\Admin\Resources\RoadmapItems\Pages\CreateRoadmapItem;
use App\Filament\Admin\Resources\RoadmapItems\Pages\EditRoadmapItem;
use App\Filament\Admin\Resources\RoadmapItems\Pages\ListRoadmapItems;
use App\Filament\Admin\Resources\RoadmapItems\RelationManagers\UpvotesRelationManager;
use App\Mapper\RoadmapMapper;
use App\Models\RoadmapItem;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RoadmapItemResource extends Resource
{
    protected static ?string $model = RoadmapItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string
    {
        return __('Roadmap');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Roadmap Items');
    }

    public static function getModelLabel(): string
    {
        return __('Roadmap Item');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextInput::make('title')
                        ->label(__('Title'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label(__('Slug'))
                        ->dehydrateStateUsing(function ($state, Get $get) {
                            if (empty($state)) {
                                // add a random string if there is a roadmap item with the same slug
                                $state = Str::slug($get('title'));
                                if (RoadmapItem::where('slug', $state)->exists()) {
                                    $state .= '-'.Str::random(5);
                                }

                                return Str::slug($state);
                            }

                            return $state;
                        })
                        ->maxLength(255),

                    Textarea::make('description')
                        ->rows(5)
                        ->label(__('Description'))
                        ->columnSpanFull(),
                    Select::make('status')
                        ->label(__('Status'))
                        ->options(function () {
                            return collect(RoadmapItemStatus::cases())->mapWithKeys(function ($status) {
                                return [$status->value => RoadmapMapper::mapStatusForDisplay($status)];
                            });
                        })
                        ->required()
                        ->default(RoadmapItemStatus::APPROVED->value),
                    Select::make('type')
                        ->label(__('Type'))
                        ->options(function () {
                            return collect(RoadmapItemType::cases())->mapWithKeys(function ($type) {
                                return [$type->value => RoadmapMapper::mapTypeForDisplay($type)];
                            });
                        })
                        ->required()
                        ->default(RoadmapItemType::FEATURE->value),
                    TextInput::make('upvotes')
                        ->label(__('Upvotes'))
                        ->required()
                        ->numeric()
                        ->default(1),
                    Select::make('user_id')
                        ->label(__('User'))
                        ->lazy()
                        ->searchable()
                        ->options(fn () => User::pluck('name', 'id'))
                        ->default(fn () => auth()->user()->id)
                        ->required(),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable(),
                SelectColumn::make('status')
                    ->label(__('Status'))
                    ->options(function () {
                        return collect(RoadmapItemStatus::cases())->mapWithKeys(function ($status) {
                            return [$status->value => RoadmapMapper::mapStatusForDisplay($status)];
                        });
                    })
                    ->rules(['required'])
                    ->searchable(),
                SelectColumn::make('type')
                    ->label(__('Type'))
                    ->options(function () {
                        return collect(RoadmapItemType::cases())->mapWithKeys(function ($type) {
                            return [$type->value => RoadmapMapper::mapTypeForDisplay($type)];
                        });
                    })
                    ->rules(['required'])
                    ->searchable(),
                TextColumn::make('upvotes')
                    ->label(__('Upvotes'))
                    ->default(1)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('User'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('upvotes', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            UpvotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoadmapItems::route('/'),
            'create' => CreateRoadmapItem::route('/create'),
            'edit' => EditRoadmapItem::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationLabel(): string
    {
        return __('Roadmap Items');
    }
}
