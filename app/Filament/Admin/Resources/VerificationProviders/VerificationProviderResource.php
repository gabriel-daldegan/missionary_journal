<?php

namespace App\Filament\Admin\Resources\VerificationProviders;

use App\Filament\Admin\Resources\VerificationProviders\Pages\CreateVerificationProvider;
use App\Filament\Admin\Resources\VerificationProviders\Pages\EditVerificationProvider;
use App\Filament\Admin\Resources\VerificationProviders\Pages\ListVerificationProviders;
use App\Filament\Admin\Resources\VerificationProviders\Pages\TwilioSettings;
use App\Models\VerificationProvider;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class VerificationProviderResource extends Resource
{
    protected static ?string $model = VerificationProvider::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextInput::make('name')
                        ->label(__('Name'))
                        ->required()
                        ->maxLength(255),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('icon')
                    ->label(__('Icon'))
                    ->getStateUsing(function (VerificationProvider $record) {
                        return new HtmlString(
                            '<div class="flex gap-2">'.
                            ' <img src="'.asset('images/verification-providers/'.$record->slug.'.png').'" alt="'.$record->name.'" class="h-6"> '
                            .'</div>'
                        );
                    }),
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable(),
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
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
            'index' => ListVerificationProviders::route('/'),
            'create' => CreateVerificationProvider::route('/create'),
            'edit' => EditVerificationProvider::route('/{record}/edit'),
            'twilio-settings' => TwilioSettings::route('/twilio-settings'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('Verification Providers');
    }
}
