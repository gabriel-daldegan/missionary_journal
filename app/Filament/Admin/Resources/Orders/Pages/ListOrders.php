<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Constants\OrderStatus;
use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\OneTimeProductService;
use App\Services\OrderService;
use App\Services\TenantCreationService;
use App\Services\TenantService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create-order')
                ->label(__('Create Order'))
                ->schema([
                    Select::make('user_id')
                        ->label(__('User'))
                        ->searchable()
                        ->getSearchResultsUsing(function (string $query) {
                            return User::query()
                                ->where('name', 'like', '%'.$query.'%')
                                ->orWhere('email', 'like', '%'.$query.'%')
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($user) => [$user->id => "{$user->name} <{$user->email}>"])->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value) => User::find($value)->name.' <'.User::find($value)->email.'>')
                        ->helperText(__('Adding an order manually to a user will add a zero amount order to the user\'s account, and user will be able to have access to any parts of your application that require a user to have ordered that product.'))
                        ->live()
                        ->label(__('User'))
                        ->required(),
                    Select::make('tenant_uuid')
                        ->label(__('Tenant'))
                        ->helperText(__('Select the tenant for which you want to create a order. If the user has multiple tenants, you can select one of them. If you do not select a tenant, a new tenant will be created for the user.'))
                        ->options(function (Get $get, TenantCreationService $tenantCreationService) {
                            $userId = $get('user_id');
                            if (! $userId) {
                                return [];
                            }

                            return $tenantCreationService->findUserTenantsForNewOrder(User::find($userId))
                                ->pluck('name', 'uuid')
                                ->toArray();
                        }),
                    Select::make('one_time_product_id')
                        ->label(__('Product'))
                        ->options(function (OneTimeProductService $productService) {
                            return $productService->getAllProductsWithPrices()->mapWithKeys(function ($product) {
                                return [$product->id => $product->name];
                            });
                        })
                        ->required(),
                    TextInput::make('quantity')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required()
                        ->label(__('Quantity')),
                ])
                ->action(function (
                    array $data,
                    OrderService $orderService,
                    OneTimeProductService $oneTimeProductService,
                    CurrencyService $currencyService,
                    TenantCreationService $tenantCreationService,
                    TenantService $tenantService,
                ) {
                    $user = User::find($data['user_id']);
                    $product = $oneTimeProductService->getActiveOneTimeProductById($data['one_time_product_id']);
                    $orderItem = [
                        'one_time_product_id' => $product->id,
                        'quantity' => $data['quantity'],
                        'price_per_unit' => 0,
                    ];

                    $selectedTenantUuid = $data['tenant_uuid'] ?? null;

                    if ($selectedTenantUuid !== null) {
                        $tenant = $tenantService->getTenantByUuid($selectedTenantUuid);
                        if (! $tenant) {
                            Notification::make()
                                ->title(__('Selected tenant not found.'))
                                ->danger()
                                ->send();

                            return;
                        }
                    } else {
                        $tenant = $tenantCreationService->createTenant($user);
                    }

                    $orderService->create(
                        user: $user,
                        tenant: $tenant,
                        currency: $currencyService->getCurrency(),
                        orderItems: [$orderItem],
                        isLocal: true,
                    );

                    Notification::make()
                        ->title(__('Order created successfully.'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            __('all') => Tab::make(),
            __('success') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::SUCCESS)),
            __('refunded') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::REFUNDED)),
            __('pending') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::PENDING)),
            __('failed') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::FAILED)),
            __('disputed') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', OrderStatus::DISPUTED)),
        ];
    }
}
