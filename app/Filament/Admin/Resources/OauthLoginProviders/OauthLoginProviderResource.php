<?php

namespace App\Filament\Admin\Resources\OauthLoginProviders;

use App\Filament\Admin\Resources\OauthLoginProviders\Pages\BitbucketSettings;
use App\Filament\Admin\Resources\OauthLoginProviders\Pages\EditOauthLoginProvider;
use App\Filament\Admin\Resources\OauthLoginProviders\Pages\FacebookSettings;
use App\Filament\Admin\Resources\OauthLoginProviders\Pages\GithubSettings;
use App\Filament\Admin\Resources\OauthLoginProviders\Pages\GitlabSettings;
use App\Filament\Admin\Resources\OauthLoginProviders\Pages\GoogleSettings;
use App\Filament\Admin\Resources\OauthLoginProviders\Pages\LinkedinSettings;
use App\Filament\Admin\Resources\OauthLoginProviders\Pages\ListOauthLoginProviders;
use App\Filament\Admin\Resources\OauthLoginProviders\Pages\TwitterSettings;
use App\Models\OauthLoginProvider;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class OauthLoginProviderResource extends Resource
{
    protected static ?string $model = OauthLoginProvider::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Oauth Login Providers');
    }

    public static function getModelLabel(): string
    {
        return __('Oauth Login Provider');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->disabled()
                            ->required()
                            ->label(__('Name'))
                            ->maxLength(255),
                        TextInput::make('provider_name')
                            ->required()
                            ->disabled()
                            ->label(__('Provider Name'))
                            ->maxLength(255),
                        Toggle::make('enabled')
                            ->label(__('Enabled'))
                            ->required(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->getStateUsing(function (OauthLoginProvider $record) {
                        return new HtmlString(
                            '<div class="flex gap-2">'.
                            ' <img src="'.asset('images/oauth-providers/'.$record->provider_name.'.svg').'"  class="h-6"> '
                            .$record->name
                            .'</div>'
                        );
                    }),
                TextColumn::make('provider_name')->label(__('Provider Name')),
                ToggleColumn::make('enabled')->label(__('Enabled')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
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
            'index' => ListOauthLoginProviders::route('/'),
            'edit' => EditOauthLoginProvider::route('/{record}/edit'),
            'google-settings' => GoogleSettings::route('/google-settings'),
            'github-settings' => GithubSettings::route('/github-settings'),
            'gitlab-settings' => GitlabSettings::route('/gitlab-settings'),
            'twitter-oauth-2-settings' => TwitterSettings::route('/twitter-settings'),
            'linkedin-openid-settings' => LinkedinSettings::route('/linkedin-settings'),
            'facebook-settings' => FacebookSettings::route('/facebook-settings'),
            'bitbucket-settings' => BitbucketSettings::route('/bitbucket-settings'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('Oauth Login Providers');
    }
}
