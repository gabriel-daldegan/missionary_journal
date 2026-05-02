<?php

namespace App\Filament\Admin\Resources\PaymentProviders\Pages;

use App\Filament\Admin\Resources\PaymentProviders\PaymentProviderResource;
use App\Models\PaymentProvider;
use App\Services\ConfigService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditPaymentProvider extends EditRecord
{
    protected static string $resource = PaymentProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit-credentials')
                ->label(__('Edit Credentials'))
                ->color('primary')
                ->visible(fn (PaymentProvider $record, ConfigService $configService) => $configService->isAdminSettingsEnabled() && PaymentProviderResource::hasPage($record->slug.'-settings'))
                ->icon('heroicon-o-rocket-launch')
                ->url(fn (PaymentProvider $record): string => PaymentProviderResource::getUrl(
                    $record->slug.'-settings'
                )),
        ];
    }
}
