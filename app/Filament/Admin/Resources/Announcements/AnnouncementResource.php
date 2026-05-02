<?php

namespace App\Filament\Admin\Resources\Announcements;

use App\Filament\Admin\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Admin\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Admin\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Announcements');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Announcements');
    }

    public static function getModelLabel(): string
    {
        return __('Announcement');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('Title'))
                    ->helperText(__('The title of the announcement (for internal use only).'))
                    ->required()
                    ->maxLength(255),
                RichEditor::make('content')
                    ->label(__('Content'))
                    ->helperText(__('The content of the announcement.'))
                    ->required()
                    ->toolbarButtons([
                        'blockquote',
                        'bold',
                        'italic',
                        'link',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                    ])
                    ->columnSpanFull(),
                DateTimePicker::make('starts_at')
                    ->label(__('Starts At'))
                    ->helperText(__('The date and time the announcement will start displaying.'))
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label(__('Ends At'))
                    ->helperText(__('The date and time the announcement will stop displaying.'))
                    ->required(),
                Toggle::make('is_active')
                    ->label(__('Is Active'))
                    ->default(true)
                    ->required(),
                Toggle::make('is_dismissible')
                    ->label(__('Is Dismissible'))
                    ->helperText(__('If enabled, users will be able to dismiss the announcement.'))
                    ->default(true)
                    ->required(),
                Toggle::make('show_on_frontend')
                    ->label(__('Show on frontend'))
                    ->helperText(__('If enabled, the announcement will be displayed on the frontend website.'))
                    ->default(true)
                    ->required(),
                Toggle::make('show_on_user_dashboard')
                    ->label(__('Show on user dashboard'))
                    ->helperText(__('If enabled, the announcement will be displayed on the user dashboard.'))
                    ->default(true)
                    ->required(),
                Toggle::make('show_for_customers')
                    ->label(__('Show for customers'))
                    ->helperText(__('If enabled, the announcement will be displayed for customers (users who either bought a product or subscribed to a plan).'))
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label(__('Starts At'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('Ends At'))
                    ->dateTime()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label(__('Active')),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime(config('app.datetime_format'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime(config('app.datetime_format'))
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
            ])
            ->defaultSort('starts_at', 'desc');
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
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Announcements');
    }
}
