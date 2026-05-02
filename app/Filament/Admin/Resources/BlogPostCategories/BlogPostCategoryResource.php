<?php

namespace App\Filament\Admin\Resources\BlogPostCategories;

use App\Filament\Admin\Resources\BlogPostCategories\Pages\CreateBlogPostCategory;
use App\Filament\Admin\Resources\BlogPostCategories\Pages\EditBlogPostCategory;
use App\Filament\Admin\Resources\BlogPostCategories\Pages\ListBlogPostCategories;
use App\Models\BlogPostCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogPostCategoryResource extends Resource
{
    protected static ?string $model = BlogPostCategory::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Blog');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Blog Post Categories');
    }

    public static function getModelLabel(): string
    {
        return __('Blog Post Category');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make([
                    TextInput::make('name')
                        ->required()
                        ->label(__('Name'))
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label(__('Slug'))
                        ->dehydrateStateUsing(function ($state, Get $get) {
                            if (empty($state)) {
                                $name = $get('name');

                                return Str::slug($name);
                            }

                            return Str::slug($state);
                        })
                        ->helperText(__('Leave empty to generate slug automatically name.'))
                        ->maxLength(255),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogPostCategories::route('/'),
            'create' => CreateBlogPostCategory::route('/create'),
            'edit' => EditBlogPostCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Blog Post Categories');
    }
}
