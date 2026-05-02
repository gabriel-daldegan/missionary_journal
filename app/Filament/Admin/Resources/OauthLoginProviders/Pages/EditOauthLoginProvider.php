<?php

namespace App\Filament\Admin\Resources\OauthLoginProviders\Pages;

use App\Filament\Admin\Resources\OauthLoginProviders\OauthLoginProviderResource;
use App\Filament\CrudDefaults;
use App\Models\OauthLoginProvider;
use App\Services\ConfigService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditOauthLoginProvider extends EditRecord
{
    use CrudDefaults;

    protected static string $resource = OauthLoginProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit-credentials')
                ->label(__('Edit Credentials'))
                ->color('primary')
                ->visible(fn (ConfigService $configService) => $configService->isAdminSettingsEnabled())
                ->icon('heroicon-o-rocket-launch')
                ->url(fn (OauthLoginProvider $record): string => OauthLoginProviderResource::getUrl(
                    $record->provider_name.'-settings'
                )),
        ];
    }
}
