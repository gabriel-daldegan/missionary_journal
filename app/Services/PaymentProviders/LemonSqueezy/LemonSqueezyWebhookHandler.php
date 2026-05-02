<?php

namespace App\Services\PaymentProviders\LemonSqueezy;

use App\Constants\LemonSqueezyConstants;
use App\Constants\OrderStatus;
use App\Constants\PaymentProviderConstants;
use App\Constants\SubscriptionStatus;
use App\Constants\SubscriptionType;
use App\Constants\TransactionStatus;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Models\Currency;
use App\Models\PaymentProvider;
use App\Models\User;
use App\Services\OneTimeProductService;
use App\Services\OrderService;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use App\Services\TenantCreationService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LemonSqueezyWebhookHandler
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private TransactionService $transactionService,
        private OrderService $orderService,
        private PlanService $planService,
        private OneTimeProductService $oneTimeProductService,
        private TenantCreationService $tenantCreationService,
    ) {}

    public function handleWebhook(Request $request): JsonResponse
    {
        $payloadString = $request->getContent();
        $signature = $request->header('x-signature');

        if (! $this->isValidSignature($payloadString, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $paymentProvider = PaymentProvider::where('slug', PaymentProviderConstants::LEMON_SQUEEZY_SLUG)->firstOrFail();

        // docs on events: https://docs.lemonsqueezy.com/help/webhooks#event-types

        $payload = $request->json()->all();
        if (! isset($payload['meta']['event_name'])) {
            return response()->json(['error' => 'Invalid event name'], 400);
        }

        $eventName = $payload['meta']['event_name'];
        $customData = $payload['meta']['custom_data'] ?? [];

        $data = $payload['data'];
        $attributes = $data['attributes'];

        $this->handleSubscriptionEvent($customData['subscription_uuid'] ?? null, $eventName, $attributes, $data, $paymentProvider);
        $this->handleOrderEvent($customData['order_uuid'] ?? null, $eventName, $attributes, $data, $paymentProvider);

        return response()->json();
    }

    private function handleOrderEvent(?string $orderUuid, string $eventName, array $attributes, array $data, PaymentProvider $paymentProvider)
    {
        if ($eventName === 'order_created' || $eventName === 'order_refunded') {

            if ($orderUuid === null && $eventName === 'order_created') {

                $order = $this->orderService->findByPaymentProviderOrderId($paymentProvider, $data['id']);

                if ($order === null) {
                    $order = $this->createOrder($attributes, $paymentProvider, $data['id']);

                    if ($order === null) {
                        return;
                    }
                }
            } else {
                if ($orderUuid) {
                    $order = $this->orderService->findByUuidOrFail($orderUuid);
                } else {
                    $order = $this->orderService->findByPaymentProviderOrderId($paymentProvider, $data['id']);
                }
            }

            $currency = Currency::where('code', strtoupper($attributes['currency']))->firstOrFail();
            $providerOrderStatus = $attributes['status'];

            $discount = $attributes['discount_total'] ?? 0;
            $tax = $attributes['tax'] ?? 0;

            $transaction = $this->transactionService->getTransactionByPaymentProviderTxId($data['id']);

            $mappedStatus = $this->mapOrderStatusToTransactionStatus($providerOrderStatus);

            if ($transaction) {
                $this->transactionService->updateTransactionByPaymentProviderTxId(
                    $data['id'],
                    $providerOrderStatus,
                    $mappedStatus,
                );
            } else {
                $this->transactionService->createForOrder(
                    $order,
                    $attributes['total'],
                    $tax,
                    $discount,
                    0,
                    $currency,
                    $paymentProvider,
                    $data['id'],
                    $providerOrderStatus,
                    $mappedStatus,
                );
            }

            $orderStatus = ($mappedStatus === TransactionStatus::SUCCESS) ?
                OrderStatus::SUCCESS : ($mappedStatus === TransactionStatus::REFUNDED ? OrderStatus::REFUNDED : OrderStatus::FAILED);

            $this->orderService->updateOrder($order, [
                'status' => $orderStatus,
                'payment_provider_id' => $paymentProvider->id,
                'payment_provider_order_id' => $data['id'],
            ]);
        }
    }

    private function createOrder(array $attributes, PaymentProvider $paymentProvider, string $providerOrderId)
    {
        $variantId = $attributes['first_order_item']['variant_id'] ?? null;
        $orderProduct = $this->oneTimeProductService->findByPaymentProviderProductId($paymentProvider, $variantId);

        if (! $orderProduct) {
            // can be a subscription order (because order_created event is also fired for subscriptions)

            return null;
        }

        $userEmail = $attributes['user_email'];
        $user = User::where('email', $userEmail)->first();

        if (! $user) {
            // create a new user
            $user = User::create([
                'email' => $userEmail,
                'name' => $attributes['user_name'] ?? $userEmail,
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        $currency = Currency::where('code', strtoupper($attributes['currency']))->firstOrFail();

        $orderItem = [
            'one_time_product_id' => $orderProduct->id,
            'quantity' => $attributes['first_order_item']['quantity'],
            'price_per_unit' => $attributes['first_order_item']['price'],
            'price_per_unit_after_discount' => $attributes['first_order_item']['price'],
            'currency_id' => $currency->id,
        ];

        $tenant = $this->tenantCreationService->createTenant($user);

        $order = $this->orderService->create(
            $user,
            $tenant,
            $paymentProvider,
            $attributes['subtotal'],
            $attributes['discount_total'] ?? 0,
            $attributes['total'],
            $currency,
            [$orderItem],
            $providerOrderId,
        );

        return $order;
    }

    private function handleSubscriptionEvent(?string $subscriptionUuid, string $eventName, array $attributes, array $data, PaymentProvider $paymentProvider)
    {
        if ($eventName === 'subscription_created') {

            if ($subscriptionUuid === null) {
                try {
                    $subscription = $this->createSubscription($attributes, $paymentProvider, $data['id']);
                } catch (SubscriptionCreationNotAllowedException) {
                    Log::error('Subscription creation not allowed', [
                        'attributes' => $attributes,
                        'payment_provider_id' => $paymentProvider->id,
                    ]);

                    throw new Exception('Subscription creation not allowed because you have an active subscription');
                }

            } else {
                $subscription = $this->subscriptionService->findByUuidOrFail($subscriptionUuid);
            }

            $lemonSqueezySubscriptionStatus = $attributes['status'];
            $subscriptionStatus = $this->mapLemonSqueezySubscriptionStatusToSubscriptionStatus($lemonSqueezySubscriptionStatus);
            $endsAt = Carbon::parse($attributes['renews_at'])->toDateTimeString();
            $trialEndsAt = $attributes['trial_ends_at'] !== null ? Carbon::parse($attributes['trial_ends_at'])->toDateTimeString() : null;
            $cancelledAt = $attributes['ends_at'] ? Carbon::parse($attributes['ends_at'])->toDateTimeString() : null;

            $extraPaymentProviderData = [];
            if (isset($attributes['first_subscription_item']) && isset($attributes['first_subscription_item']['id'])) {
                $extraPaymentProviderData[LemonSqueezyConstants::SUBSCRIPTION_ITEM_ID] = $attributes['first_subscription_item']['id'];
            }

            $this->subscriptionService->updateSubscription($subscription, [
                'type' => SubscriptionType::PAYMENT_PROVIDER_MANAGED,
                'status' => $subscriptionStatus,
                'ends_at' => $endsAt,
                'payment_provider_subscription_id' => $data['id'],
                'payment_provider_status' => $lemonSqueezySubscriptionStatus,
                'payment_provider_id' => $paymentProvider->id,
                'trial_ends_at' => $trialEndsAt,
                'cancelled_at' => $cancelledAt,
                'extra_payment_provider_data' => $extraPaymentProviderData,
            ]);
        } elseif ($eventName === 'subscription_updated' ||
            $eventName === 'subscription_cancelled' ||
            $eventName === 'subscription_resumed' ||
            $eventName === 'subscription_expired' ||
            $eventName === 'subscription_paused' ||
            $eventName === 'subscription_unpaused'
        ) {
            $subscription = $this->subscriptionService->findByPaymentProviderId($paymentProvider, $data['id']);
            $lemonSqueezySubscriptionStatus = $attributes['status'];
            $subscriptionStatus = $this->mapLemonSqueezySubscriptionStatusToSubscriptionStatus($lemonSqueezySubscriptionStatus);
            $endsAt = Carbon::parse($attributes['renews_at'])->toDateTimeString();
            $trialEndsAt = $attributes['trial_ends_at'] !== null ? Carbon::parse($attributes['trial_ends_at'])->toDateTimeString() : null;
            $cancelledAt = $attributes['ends_at'] !== null ? Carbon::parse($attributes['ends_at'])->toDateTimeString() : null;
            $isCanceledAtTheEndOfCycle = $attributes['cancelled'] ?? false;
            $quantity = $attributes['first_subscription_item']['quantity'] ?? 1;
            $extraPaymentProviderData = [];
            if (isset($attributes['first_subscription_item']) && isset($attributes['first_subscription_item']['id'])) {
                $extraPaymentProviderData[LemonSqueezyConstants::SUBSCRIPTION_ITEM_ID] = $attributes['first_subscription_item']['id'];
            }

            $this->subscriptionService->updateSubscription($subscription, [
                'type' => SubscriptionType::PAYMENT_PROVIDER_MANAGED,
                'status' => $subscriptionStatus,
                'ends_at' => $endsAt,
                'payment_provider_subscription_id' => $data['id'],
                'payment_provider_status' => $lemonSqueezySubscriptionStatus,
                'payment_provider_id' => $paymentProvider->id,
                'trial_ends_at' => $trialEndsAt,
                'cancelled_at' => $cancelledAt,
                'is_canceled_at_end_of_cycle' => $isCanceledAtTheEndOfCycle,
                'quantity' => $quantity,
                'extra_payment_provider_data' => $extraPaymentProviderData,
            ]);

        } elseif ($eventName === 'subscription_payment_success' || $eventName === 'subscription_payment_failed') {
            $subscription = $this->subscriptionService->findByPaymentProviderId($paymentProvider, $attributes['subscription_id']);
            $currency = Currency::where('code', strtoupper($attributes['currency']))->firstOrFail();
            $invoiceStatus = $attributes['status'];

            $discount = $attributes['discount_total'] ?? 0;
            $tax = $attributes['tax'] ?? 0;

            $transaction = $this->transactionService->getTransactionByPaymentProviderTxId($data['id']);
            $mappedStatus = $this->mapOrderStatusToTransactionStatus($invoiceStatus);

            if ($transaction) {
                $this->transactionService->updateTransactionByPaymentProviderTxId(
                    $data['id'],
                    $invoiceStatus,
                    $mappedStatus,
                );
            } else {
                $this->transactionService->createForSubscription(
                    $subscription,
                    $attributes['total'],
                    $tax,
                    $discount,
                    0,
                    $currency,
                    $paymentProvider,
                    $data['id'],
                    $invoiceStatus,
                    $mappedStatus,
                );
            }

            if ($mappedStatus === TransactionStatus::FAILED) {
                $this->subscriptionService->handleInvoicePaymentFailed($subscription);
            }
        }
    }

    private function createSubscription(array $attributes, PaymentProvider $paymentProvider, string $providerSubscriptionId)
    {
        $userEmail = $attributes['user_email'];
        $user = User::where('email', $userEmail)->first();

        if (! $user) {
            // create a new user
            $user = User::create([
                'email' => $userEmail,
                'name' => $attributes['user_name'] ?? $userEmail,
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        $plan = $this->planService->findByPaymentProviderProductId($paymentProvider, $attributes['variant_id']);

        if (! $plan) {
            Log::error('Plan not found for subscription', [
                'payment_provider_id' => $paymentProvider->id,
                'payment_provider_product_id' => $attributes['variant_id'],
            ]);

            return null;
        }

        $quantity = $attributes['first_subscription_item']['quantity'] ?? 1;

        $tenant = $this->tenantCreationService->createTenant($user);

        return $this->subscriptionService->create($plan->slug, $user->id, $quantity, $tenant, $paymentProvider, $providerSubscriptionId);
    }

    private function mapOrderStatusToTransactionStatus(string $providerOrderStatus): TransactionStatus
    {
        if ($providerOrderStatus == 'paid') {
            return TransactionStatus::SUCCESS;
        }

        if ($providerOrderStatus == 'refunded') {
            return TransactionStatus::REFUNDED;
        }

        if ($providerOrderStatus == 'pending') {
            return TransactionStatus::PENDING;
        }

        if ($providerOrderStatus == 'failed') {
            return TransactionStatus::FAILED;
        }

        return TransactionStatus::NOT_STARTED;
    }

    private function mapLemonSqueezySubscriptionStatusToSubscriptionStatus(string $providerSubscriptionStatus)
    {
        if ($providerSubscriptionStatus == 'active' || $providerSubscriptionStatus == 'on_trial') {
            return SubscriptionStatus::ACTIVE;
        }

        if ($providerSubscriptionStatus == 'cancelled') {  // lemon squeezy sets the subscription to cancelled immediately after the user cancels it, so we still need to keep the subscription active until the end of the billing period (ends_at)
            return SubscriptionStatus::ACTIVE;
        }

        if ($providerSubscriptionStatus == 'past_due' || $providerSubscriptionStatus == 'unpaid') {
            return SubscriptionStatus::PAST_DUE;
        }

        if ($providerSubscriptionStatus == 'paused') {
            return SubscriptionStatus::PAUSED;
        }

        return SubscriptionStatus::INACTIVE;

    }

    private function isValidSignature(string $payload, ?string $signature)
    {
        if (empty($signature)) {
            return false;
        }

        $hash = hash_hmac('sha256', $payload, config('services.lemon-squeezy.signing_secret'));

        return hash_equals($hash, $signature);
    }
}
