<?php

namespace App\Services;

use App\Constants\OrderStatus;
use App\Constants\PlanType;
use App\Dto\CartDto;
use App\Dto\TotalsDto;
use App\Models\Plan;
use App\Models\Tenant;

class CheckoutService
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private OrderService $orderService,
        private TenantCreationService $tenantCreationService,
        private PlanService $planService,
    ) {}

    public function initSubscriptionCheckout(string $planSlug, ?string $tenantUuid, int $quantity = 1, bool $shouldCreateNewTenant = false)
    {
        $plan = $this->planService->getActivePlanBySlug($planSlug);
        $tenant = $this->resolveSubscriptionTenant($shouldCreateNewTenant, $tenantUuid, $plan);

        $subscription = $this->subscriptionService->findNewByPlanSlugAndTenant($planSlug, $tenant);
        if ($subscription === null) {
            $subscription = $this->subscriptionService->create(
                planSlug: $planSlug,
                userId: auth()->id(),
                quantity: $quantity,
                tenant: $tenant,
            );
        }

        $plan = $subscription->plan;

        if ($plan->type === PlanType::SEAT_BASED->value) { // in case tenant already had users inside
            $subscription->update(['quantity' => max($tenant->users->count(), $quantity)]);
        }

        return $subscription;
    }

    public function initLocalSubscriptionCheckout(string $planSlug, ?string $tenantUuid, int $quantity = 1, bool $shouldCreateNewTenant = false)
    {
        $plan = $this->planService->getActivePlanBySlug($planSlug);
        $tenant = $this->resolveSubscriptionTenant($shouldCreateNewTenant, $tenantUuid, $plan);

        $subscription = $this->subscriptionService->findNewByPlanSlugAndTenant($planSlug, $tenant);
        if ($subscription === null) {
            $subscription = $this->subscriptionService->create(
                $planSlug,
                auth()->id(),
                quantity: $quantity,
                tenant: $tenant,
                localSubscription: true);
        }

        $plan = $subscription->plan;

        if ($plan->type === PlanType::SEAT_BASED->value) { // in case tenant already had users inside
            $subscription->update(['quantity' => max($tenant->users->count(), $quantity)]);
        }

        return $subscription;
    }

    public function initProductCheckout(CartDto $cartDto, ?string $tenantUuid, TotalsDto $totalsDto, bool $shouldCreateNewTenant = false)
    {
        $user = auth()->user();

        $isLocalOrder = $totalsDto->amountDue === 0; // If amount due is zero, it's a local order (no payment provider needed)

        if ($shouldCreateNewTenant) {
            $tenant = $this->tenantCreationService->createTenant($user);
        } else {
            $tenant = $this->tenantCreationService->findUserTenantForNewOrderByUuid($user, $tenantUuid);

            if ($tenant === null) {
                // just find any tenant user can use
                $tenant = $this->tenantCreationService->findUserTenantForNewOrder($user);

                if ($tenant === null) {
                    $tenant = $this->tenantCreationService->createTenant($user);
                }
            }
        }

        $order = null;
        if ($cartDto->orderId !== null) {
            $order = $this->orderService->findNewByIdForUser($cartDto->orderId, $user);
        }

        if ($order === null) {
            $order = $this->orderService->create($user, $tenant, isLocal: $isLocalOrder);
        }

        $this->orderService->refreshOrder($cartDto, $order);

        if (! $isLocalOrder) {
            $order->status = OrderStatus::PENDING->value;
            $order->save();
        }

        return $order;
    }

    public function resolveSubscriptionTenant(bool $shouldCreateNewTenant, ?string $tenantUuid, ?Plan $plan = null): Tenant
    {
        if ($shouldCreateNewTenant) {
            $tenant = $this->tenantCreationService->createTenant(auth()->user());
        } else {
            $tenant = $this->tenantCreationService->findUserTenantForNewSubscriptionByUuid(auth()->user(), $tenantUuid, $plan);

            if ($tenant === null) {
                // just find any tenant user can use
                $tenant = $this->tenantCreationService->findUserTenantForNewSubscription(auth()->user());

                if ($tenant === null) {
                    $tenant = $this->tenantCreationService->createTenant(auth()->user());
                }
            }
        }

        return $tenant;
    }
}
