<?php

namespace App\Services\PaymentProviders\Creem;

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

class CreemWebhookHandler
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
        $signature = $request->header('creem-signature');

        if (! $this->isValidSignature($payloadString, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $paymentProvider = PaymentProvider::where('slug', PaymentProviderConstants::CREEM_SLUG)->firstOrFail();

        $payload = $request->json()->all();

        if (! isset($payload['eventType'])) {
            return response()->json(['error' => 'Invalid event type'], 400);
        }

        $eventType = $payload['eventType'];
        $object = $payload['object'] ?? [];

        $this->handleEvent($eventType, $object, $paymentProvider);

        return response()->json();
    }

    private function handleEvent(string $eventType, array $object, PaymentProvider $paymentProvider): void
    {
        match (true) {
            str_starts_with($eventType, 'subscription.') => $this->handleSubscriptionEvent($eventType, $object, $paymentProvider),
            $eventType === 'checkout.completed' => $this->handleCheckoutCompleted($object, $paymentProvider),
            $eventType === 'refund.created' => $this->handleRefund($object),
            default => Log::warning('Unhandled Creem webhook event: '.$eventType),
        };
    }

    private function handleCheckoutCompleted(array $object, PaymentProvider $paymentProvider): void
    {
        $metadata = $object['metadata'] ?? [];
        $orderUuid = $metadata['order_uuid'] ?? null;

        if ($orderUuid === null) {
            return;
        }

        $order = $this->orderService->findByUuidOrFail($orderUuid);

        $creemOrderObject = $object['order'];
        $transactionId = $object['order']['transaction'] ?? null;
        $amount = $creemOrderObject['amount'] ?? 0;
        $amountPaid = $creemOrderObject['amount_paid'] ?? 0;
        $tax = $creemOrderObject['tax_amount'] ?? 0;
        $discount = $creemOrderObject['discount_amount'] ?? 0;
        $currencyCode = strtoupper($creemOrderObject['currency'] ?? 'USD');
        $currency = Currency::where('code', $currencyCode)->firstOrFail();

        $transaction = $this->transactionService->getTransactionByPaymentProviderTxId($transactionId);

        if ($transaction) {
            $this->transactionService->updateTransactionByPaymentProviderTxId(
                $transactionId,
                'paid',
                TransactionStatus::SUCCESS,
            );
        } else {
            $this->transactionService->createForOrder(
                $order,
                $amountPaid,
                $tax,
                $discount,
                0,
                $currency,
                $paymentProvider,
                $transactionId,
                'paid',
                TransactionStatus::SUCCESS,
            );
        }

        $this->orderService->updateOrder($order, [
            'status' => OrderStatus::SUCCESS,
            'payment_provider_id' => $paymentProvider->id,
            'payment_provider_order_id' => $creemOrderObject['id'],
            'total_amount' => $creemOrderObject['sub_total'] ?? $amount,
            'total_amount_after_discount' => $creemOrderObject['sub_total'] - $discount,
            'total_discount_amount' => $discount,
            'currency_id' => $currency->id,
        ]);
    }

    private function handleSubscriptionEvent(string $eventType, array $object, PaymentProvider $paymentProvider): void
    {
        $metadata = $object['metadata'] ?? [];
        $subscriptionUuid = $metadata['subscription_uuid'] ?? null;
        $providerSubscriptionId = $object['id'] ?? null;
        $providerStatus = $object['status'] ?? null;

        if ($eventType === 'subscription.active' || $eventType === 'subscription.trialing') {
            $subscription = $this->findOrCreateSubscription($subscriptionUuid, $providerSubscriptionId, $object, $paymentProvider);

            if ($subscription === null) {
                return;
            }

            $subscriptionStatus = $this->mapCreemStatusToSubscriptionStatus($providerStatus);
            $endsAt = isset($object['current_period_end_date']) ? Carbon::parse($object['current_period_end_date'])->toDateTimeString() : null;
            $trialEndsAt = isset($object['trial_end']) ? Carbon::parse($object['trial_end'])->toDateTimeString() : null;

            $extraData = $subscription->extra_payment_provider_data ?? [];
            $customerId = $object['customer']['id'] ?? null;
            if ($customerId) {
                $extraData['customer_id'] = $customerId;
            }

            $this->subscriptionService->updateSubscription($subscription, [
                'type' => SubscriptionType::PAYMENT_PROVIDER_MANAGED,
                'status' => $subscriptionStatus,
                'ends_at' => $endsAt,
                'payment_provider_subscription_id' => $providerSubscriptionId,
                'payment_provider_status' => $providerStatus,
                'payment_provider_id' => $paymentProvider->id,
                'trial_ends_at' => $trialEndsAt,
                'extra_payment_provider_data' => $extraData,
            ]);
        } elseif ($eventType === 'subscription.scheduled_cancel') {
            $subscription = $this->findSubscription($subscriptionUuid, $providerSubscriptionId, $paymentProvider);

            $cancelledAt = isset($object['canceled_at']) ? Carbon::parse($object['canceled_at'])->toDateTimeString() : null;

            $this->subscriptionService->updateSubscription($subscription, [
                'payment_provider_status' => $providerStatus ?? 'scheduled_cancel',
                'is_canceled_at_end_of_cycle' => true,
                'cancelled_at' => $cancelledAt,
            ]);
        } elseif ($eventType === 'subscription.canceled' || $eventType === 'subscription.expired') {
            $subscription = $this->findSubscription($subscriptionUuid, $providerSubscriptionId, $paymentProvider);

            $subscriptionStatus = $this->mapCreemStatusToSubscriptionStatus($providerStatus);

            $this->subscriptionService->updateSubscription($subscription, [
                'status' => $subscriptionStatus,
                'payment_provider_status' => $providerStatus,
            ]);
        } elseif ($eventType === 'subscription.past_due') {
            $subscription = $this->findSubscription($subscriptionUuid, $providerSubscriptionId, $paymentProvider);

            $this->subscriptionService->updateSubscription($subscription, [
                'status' => SubscriptionStatus::PAST_DUE,
                'payment_provider_status' => $providerStatus,
            ]);

            $this->subscriptionService->handleInvoicePaymentFailed($subscription);
        } elseif ($eventType === 'subscription.paused') {
            $subscription = $this->findSubscription($subscriptionUuid, $providerSubscriptionId, $paymentProvider);

            $this->subscriptionService->updateSubscription($subscription, [
                'status' => SubscriptionStatus::PAUSED,
                'payment_provider_status' => $providerStatus,
            ]);
        } elseif ($eventType === 'subscription.paid') {
            $subscription = $this->findSubscription($subscriptionUuid, $providerSubscriptionId, $paymentProvider);

            $lastTransaction = $object['last_transaction'] ?? [];
            $amount = $lastTransaction['amount_paid'] ?? 0;
            $tax = $lastTransaction['tax_amount'] ?? 0;
            $discountAmount = $lastTransaction['discount_amount'] ?? 0;
            $currencyCode = strtoupper($lastTransaction['currency'] ?? $object['currency'] ?? 'USD');
            $currency = Currency::where('code', $currencyCode)->firstOrFail();
            $transactionId = $object['last_transaction_id'];

            $transaction = $this->transactionService->getTransactionByPaymentProviderTxId($transactionId);

            if ($transaction) {
                $this->transactionService->updateTransactionByPaymentProviderTxId(
                    $transactionId,
                    'paid',
                    TransactionStatus::SUCCESS,
                );
            } else {
                $this->transactionService->createForSubscription(
                    $subscription,
                    $amount,
                    $tax,
                    $discountAmount,
                    0,
                    $currency,
                    $paymentProvider,
                    $transactionId,
                    'paid',
                    TransactionStatus::SUCCESS,
                );
            }

            $endsAt = isset($object['current_period_end_date']) ? Carbon::parse($object['current_period_end_date'])->toDateTimeString() : null;

            if ($endsAt) {
                $this->subscriptionService->updateSubscription($subscription, [
                    'status' => SubscriptionStatus::ACTIVE,
                    'ends_at' => $endsAt,
                    'payment_provider_status' => 'active',
                ]);
            }
        } elseif ($eventType === 'subscription.update') {
            $subscription = $this->findSubscription($subscriptionUuid, $providerSubscriptionId, $paymentProvider);

            $endsAt = isset($object['current_period_end_date']) ? Carbon::parse($object['current_period_end_date'])->toDateTimeString() : null;
            $trialEndsAt = isset($object['trial_end']) ? Carbon::parse($object['trial_end'])->toDateTimeString() : null;
            $subscriptionStatus = $this->mapCreemStatusToSubscriptionStatus($providerStatus);

            $extraData = $subscription->extra_payment_provider_data ?? [];
            $customerId = $object['customer']['id'] ?? null;
            if ($customerId) {
                $extraData['customer_id'] = $customerId;
            }

            $updateData = [
                'status' => $subscriptionStatus,
                'payment_provider_status' => $providerStatus,
                'payment_provider_subscription_id' => $providerSubscriptionId,
                'payment_provider_id' => $paymentProvider->id,
                'extra_payment_provider_data' => $extraData,
            ];

            if ($endsAt) {
                $updateData['ends_at'] = $endsAt;
            }

            if ($trialEndsAt) {
                $updateData['trial_ends_at'] = $trialEndsAt;
            }

            $this->subscriptionService->updateSubscription($subscription, $updateData);
        }
    }

    private function handleRefund(array $object): void
    {
        $transactionData = $object['transaction'] ?? [];
        $transactionId = $transactionData['transaction'] ?? $transactionData['id'] ?? null;

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

    private function findOrCreateSubscription(?string $subscriptionUuid, ?string $providerSubscriptionId, array $object, PaymentProvider $paymentProvider)
    {
        if ($subscriptionUuid !== null) {
            return $this->subscriptionService->findByUuidOrFail($subscriptionUuid);
        }

        $existingSubscription = $this->subscriptionService->findByPaymentProviderId($paymentProvider, $providerSubscriptionId);

        if ($existingSubscription) {
            return $existingSubscription;
        }

        try {
            return $this->createSubscription($object, $paymentProvider, $providerSubscriptionId);
        } catch (SubscriptionCreationNotAllowedException) {
            Log::error('Subscription creation not allowed', [
                'object' => $object,
                'payment_provider_id' => $paymentProvider->id,
            ]);

            throw new Exception('Subscription creation not allowed because you have an active subscription');
        }
    }

    private function createSubscription(array $object, PaymentProvider $paymentProvider, string $providerSubscriptionId)
    {
        $customerEmail = $object['customer_email'] ?? null;

        if ($customerEmail === null) {
            Log::error('No customer email in Creem webhook for subscription creation');

            return null;
        }

        $user = User::where('email', $customerEmail)->first();

        if (! $user) {
            $user = User::create([
                'email' => $customerEmail,
                'name' => $object['customer_name'] ?? $customerEmail,
                'password' => bcrypt(Str::random(16)),
            ]);
        }

        $productId = $object['product_id'] ?? null;
        $plan = $this->planService->findByPaymentProviderProductId($paymentProvider, $productId);

        if (! $plan) {
            Log::error('Plan not found for Creem subscription', [
                'payment_provider_id' => $paymentProvider->id,
                'product_id' => $productId,
            ]);

            return null;
        }

        return $this->subscriptionService->create($plan->slug, $user->id, $paymentProvider, $providerSubscriptionId);
    }

    private function mapCreemStatusToSubscriptionStatus(?string $providerStatus): SubscriptionStatus
    {
        return match ($providerStatus) {
            'active', 'trialing' => SubscriptionStatus::ACTIVE,
            'past_due' => SubscriptionStatus::PAST_DUE,
            'paused' => SubscriptionStatus::PAUSED,
            'canceled', 'expired' => SubscriptionStatus::INACTIVE,
            'scheduled_cancel' => SubscriptionStatus::ACTIVE,
            default => SubscriptionStatus::INACTIVE,
        };
    }

    private function isValidSignature(string $payload, ?string $signature): bool
    {
        if (empty($signature)) {
            return false;
        }

        $hash = hash_hmac('sha256', $payload, config('services.creem.webhook_secret'));

        return hash_equals($hash, $signature);
    }
}
