<?php

namespace App\Filament\Admin\Resources\Transactions;

use App\Constants\TransactionStatus;
use App\Filament\Admin\Resources\Orders\Pages\ViewOrder;
use App\Filament\Admin\Resources\Subscriptions\Pages\ViewSubscription;
use App\Filament\Admin\Resources\Tenants\Pages\EditTenant;
use App\Filament\Admin\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Admin\Resources\Transactions\Pages\ViewTranscription;
use App\Filament\Admin\Resources\Transactions\Widgets\TransactionOverview;
use App\Mapper\TransactionStatusMapper;
use App\Models\Transaction;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static array $cachedTransactionHistoryComponents = [];

    protected static ?string $model = Transaction::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Revenue');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')->label(__('Tenant'))->searchable(),
                TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->formatStateUsing(function (string $state, $record) {
                        return money($state, $record->currency->code);
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->label(__('Status'))
                    ->color(fn (Transaction $record, TransactionStatusMapper $mapper): string => $mapper->mapColor($record->status))
                    ->formatStateUsing(function (string $state, $record, TransactionStatusMapper $mapper) {
                        return $mapper->mapForDisplay($state);
                    })
                    ->searchable(),
                TextColumn::make('payment_provider_id')
                    ->label(__('Payment Provider'))
                    ->getStateUsing(fn (Transaction $record) => $record->paymentProvider->name)
                    ->sortable(),
                TextColumn::make('owner')
                    ->label(__('Owner'))
                    ->getStateUsing(fn (Transaction $record) => $record->subscription_id !== null ? ($record->subscription->plan?->name ?? '-') : ($record->order_id !== null ? __('Order Nr. ').$record->order_id : '-'))
                    ->url(fn (Transaction $record) => $record->subscription_id !== null ? ViewSubscription::getUrl(['record' => $record->subscription]) : ($record->order_id !== null ? ViewOrder::getUrl(['record' => $record->order]) : '-')),
                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime(config('app.datetime_format'))
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('see-invoice')
                        ->label(__('See Invoice'))
                        ->icon('heroicon-o-document')
                        ->visible(fn (Transaction $record, InvoiceService $invoiceService): bool => $invoiceService->canGenerateInvoices($record))
                        ->url(
                            fn (Transaction $record): string => route('invoice.generate', ['transactionUuid' => $record->uuid]),
                            shouldOpenInNewTab: true
                        ),
                    Action::make('force-regenerate')
                        ->label(__('Force Regenerate Invoice'))
                        ->color('gray')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn (Transaction $record, InvoiceService $invoiceService): bool => $invoiceService->canGenerateInvoices($record))
                        ->url(
                            function (Transaction $record): string {
                                return route('invoice.generate', ['transactionUuid' => $record->uuid, 'regenerate' => true]);
                            },
                            shouldOpenInNewTab: true
                        ),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'user',
                'currency',
                'paymentProvider',
                'order',
                'subscription',
                'subscription.plan',
            ]))
            ->toolbarActions([

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
            'index' => ListTransactions::route('/'),
            'view' => ViewTranscription::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Transaction')
                    ->columnSpan('full')
                    ->tabs([
                        Tab::make(__('Details'))
                            ->icon('heroicon-s-currency-dollar')
                            ->schema([
                                TextEntry::make('uuid')->copyable(),
                                TextEntry::make('tenant')
                                    ->label(__('Tenant'))
                                    ->getStateUsing(function (Transaction $record) {
                                        return $record->tenant->name;
                                    })->url(fn (Transaction $record) => EditTenant::getUrl(['record' => $record->tenant])),
                                TextEntry::make('subscription_id')
                                    ->label(__('Subscription'))
                                    ->visible(fn (Transaction $record) => $record->subscription_id !== null)
                                    ->formatStateUsing(function (string $state, $record) {
                                        return $record->subscription->plan?->name ?? '-';
                                    })
                                    ->url(fn (Transaction $record) => $record->subscription ? ViewSubscription::getUrl(['record' => $record->subscription]) : '-')->badge()->color('info'),
                                TextEntry::make('status')
                                    ->label(__('Status'))
                                    ->colors([
                                        'success' => TransactionStatus::SUCCESS->value,
                                        'danger' => TransactionStatus::FAILED->value,
                                    ])
                                    ->formatStateUsing(function (string $state, $record, TransactionStatusMapper $mapper) {
                                        return $mapper->mapForDisplay($state);
                                    })
                                    ->badge(),
                                TextEntry::make('payment_provider_transaction_id')
                                    ->label(__('Payment Provider Transaction ID'))
                                    ->copyable(),
                                TextEntry::make('error_reason')
                                    ->label(__('Error Reason'))
                                    ->visible(fn (Transaction $record) => $record->error_reason !== null),
                                TextEntry::make('payment_provider_id')
                                    ->label(__('Payment Provider'))
                                    ->getStateUsing(fn (Transaction $record) => $record->paymentProvider->name),
                                TextEntry::make('payment_provider_status')
                                    ->label(__('Payment Provider Status'))
                                    ->badge()->color('info'),
                                TextEntry::make('amount')
                                    ->label(__('Amount'))
                                    ->formatStateUsing(function (string $state, $record) {
                                        return money($state, $record->currency->code);
                                    }),
                                TextEntry::make('total_discount')
                                    ->label(__('Total Discount'))
                                    ->formatStateUsing(function (string $state, $record) {
                                        return money($state, $record->currency->code);
                                    }),
                                TextEntry::make('total_fees')
                                    ->label(__('Total Fees'))
                                    ->formatStateUsing(function (string $state, $record) {
                                        return money($state, $record->currency->code);
                                    }),
                                TextEntry::make('total_tax')
                                    ->label(__('Total Tax'))
                                    ->formatStateUsing(function (string $state, $record) {
                                        return money($state, $record->currency->code);
                                    }),
                                TextEntry::make('created_at')
                                    ->label(__('Created At'))
                                    ->dateTime(config('app.datetime_format')),
                                TextEntry::make('updated_at')
                                    ->label(__('Updated At'))
                                    ->dateTime(config('app.datetime_format')),

                            ])
                            ->columns([
                                'xl' => 2,
                                '2xl' => 2,
                            ]),
                        Tab::make(__('Changes'))
                            ->icon('heroicon-m-arrow-uturn-down')
                            ->schema(function ($record) {
                                // Filament schema is called multiple times for some reason, so we need to cache the components to avoid performance issues.
                                return static::subscriptionHistoryComponents($record);
                            }),
                    ]),

            ]);
    }

    public static function getWidgets(): array
    {
        return [
            TransactionOverview::class,
        ];
    }

    public static function subscriptionHistoryComponents($record): array
    {
        if (! empty(static::$cachedTransactionHistoryComponents)) {
            return static::$cachedTransactionHistoryComponents;
        }

        $i = 0;
        foreach ($record->versions->reverse() as $version) {
            $versionModel = $version->getModel();

            static::$cachedTransactionHistoryComponents[] = Section::make([
                TextEntry::make('status_'.$i)
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($record, TransactionStatusMapper $mapper): string => $mapper->mapColor($record->status))
                    ->getStateUsing(fn ($record, TransactionStatusMapper $mapper): string => $mapper->mapForDisplay($record->status)),

                TextEntry::make('provider_status_'.$i)
                    ->label(__('Payment Provider Status'))
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn () => $versionModel->payment_provider_status),

                TextEntry::make('amount_'.$i)
                    ->label(__('Amount'))
                    ->getStateUsing(function () use ($versionModel) {
                        return money($versionModel->amount, $versionModel->currency->code);
                    }),

            ])->columns(4)->collapsible()->heading(
                date(config('app.datetime_format'), strtotime($version->created_at))
            );

            $i++;
        }

        return static::$cachedTransactionHistoryComponents;
    }

    public static function getNavigationLabel(): string
    {
        return __('Transactions');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Transactions');
    }

    public static function getModelLabel(): string
    {
        return __('Transaction');
    }
}
