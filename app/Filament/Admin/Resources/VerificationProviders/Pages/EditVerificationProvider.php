<?php

namespace App\Filament\Admin\Resources\VerificationProviders\Pages;

use App\Filament\Admin\Resources\VerificationProviders\VerificationProviderResource;
use App\Models\VerificationProvider;
use App\Services\ConfigService;
use App\Services\UserVerificationService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVerificationProvider extends EditRecord
{
    protected static string $resource = VerificationProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            Action::make('edit-credentials')
                ->label(__('Edit Credentials'))
                ->color('primary')
                ->visible(fn (ConfigService $configService) => $configService->isAdminSettingsEnabled())
                ->icon('heroicon-o-rocket-launch')
                ->url(fn (VerificationProvider $record): string => VerificationProviderResource::getUrl(
                    $record->slug.'-settings'
                )),
            Action::make('send-test-sms')
                ->label(__('Send Test SMS'))
                ->color('gray')
                ->schema([
                    TextInput::make('phone')->required(),
                    Textarea::make('body')->default('This is a test sms.')->required(),
                ])
                ->action(function (array $data, VerificationProvider $record, UserVerificationService $userVerificationService) {
                    try {
                        $userVerificationService->getProviderBySlug($record->slug)
                            ->sendSms($data['phone'], $data['body']);
                    } catch (Exception $e) {
                        logger()->error($e->getMessage());
                        Notification::make()
                            ->title(__('Test SMS Failed To Send with message:'))
                            ->body($e->getMessage())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('Test SMS was sent.'))
                        ->success()
                        ->send();
                })->modalSubmitActionLabel(__('Send Test SMS')),
        ];
    }
}
