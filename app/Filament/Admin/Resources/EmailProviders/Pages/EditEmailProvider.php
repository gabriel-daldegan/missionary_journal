<?php

namespace App\Filament\Admin\Resources\EmailProviders\Pages;

use App\Filament\Admin\Resources\EmailProviders\EmailProviderResource;
use App\Mail\TestEmail;
use App\Models\EmailProvider;
use App\Services\ConfigService;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EditEmailProvider extends EditRecord
{
    protected static string $resource = EmailProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit-credentials')
                ->label(__('Edit Credentials'))
                ->visible(fn (ConfigService $configService) => $configService->isAdminSettingsEnabled())
                ->color('primary')
                ->icon('heroicon-o-rocket-launch')
                ->url(fn (EmailProvider $record): string => EmailProviderResource::getUrl(
                    $record->slug.'-settings'
                )),
            Action::make('send-test-email')
                ->color('gray')
                ->schema([
                    TextInput::make('email')->default(config('app.support_email'))->required(),
                    TextInput::make('subject')->default('Test Email')->required(),
                    RichEditor::make('body')->default('This is a test email.')->required(),
                ])
                ->action(function (array $data, EmailProvider $record) {

                    try {
                        Mail::mailer($record->slug)
                            ->to($data['email'])
                            ->send(new TestEmail(
                                $data['subject'],
                                $data['body'],
                            ));
                    } catch (Exception $e) {
                        Log::error($e->getMessage());
                        Notification::make()
                            ->title(__('Test Email Failed To Send with message:'))
                            ->body($e->getMessage())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('Test Email Queued For Sending, check /horizon to see if it was sent.'))
                        ->success()
                        ->send();
                })->modalSubmitActionLabel(__('Send Test Email')),
        ];
    }
}
