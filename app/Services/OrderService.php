<?php

namespace App\Services;

use App\Constants\OrderStatus;
use App\Constants\PaymentProviderConstants;
use App\Dto\CartDto;
use App\Events\Order\Ordered;
use App\Events\Order\OrderedOffline;
use App\Events\Order\OrderRefunded;
use App\Exceptions\TenantException;
use App\Models\Currency;
use App\Models\OneTimeProduct;
use App\Models\Order;
use App\Models\PaymentProvider;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private CalculationService $calculationService,
    ) {}

    public function create(
        User $user,
        Tenant $tenant,
        ?PaymentProvider $paymentProvider = null,
        ?int $totalAmount = null,
        ?int $discountTotal = null,
        ?int $totalAmountAfterDiscount = null,
        ?Currency $currency = null,
        ?array $orderItems = [],
        $paymentProviderOrderId = null,
        bool $isLocal = false,
    ): Order {
        $orderAttributes = [
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'status' => OrderStatus::NEW->value,
            'total_amount' => $totalAmount ?? 0,
            'tenant_id' => $tenant->id,
            'is_local' => $isLocal,
        ];

        if ($paymentProvider) {
            $orderAttributes['payment_provider_id'] = $paymentProvider->id;
        }

        if ($discountTotal) {
            $orderAttributes['total_discount_amount'] = $discountTotal;
        }

        if ($totalAmountAfterDiscount) {
            $orderAttributes['total_amount_after_discount'] = $totalAmountAfterDiscount;
        }

        if ($currency) {
            $orderAttributes['currency_id'] = $currency->id;
        }

        if ($paymentProviderOrderId) {
            $orderAttributes['payment_provider_order_id'] = $paymentProviderOrderId;
        }

        if ($isLocal) {
            $orderAttributes['status'] = OrderStatus::SUCCESS->value; // Local orders are considered successful immediately
        }

        $order = Order::create($orderAttributes);

        if ($orderItems) {
            $order->items()->createMany($orderItems);
        }

        if ($isLocal) {
            // if it's a local order, dispatch the Ordered event immediately
            Ordered::dispatch($order);
        }

        return $order;
    }

    public function findByUuidOrFail(string $uuid): Order
    {
        return Order::where('uuid', $uuid)->firstOrFail();
    }

    public function findByPaymentProviderOrderId(PaymentProvider $paymentProvider, string $paymentProviderOrderId): ?Order
    {
        return Order::where('payment_provider_id', $paymentProvider->id)
            ->where('payment_provider_order_id', $paymentProviderOrderId)
            ->first();
    }

    public function updateOrder(
        Order $order,
        array $data
    ): Order {
        $oldStatus = $order->status;
        $newStatus = $data['status'] ?? $oldStatus;
        $order->update($data);

        $this->handleDispatchingEvents(
            $oldStatus,
            $newStatus,
            $order
        );

        return $order;
    }

    private function handleDispatchingEvents(
        ?string $oldStatus,
        string|OrderStatus $newStatus,
        Order $order
    ): void {
        $newStatus = $newStatus instanceof OrderStatus ? $newStatus->value : $newStatus;

        if ($oldStatus !== $newStatus) {
            switch ($newStatus) {
                case OrderStatus::SUCCESS->value:
                    Ordered::dispatch($order);
                    break;
                case OrderStatus::REFUNDED->value:
                    OrderRefunded::dispatch($order);
                    break;
            }
        }

        if ($newStatus == OrderStatus::PENDING->value && $order->is_local && $order->paymentProvider->slug === PaymentProviderConstants::OFFLINE_SLUG) {
            // If the order is pending and it's an offline order, dispatch OrderedOffline event - (you can use this to let the user know that they need to pay offline)
            OrderedOffline::dispatch($order);
        }
    }

    public function refreshOrder(CartDto $cartDto, Order $order)
    {
        $existingProductIds = $order->items->pluck('one_time_product_id')->toArray();
        $newProductIds = [];
        foreach ($cartDto->items as $item) {
            $newProductIds[] = $item->productId;
        }

        $cartProductToQuantity = [];
        foreach ($cartDto->items as $item) {
            $cartProductToQuantity[$item->productId] = $item->quantity;
        }

        $productIdsToAdd = array_diff($newProductIds, $existingProductIds);
        $productIdsToRemove = array_diff($existingProductIds, $newProductIds);
        $productsToUpdate = array_intersect($existingProductIds, $newProductIds);
        $productsToAdd = OneTimeProduct::whereIn('id', $productIdsToAdd)->get();

        DB::transaction(function () use ($order, $productIdsToRemove, $productsToAdd, $cartDto, $cartProductToQuantity, $productsToUpdate) {

            foreach ($productIdsToRemove as $productId) {
                $order->items()->where('one_time_product_id', $productId)->delete();
            }

            foreach ($productsToAdd as $product) {
                $order->items()->create([
                    'one_time_product_id' => $product->id,
                    'quantity' => $cartProductToQuantity[$product->id],
                    'price_per_unit' => 0,
                ]);
            }

            foreach ($productsToUpdate as $productId) {
                $orderItem = $order->items()->where('one_time_product_id', $productId)->first();
                $orderItem->quantity = $cartProductToQuantity[$productId];
                $orderItem->save();
            }

            $order->save();

            $order->refresh();

            $this->calculationService->calculateOrderTotals($order, auth()->user(), $cartDto->discountCode);

            $order->save();
        });
    }

    public function findNewByIdForUser(int $orderId, User $user): ?Order
    {
        return Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->where('status', OrderStatus::NEW)
            ->first();
    }

    public function hasOrderedProduct(User $user, string $productSlug): bool
    {
        $product = OneTimeProduct::where('slug', $productSlug)->first();

        if (! $product) {
            return false;
        }

        return $user->orders()->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.one_time_product_id', $product->id)
            ->where('orders.status', OrderStatus::SUCCESS)
            ->exists();
    }

    public function hasUserOrdered(?User $user, ?string $productSlug = null, ?Tenant $tenant = null): bool
    {
        if (! $user) {
            return false;
        }

        $tenant = $tenant ?? Filament::getTenant();

        if (! $tenant) {
            throw new TenantException('Could not resolve tenant: You either need to specify a tenant or be in a tenant context to check if a user purchased a product.');
        }

        $userTenant = $user->tenants()->where('tenant_id', $tenant->id)->first();

        if (! $userTenant) {
            return false;
        }

        if (! $productSlug) {
            return $userTenant->orders()
                ->where('status', OrderStatus::SUCCESS)
                ->exists();
        }

        return $userTenant->orders()
            ->where('status', OrderStatus::SUCCESS)
            ->whereHas('items', function ($query) use ($productSlug) {
                $query->whereHas('oneTimeProduct', function ($query) use ($productSlug) {
                    $query->where('slug', $productSlug);
                });
            })
            ->exists();
    }

    public function hasUserOrderedViaAnyTenant(User $user, ?string $productSlug = null): bool
    {
        $tenantIds = $user->tenants()->pluck('tenant_id')->toArray();

        if (empty($tenantIds)) {
            return false;
        }

        if (! $productSlug) {
            return Order::whereIn('tenant_id', $tenantIds)
                ->where('status', OrderStatus::SUCCESS)
                ->exists();
        }

        return Order::whereIn('tenant_id', $tenantIds)
            ->where('status', OrderStatus::SUCCESS)
            ->whereHas('items', function ($query) use ($productSlug) {
                $query->whereHas('oneTimeProduct', function ($query) use ($productSlug) {
                    $query->where('slug', $productSlug);
                });
            })
            ->exists();
    }

    public function findAllTenantSuccessfulOrders(?Tenant $tenant): Collection
    {
        $tenant = $tenant ?? Filament::getTenant();

        if (! $tenant) {
            return collect();
        }

        return $tenant->orders()
            ->where('status', OrderStatus::SUCCESS)
            ->with(['items.oneTimeProduct'])
            ->get();
    }

    public function findAllTenantOrderedProducts(?Tenant $tenant): array
    {
        $orders = $this->findAllTenantSuccessfulOrders($tenant);

        $orderedProducts = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $orderedProducts[$item->oneTimeProduct->slug] = $item->oneTimeProduct;
            }
        }

        return array_values($orderedProducts);
    }

    public function getUserOrderedProductsMetadata(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $orders = $user->orders()
            ->where('status', OrderStatus::SUCCESS)
            ->with(['items.oneTimeProduct'])
            ->get();

        return $orders->flatMap(function (Order $order) {
            return $order->items->mapWithKeys(function ($item) {
                return [$item->oneTimeProduct->slug => $item->oneTimeProduct->metadata];
            });
        })->toArray();
    }

    public function canUpdateOrder(Order $order): bool
    {
        return $order->is_local;
    }
}
