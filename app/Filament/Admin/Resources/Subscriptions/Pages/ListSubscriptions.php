<?php

namespace App\Filament\Admin\Resources\Subscriptions\Pages;

use App\Constants\SubscriptionStatus;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Filament\Admin\Resources\Subscriptions\SubscriptionResource;
use App\Filament\ListDefaults;
use App\Models\User;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use App\Services\TenantCreationService;
use App\Services\TenantService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

class ListSubscriptions extends ListRecords
{
    use ListDefaults;

    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('Create Subscription'))
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->label(__('User'))
                        ->getSearchResultsUsing(function (string $query) {
                            return User::query()
                                ->where('name', 'like', '%'.$query.'%')
                                ->orWhere('email', 'like', '%'.$query.'%')
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn ($user) => [$user->id => "{$user->name} <{$user->email}>"])->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value) => User::find($value)->name.' <'.User::find($value)->email.'>')
                        ->helperText(__('Adding a subscription to a user will create a "locally managed" subscription, which means the user will be able to use subscription features without being billed, and they can later convert to a "payment provider managed" subscription from their dashboard.'))
                        ->live()
                        ->required(),
                    Select::make('tenant_uuid')
                        ->label(__('Tenant'))
                        ->helperText(__('Select the tenant for which you want to create a subscription. If the user has multiple tenants, you can select one of them. If you do not select a tenant, a new tenant will be created for the user.'))
                        ->options(function (Get $get, TenantCreationService $tenantCreationService) {
                            $userId = $get('user_id');
                            if (! $userId) {
                                return [];
                            }

                            return $tenantCreationService->findUserTenantsForNewSubscription(User::find($userId))
                                ->pluck('name', 'uuid')
                                ->toArray();
                        }),
                    Select::make('plan_id')
                        ->label(__('Plan'))
                        ->options(function (PlanService $planService) {
                            return $planService->getAllPlansWithPrices()->mapWithKeys(function ($plan) {
                                return [$plan->id => $plan->name];
                            });
                        })
                        ->required(),
                    DateTimePicker::make('ends_at')
                        ->displayFormat(config('app.datetime_format'))
                        ->label(__('Ends At'))
                        ->afterOrEqual('now')
                        ->helperText(__('The date when the subscription will end.'))
                        ->required(),
                ])
                ->action(function (
                    array $data,
                    SubscriptionService $subscriptionService,
                    PlanService $planService,
                    TenantCreationService $tenantCreationService,
                    TenantService $tenantService,
                ) {
                    $user = User::find($data['user_id']);
                    $plan = $planService->getActivePlanById($data['plan_id']);
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

                    try {
                        $subscriptionService->create(
                            $plan->slug,
                            $user->id,
                            quantity: 1,
                            tenant: $tenant,
                            localSubscription: true,
                            endsAt: Carbon::parse($data['ends_at'])
                        );
                    } catch (SubscriptionCreationNotAllowedException $e) {
                        Notification::make()
                            ->title(__('Failed to create subscription. User already has an active subscription and cannot have more than one.'))
                            ->danger()
                            ->send();
                    }

                    Notification::make()
                        ->title(__('Subscription created successfully.'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            __('all') => Tab::make(),
            __('active') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubscriptionStatus::ACTIVE)),
            __('inactive') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubscriptionStatus::INACTIVE)),
            __('pending') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubscriptionStatus::PENDING)),
            __('canceled') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubscriptionStatus::CANCELED)),
            __('past Due') => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', SubscriptionStatus::PAST_DUE)),
        ];
    }
}
