<?php

namespace App\Services\PaymentProviders\Polar;

use App\Constants\OrderStatus;
use App\Constants\PaymentProviderConstants;
use App\Constants\SubscriptionStatus;
use App\Constants\SubscriptionType;
use App\Constants\TransactionStatus;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Models\Currency;
use App\Models\PaymentProvider;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PolarWebhookHandler
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private TransactionService $transactionService,
        private OrderService $orderService,
        private PlanService $planService,
    ) {}

    public function handleWebhook(Request $request): JsonResponse
    {
        $payloadString = $request->getContent();

        if (! $this->isValidSignature($payloadString, $request)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $paymentProvider = PaymentProvider::where('slug', PaymentProviderConstants::POLAR_SLUG)->firstOrFail();

        $payload = $request->json()->all();

        $eventType = $payload['type'] ?? null;

        if (! $eventType) {
            return response()->json(['error' => 'Invalid event type'], 400);
        }

        $data = $payload['data'] ?? [];

        $this->handleEvent($eventType, $data, $paymentProvider);

        return response()->json();
    }

    private function handleEvent(string $eventType, array $data, PaymentProvider $paymentProvider): void
    {
        match (true) {
            str_starts_with($eventType, 'subscription.') => $this->handleSubscriptionEvent($eventType, $data, $paymentProvider),
            str_starts_with($eventType, 'order.') => $this->handleOrderEvent($eventType, $data, $paymentProvider),
            default => Log::warning('Unhandled Polar webhook event: '.$eventType),
        };
    }

    private function handleSubscriptionEvent(string $eventType, array $data, PaymentProvider $paymentProvider): void
    {
        $metadata = $data['metadata'] ?? [];
        $subscriptionUuid = $metadata['subscription_uuid'] ?? null;
        $providerSubscriptionId = $data['id'] ?? null;
        $providerStatus = $data['status'] ?? null;

        if ($eventType === 'subscription.created' || $eventType === 'subscription.active') {
            $subscription = $this->findOrCreateSubscription($subscriptionUuid, $providerSubscriptionId, $data, $paymentProvider);

            if ($subscription === null) {
                return;
            }

            $subscriptionStatus = $this->mapPolarStatusToSubscriptionStatus($providerStatus);
            $endsAt = isset($data['current_period_end']) ? Carbon::parse($data['current_period_end'])->toDateTimeString() : null;

            $extraData = $subscription->extra_payment_provider_data ?? [];
            $customerId = $data['customer_id'] ?? null;
            if ($customerId) {
                $extraData['customer_id'] = $customerId;
            }

            $updateData = [
                'type' => SubscriptionType::PAYMENT_PROVIDER_MANAGED,
                'status' => $subscriptionStatus,
                'ends_at' => $endsAt,
                'payment_provider_subscription_id' => $providerSubscriptionId,
                'payment_provider_status' => $providerStatus,
                'payment_provider_id' => $paymentProvider->id,
                'extra_payment_provider_data' => $extraData,
            ];

            if (isset($data['seats'])) {
                $updateData['quantity'] = $data['seats'];
            }

            $this->subscriptionService->updateSubscription($subscription, $updateData);
        } elseif ($eventType === 'subscription.updated' ||
            $eventType === 'subscription.canceled' ||
            $eventType === 'subscription.uncanceled'
        ) {
            $subscription = $this->findSubscription($subscriptionUuid, $providerSubscriptionId, $paymentProvider);

            $endsAt = isset($data['current_period_end']) ? Carbon::parse($data['current_period_end'])->toDateTimeString() : null;
            $subscriptionStatus = $this->mapPolarStatusToSubscriptionStatus($providerStatus);

            $extraData = $subscription->extra_payment_provider_data ?? [];
            $customerId = $data['customer_id'] ?? null;
            if ($customerId) {
                $extraData['customer_id'] = $customerId;
            }

            $cancelAtPeriodEnd = $data['cancel_at_period_end'] ?? false;

            $updateData = [
                'status' => $subscriptionStatus,
                'payment_provider_status' => $providerStatus,
                'payment_provider_subscription_id' => $providerSubscriptionId,
                'payment_provider_id' => $paymentProvider->id,
                'extra_payment_provider_data' => $extraData,
                'is_canceled_at_end_of_cycle' => $cancelAtPeriodEnd,
            ];

            if (isset($data['seats'])) {
                $updateData['quantity'] = $data['seats'];
            }

            if ($endsAt) {
                $updateData['ends_at'] = $endsAt;
            }

            if ($cancelAtPeriodEnd) {
                $updateData['cancelled_at'] = now()->toDateTimeString();
            }

            $this->subscriptionService->updateSubscription($subscription, $updateData);
        } elseif ($eventType === 'subscription.revoked') {
            $subscription = $this->findSubscription($subscriptionUuid, $providerSubscriptionId, $paymentProvider);

            $this->subscriptionService->updateSubscription($subscription, [
                'status' => SubscriptionStatus::INACTIVE,
                'payment_provider_status' => 'revoked',
            ]);
        }
    }

    private function handleOrderEvent(string $eventType, array $data, PaymentProvider $paymentProvider): void
    {
        $metadata = $data['metadata'] ?? [];
        $orderUuid = $metadata['order_uuid'] ?? null;
        $subscriptionUuid = $metadata['subscription_uuid'] ?? null;

        if ($eventType === 'order.paid' || $eventType === 'order.updated') {
            if ($orderUuid) {
                $this->handleOneTimeOrderPaid($data, $orderUuid, $paymentProvider);
            } elseif ($subscriptionUuid) {
                $this->handleSubscriptionOrderPaid($data, $subscriptionUuid, $paymentProvider);
            } else {
                $subscriptionId = $data['subscription_id'] ?? null;
                if ($subscriptionId) {
                    $this->handleSubscriptionOrderPaidByProviderId($data, $subscriptionId, $paymentProvider);
                }
            }
        } elseif ($eventType === 'order.refunded') {
            $this->handleOrderRefunded($data);
        }
    }

    private function handleOneTimeOrderPaid(array $data, string $orderUuid, PaymentProvider $paymentProvider): void
    {
        $order = $this->orderService->findByUuidOrFail($orderUuid);

        $transactionId = $data['id'] ?? null;
        $amount = $data['net_amount'] ?? 0;
        $tax = $data['tax_amount'] ?? 0;
        $discountAmount = $data['discount_amount'] ?? 0;
        $totalFees = $data['platform_fee_amount'] ?? 0;
        $currencyCode = strtoupper($data['currency'] ?? 'usd');
        $currency = Currency::where('code', $currencyCode)->firstOrFail();

        $transaction = $this->transactionService->getTransactionByPaymentProviderTxId($transactionId);

        if ($transaction) {
            $this->transactionService->updateTransactionByPaymentProviderTxId(
                $transactionId,
                $data['status'] ?? 'unknown',
                TransactionStatus::SUCCESS,
                newFees: $totalFees,
            );
        } else {
            $this->transactionService->createForOrder(
                $order,
                $data['total_amount'] ?? $amount,
                $tax,
                $discountAmount,
                $totalFees,
                $currency,
                $paymentProvider,
                $transactionId,
                $data['status'] ?? 'unknown',
                TransactionStatus::SUCCESS,
            );
        }

        $this->orderService->updateOrder($order, [
            'status' => OrderStatus::SUCCESS,
            'payment_provider_id' => $paymentProvider->id,
            'payment_provider_order_id' => $data['id'],
            'total_amount' => $data['subtotal_amount'] ?? $amount,
            'total_amount_after_discount' => ($data['subtotal_amount'] ?? $amount) - $discountAmount,
            'total_discount_amount' => $discountAmount,
            'currency_id' => $currency->id,
        ]);
    }

    private function handleSubscriptionOrderPaid(array $data, string $subscriptionUuid, PaymentProvider $paymentProvider): void
    {
        $subscription = $this->subscriptionService->findByUuidOrFail($subscriptionUuid);

        $this->createSubscriptionTransaction($data, $subscription, $paymentProvider);
    }

    private function handleSubscriptionOrderPaidByProviderId(array $data, string $subscriptionId, PaymentProvider $paymentProvider): void
    {
        $subscription = $this->subscriptionService->findByPaymentProviderId($paymentProvider, $subscriptionId);

        if (! $subscription) {
            Log::warning('Polar subscription not found for order payment', [
                'subscription_id' => $subscriptionId,
            ]);

            return;
        }

        $this->createSubscriptionTransaction($data, $subscription, $paymentProvider);
    }

    private function createSubscriptionTransaction(array $data, $subscription, PaymentProvider $paymentProvider): void
    {
        $transactionId = $data['id'] ?? null;
        $amount = $data['total_amount'] ?? 0;
        $tax = $data['tax_amount'] ?? 0;
        $discountAmount = $data['discount_amount'] ?? 0;
        $totalFees = $data['platform_fee_amount'] ?? 0;
        $currencyCode = strtoupper($data['currency'] ?? 'usd');
        $currency = Currency::where('code', $currencyCode)->firstOrFail();

        $transaction = $this->transactionService->getTransactionByPaymentProviderTxId($transactionId);

        if ($transaction) {
            $this->transactionService->updateTransactionByPaymentProviderTxId(
                $transactionId,
                $data['status'] ?? 'unknown',
                TransactionStatus::SUCCESS,
                newFees: $totalFees,
            );
        } else {
            $this->transactionService->createForSubscription(
                $subscription,
                $amount,
                $tax,
                $discountAmount,
                $totalFees,
                $currency,
                $paymentProvider,
                $transactionId,
                $data['status'] ?? 'unknown',
                TransactionStatus::SUCCESS,
            );
        }
    }

    private function handleOrderRefunded(array $data): void
    {
        $transactionId = $data['id'] ?? null;

        if ($transactionId === null) {
            return;
        }

        $transaction = $this->transactionService->getTransactionByPaymentProviderTxId($transactionId);

        if ($transaction) {
            $this->transactionService->updateTransactionByPaymentProviderTxId(
                $transactionId,
                'refunded',
                TransactionStatus::REFUNDED,
            );

            if ($transaction->order_id) {
                $order = $transaction->order;
                if ($order) {
                    $this->orderService->updateOrder($order, [
                        'status' => OrderStatus::REFUNDED,
                    ]);
                }
            }
        }
    }

    private function findSubscription(?string $subscriptionUuid, ?string $providerSubscriptionId, PaymentProvider $paymentProvider)
    {
        if ($subscriptionUuid !== null) {
            return $this->subscriptionService->findByUuidOrFail($subscriptionUuid);
        }

        return $this->subscriptionService->findByPaymentProviderId($paymentProvider, $providerSubscriptionId);
    }

    private function findOrCreateSubscription(?string $subscriptionUuid, ?string $providerSubscriptionId, array $data, PaymentProvider $paymentProvider)
    {
        if ($subscriptionUuid !== null) {
            return $this->subscriptionService->findByUuidOrFail($subscriptionUuid);
        }

        $existingSubscription = $this->subscriptionService->findByPaymentProviderId($paymentProvider, $providerSubscriptionId);

        if ($existingSubscription) {
            return $existingSubscription;
        }

        try {
            return $this->createSubscription($data, $paymentProvider, $providerSubscriptionId);
        } catch (SubscriptionCreationNotAllowedException) {
            Log::error('Subscription creation not allowed', [
                'data' => $data,
                'payment_provider_id' => $paymentProvider->id,
            ]);

            throw new Exception('Subscription creation not allowed because you have an active subscription');
        }
    }

    private function createSubscription(array $data, PaymentProvider $paymentProvider, string $providerSubscriptionId)
    {
        $customerEmail = $data['customer']['email'] ?? null;

        if ($customerEmail === null) {
            Log::error('No customer email in Polar webhook for subscription creation');

            return null;
        }

        $user = User::where('email', $customerEmail)->first();

        if (! $user) {
            $user = User::create([
                'email' => $customerEmail,
                'name' => $data['customer']['name'] ?? $customerEmail,
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        $productId = $data['product_id'] ?? null;
        $plan = $this->planService->findByPaymentProviderProductId($paymentProvider, $productId);

        if (! $plan) {
            Log::error('Plan not found for Polar subscription', [
                'payment_provider_id' => $paymentProvider->id,
                'product_id' => $productId,
            ]);

            return null;
        }

        return $this->subscriptionService->create($plan->slug, $user->id, $paymentProvider, $providerSubscriptionId);
    }

    private function mapPolarStatusToSubscriptionStatus(?string $providerStatus): SubscriptionStatus
    {
        return match ($providerStatus) {
            'active', 'trialing' => SubscriptionStatus::ACTIVE,
            'past_due' => SubscriptionStatus::PAST_DUE,
            'canceled', 'revoked' => SubscriptionStatus::INACTIVE,
            default => SubscriptionStatus::INACTIVE,
        };
    }

    private function isValidSignature(string $payload, Request $request): bool
    {
        $webhookId = $request->header('webhook-id');
        $webhookTimestamp = $request->header('webhook-timestamp');
        $webhookSignature = $request->header('webhook-signature');

        if (empty($webhookId) || empty($webhookTimestamp) || empty($webhookSignature)) {
            return false;
        }

        // Verify timestamp is within tolerance (5 minutes)
        $tolerance = 300;
        $currentTime = time();
        $timestampInt = (int) $webhookTimestamp;
        if (abs($currentTime - $timestampInt) > $tolerance) {
            return false;
        }

        $secret = config('services.polar.webhook_secret');

        // Polar provides a raw secret string (not base64-encoded).
        // Use it directly as the HMAC key per Polar docs.
        $signedContent = $webhookId.'.'.$webhookTimestamp.'.'.$payload;
        $expectedSignature = base64_encode(hash_hmac('sha256', $signedContent, $secret, true));

        // The webhook-signature header can contain multiple signatures separated by spaces
        $signatures = explode(' ', $webhookSignature);
        foreach ($signatures as $sig) {
            $parts = explode(',', $sig, 2);
            $sigValue = $parts[1] ?? $parts[0];
            if (hash_equals($expectedSignature, $sigValue)) {
                return true;
            }
        }

        return false;
    }
}
