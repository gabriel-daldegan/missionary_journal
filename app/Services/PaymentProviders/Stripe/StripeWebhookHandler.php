<?php

namespace App\Services\PaymentProviders\Stripe;

use App\Constants\OrderStatus;
use App\Constants\PaymentProviderConstants;
use App\Constants\SubscriptionStatus;
use App\Constants\SubscriptionType;
use App\Constants\TransactionStatus;
use App\Models\Currency;
use App\Models\PaymentProvider;
use App\Models\Subscription;
use App\Models\UserStripeData;
use App\Services\OrderService;
use App\Services\SubscriptionService;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
use Throwable;

class StripeWebhookHandler
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private TransactionService $transactionService,
        private OrderService $orderService,
    ) {}

    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            $event = $this->buildStripeEvent($request);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Invalid payload',
            ], 400);
        }

        $paymentProvider = PaymentProvider::where('slug', PaymentProviderConstants::STRIPE_SLUG)->firstOrFail();

        // docs on events: https://stripe.com/docs/billing/testing?dashboard-or-api=api

        if ($event->type == 'customer.subscription.created' ||
            $event->type == 'customer.subscription.updated' ||
            $event->type == 'customer.subscription.resumed' ||
            $event->type == 'customer.subscription.deleted' ||
            $event->type == 'customer.subscription.paused'
        ) {
            $subscriptionUuid = $event->data->object->metadata->subscription_uuid;

            DB::transaction(function () use ($subscriptionUuid, $event, $paymentProvider) {
                // subscription events can arrive at same time, so we need to make sure we lock the subscription to maintain sequential processing
                $subscription = Subscription::query()->where('uuid', $subscriptionUuid)->lockForUpdate()->firstOrFail();

                if ($this->isSuperfluousEvent($subscription, $event->type)) {
                    return;
                }

                $stripeSubscriptionStatus = $event->data->object->status;
                $subscriptionStatus = $this->mapStripeSubscriptionStatusToSubscriptionStatus($stripeSubscriptionStatus);
                $endsAt = $this->getSubscriptionEndsAt($event);
                $endsAt = Carbon::createFromTimestampUTC($endsAt)->toDateTimeString();
                $trialEndsAt = $event->data->object->trial_end ? Carbon::createFromTimestampUTC($event->data->object->trial_end)->toDateTimeString() : null;
                $cancelledAt = $event->data->object->canceled_at ? Carbon::createFromTimestampUTC($event->data->object->canceled_at)->toDateTimeString() : null;

                $this->subscriptionService->updateSubscription($subscription, [
                    'type' => SubscriptionType::PAYMENT_PROVIDER_MANAGED,
                    'status' => $subscriptionStatus,
                    'ends_at' => $endsAt,
                    'payment_provider_subscription_id' => $event->data->object->id,
                    'payment_provider_status' => $event->data->object->status,
                    'payment_provider_id' => $paymentProvider->id,
                    'trial_ends_at' => $trialEndsAt,
                    'cancelled_at' => $cancelledAt,
                    'quantity' => $event->data->object->quantity ?? 1,
                    'is_canceled_at_end_of_cycle' => $event->data->object->cancel_at_period_end ?? false,
                    'cancellation_reason' => $event->data->object->cancellation_details?->feedback ?? $subscription->cancellation_reason,
                ]);
            });

        } elseif ($event->type == 'customer.subscription.trial_will_end') {
            // TODO send email to user

        } elseif ($event->type == 'invoice.created') {
            $subscriptionUuid = $this->getSubscriptionUuidFromInvoiceEvent($event);
            $subscription = $this->subscriptionService->findByUuidOrFail($subscriptionUuid);
            $currency = Currency::where('code', strtoupper($event->data->object->currency))->firstOrFail();
            $invoiceStatus = $event->data->object->status;

            $discount = $this->sumDiscountAmounts($event->data->object->total_discount_amounts ?? []);
            $tax = $this->sumTaxAmounts($event->data->object->total_tax_amounts ?? []);

            // create transaction

            $this->transactionService->createForSubscription(
                $subscription,
                $event->data->object->amount_due,
                $tax,
                $discount,
                0,  // calculated when invoice is paid
                $currency,
                $paymentProvider,
                $event->data->object->id,
                $invoiceStatus,
                $this->mapInvoiceStatusToTransactionStatus($invoiceStatus),
            );
        } elseif ($event->type == 'invoice.finalized' ||
                    $event->type == 'invoice.paid' ||
                    $event->type == 'invoice.updated'
        ) {
            $invoiceStatus = $event->data->object->status;
            $paymentIntent = $event->data->object->payment_intent;
            $fees = $this->calculateFees($paymentIntent);
            // update transaction

            $this->transactionService->updateTransactionByPaymentProviderTxId(
                $event->data->object->id,
                $invoiceStatus,
                $this->mapInvoiceStatusToTransactionStatus($invoiceStatus),
                null,
                null,
                $fees,
            );
        } elseif ($event->type == 'invoice.finalization_failed' ||
            $event->type == 'invoice.payment_failed' ||
            $event->type == 'invoice.payment_action_required'
        ) {
            $invoiceStatus = $event->data->object->status;
            // update transaction

            $errorReason = $event->data->object->last_payment_error->message ?? null;

            $this->transactionService->updateTransactionByPaymentProviderTxId(
                $event->data->object->id,
                $invoiceStatus,
                $this->mapInvoiceStatusToTransactionStatus($invoiceStatus),
                $errorReason,
            );

            $subscriptionUuid = $event->data->object->subscription_details->metadata->subscription_uuid;
            $subscription = $this->subscriptionService->findByUuidOrFail($subscriptionUuid);

            $this->subscriptionService->handleInvoicePaymentFailed($subscription);

        } elseif ($event->type == 'customer.updated') {
            $defaultPaymentMethodId = $event->data->object->invoice_settings->default_payment_method;
            $stripeCustomerId = $event->data->object->id;

            UserStripeData::where('stripe_customer_id', $stripeCustomerId)->update([
                'stripe_payment_method_id' => $defaultPaymentMethodId,
            ]);

        } elseif ($event->type == 'payment_intent.succeeded' || $event->type == 'payment_intent.payment_failed') { // order event
            $paymentIntentId = $event->data->object->id;
            $orderUuid = $event->data->object->metadata?->order_uuid;

            if (! empty($orderUuid)) {
                $order = $this->orderService->findByUuidOrFail($orderUuid);
                $fees = $this->calculateFees($paymentIntentId);
                $currency = Currency::where('code', strtoupper($event->data->object->currency))->firstOrFail();

                $transaction = $this->transactionService->getTransactionByPaymentProviderTxId($paymentIntentId);

                $transactionStatus = $event->type == 'payment_intent.succeeded' ? TransactionStatus::SUCCESS : TransactionStatus::FAILED;

                DB::transaction(function () use ($order, $event, $transaction, $transactionStatus, $fees, $currency, $paymentProvider, $paymentIntentId) {
                    if ($transaction) {
                        $this->transactionService->updateTransaction(
                            $transaction,
                            $event->data->object->status,
                            $transactionStatus,
                            null,
                            $event->data->object->amount,
                            $fees,
                        );
                    } else {
                        $this->transactionService->createForOrder(
                            $order,
                            $event->data->object->amount,
                            0,
                            $order->total_discount_amount,
                            $fees,
                            $currency,
                            $paymentProvider,
                            $paymentIntentId,
                            $event->data->object->status,
                            $transactionStatus,
                        );
                    }

                    $orderStatus = $event->type == 'payment_intent.succeeded' ? OrderStatus::SUCCESS : OrderStatus::FAILED;

                    $this->orderService->updateOrder($order, [
                        'status' => $orderStatus->value,
                        'total_amount_after_discount' => $event->data->object->amount,
                        'payment_provider_id' => $paymentProvider->id,
                    ]);
                });
            }
        } elseif ($event->type == 'charge.refunded') { // order event
            $paymentIntentId = $event->data->object->payment_intent;

            $orderUuid = $event->data->object->metadata?->order_uuid;

            if (! empty($orderUuid)) {

                $transaction = $this->transactionService->getTransactionByPaymentProviderTxId($paymentIntentId);

                if ($transaction) {
                    $this->transactionService->updateTransaction(
                        $transaction,
                        'refunded',
                        TransactionStatus::REFUNDED,
                    );

                    if ($transaction->order) {
                        $this->orderService->updateOrder($transaction->order, [
                            'status' => OrderStatus::REFUNDED,
                        ]);
                    }
                }
            }
        } elseif (str_starts_with($event->type, 'charge.dispute.')) { // order event
            $paymentIntentId = $event->data->object->payment_intent;

            $transaction = $this->transactionService->getTransactionByPaymentProviderTxId($paymentIntentId);

            if ($transaction) {
                $this->transactionService->updateTransaction(
                    $transaction,
                    $event->data->object->status,
                    TransactionStatus::DISPUTED,
                );

                if ($transaction->order) {
                    $this->orderService->updateOrder($transaction->order, [
                        'status' => OrderStatus::DISPUTED,
                    ]);
                }
            }
        }

        return response()->json();
    }

    private function mapInvoiceStatusToTransactionStatus(string $invoiceStatus): TransactionStatus
    {
        if ($invoiceStatus == 'paid') {
            return TransactionStatus::SUCCESS;
        }

        if ($invoiceStatus == 'void') {
            return TransactionStatus::FAILED;
        }

        if ($invoiceStatus == 'pending') {
            return TransactionStatus::PENDING;
        }

        if ($invoiceStatus == 'open') {
            return TransactionStatus::NOT_STARTED;
        }

        return TransactionStatus::NOT_STARTED;
    }

    private function mapStripeSubscriptionStatusToSubscriptionStatus(string $stripeSubscriptionStatus)
    {
        if ($stripeSubscriptionStatus == 'active' || $stripeSubscriptionStatus == 'trialing') {
            return SubscriptionStatus::ACTIVE;
        }

        if ($stripeSubscriptionStatus == 'past_due') {
            return SubscriptionStatus::PAST_DUE;
        }

        if ($stripeSubscriptionStatus == 'canceled') {
            return SubscriptionStatus::CANCELED;

        }

        if ($stripeSubscriptionStatus == 'paused') {
            return SubscriptionStatus::PAUSED;
        }

        return SubscriptionStatus::INACTIVE;

    }

    protected function buildStripeEvent(Request $request)
    {
        $this->setupClient();

        return Webhook::constructEvent(
            $request->getContent(),
            $request->header('Stripe-Signature'),
            config('services.stripe.webhook_signing_secret')
        );
    }

    private function setupClient()
    {
        Stripe::setApiKey(config('services.stripe.secret_key'));
    }

    private function sumDiscountAmounts(array $stripeDiscounts): int
    {
        $discount = 0;

        foreach ($stripeDiscounts as $stripeDiscount) {
            $discount += $stripeDiscount->amount;
        }

        return $discount;
    }

    private function sumTaxAmounts(array $stripeTaxes): int
    {
        $tax = 0;

        foreach ($stripeTaxes as $stripeTax) {
            $tax += $stripeTax->amount;
        }

        return $tax;
    }

    protected function calculateFees($paymentIntentId)
    {
        if (! $paymentIntentId) {
            return null;
        }

        $paymentIntent = PaymentIntent::retrieve([
            'id' => $paymentIntentId,
            'expand' => ['latest_charge.balance_transaction'],
        ]);

        return $paymentIntent?->latest_charge?->balance_transaction?->fee ?? 0;
    }

    private function isSuperfluousEvent(Subscription $subscription, string $eventType): bool
    {
        // Stripe events can arrive out of order, so the "updated" event can arrive before the "created" event, so we want to make
        // sure that if the subscription is already active, we need to skip the "created" event.

        if ($subscription->type === SubscriptionType::PAYMENT_PROVIDER_MANAGED &&
            $subscription->status == SubscriptionStatus::ACTIVE->value &&
            $eventType == 'customer.subscription.created'
        ) {
            return true;
        }

        return false;
    }

    private function getSubscriptionEndsAt(Event $subscriptionEvent): ?string
    {
        if ($subscriptionEvent->data->object->current_period_end !== null) {
            return $subscriptionEvent->data->object->current_period_end;
        }

        if ($subscriptionEvent->data->object->items !== null) { // change introduced in the Stripe API 2025-03-01.dashboard
            return $subscriptionEvent->data->object->items?->data[0]?->current_period_end;
        }

        return null;
    }

    private function getSubscriptionUuidFromInvoiceEvent(Event $invoiceEvent)
    {
        if ($invoiceEvent->data->object->subscription_details !== null) {
            return $invoiceEvent->data->object->subscription_details->metadata->subscription_uuid;
        }

        return $invoiceEvent->data->object->parent->subscription_details->metadata->subscription_uuid;
    }
}
