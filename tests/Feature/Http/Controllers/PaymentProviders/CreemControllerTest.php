<?php

namespace Tests\Feature\Http\Controllers\PaymentProviders;

use App\Constants\OrderStatus;
use App\Constants\SubscriptionStatus;
use App\Constants\SubscriptionType;
use App\Constants\TransactionStatus;
use App\Models\Currency;
use App\Models\Order;
use App\Models\PaymentProvider;
use App\Models\Subscription;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Feature\FeatureTest;

class CreemControllerTest extends FeatureTest
{
    private function getCreemPaymentProvider(): PaymentProvider
    {
        return PaymentProvider::where('slug', 'creem')->firstOrFail();
    }

    private function createTenantAndUser(): array
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        return [$tenant, $user];
    }

    private function signPayload(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), config('services.creem.webhook_secret'));
    }

    private function postCreemWebhook(array $payload): TestResponse
    {
        $signature = $this->signPayload($payload);

        return $this->postJson(route('payments-providers.creem.webhook'), $payload, [
            'creem-signature' => $signature,
            'Content-Type' => 'application/json',
        ]);
    }

    // -------------------------------------------------------
    // Signature validation
    // -------------------------------------------------------

    public function test_invalid_signature_returns_400(): void
    {
        $payload = ['eventType' => 'subscription.active', 'object' => []];

        $response = $this->postJson(route('payments-providers.creem.webhook'), $payload, [
            'creem-signature' => 'invalid-signature',
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
    }

    public function test_missing_event_type_returns_400(): void
    {
        $payload = ['object' => []];

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(400);
    }

    // -------------------------------------------------------
    // subscription.active
    // -------------------------------------------------------

    public function test_subscription_active_webhook(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $subscription = Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 3000,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::NEW->value,
        ]);

        $payload = $this->getSubscriptionActivePayload($uuid);

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_subscription_id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
            'payment_provider_status' => 'active',
            'type' => SubscriptionType::PAYMENT_PROVIDER_MANAGED,
        ]);
    }

    // -------------------------------------------------------
    // subscription.paid
    // -------------------------------------------------------

    public function test_subscription_paid_webhook_creates_transaction(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $transactionId = 'tran_paid_create_'.Str::random(10);

        $subscription = Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 3000,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $payload = $this->getSubscriptionPaidPayload($uuid, $transactionId);

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'subscription_id' => $subscription->id,
            'amount' => 3000,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_status' => 'paid',
            'payment_provider_transaction_id' => $transactionId,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);
    }

    public function test_subscription_paid_webhook_updates_existing_transaction(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $transactionId = 'tran_paid_update_'.Str::random(10);
        $creemPaymentProvider = $this->getCreemPaymentProvider();
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $subscription = Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 3000,
            'currency_id' => $currency->id,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $transaction = $subscription->transactions()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $subscription->user_id,
            'tenant_id' => $tenant->id,
            'currency_id' => $currency->id,
            'amount' => 3000,
            'status' => TransactionStatus::NOT_STARTED->value,
            'subscription_id' => $subscription->id,
            'payment_provider_id' => $creemPaymentProvider->id,
            'payment_provider_status' => 'pending',
            'payment_provider_transaction_id' => $transactionId,
        ]);

        $payload = $this->getSubscriptionPaidPayload($uuid, $transactionId);

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_status' => 'paid',
        ]);
    }

    // -------------------------------------------------------
    // subscription.update
    // -------------------------------------------------------

    public function test_subscription_update_webhook(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $creemPaymentProvider = $this->getCreemPaymentProvider();

        Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 3000,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_id' => $creemPaymentProvider->id,
            'payment_provider_subscription_id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
        ]);

        $payload = $this->getSubscriptionUpdatePayload($uuid);

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_status' => 'active',
            'payment_provider_subscription_id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
        ]);
    }

    // -------------------------------------------------------
    // subscription.scheduled_cancel
    // -------------------------------------------------------

    public function test_subscription_scheduled_cancel_webhook(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $creemPaymentProvider = $this->getCreemPaymentProvider();

        Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 3000,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_id' => $creemPaymentProvider->id,
            'payment_provider_subscription_id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
        ]);

        $payload = $this->getSubscriptionScheduledCancelPayload($uuid);

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'is_canceled_at_end_of_cycle' => true,
            'payment_provider_status' => 'scheduled_cancel',
        ]);
    }

    // -------------------------------------------------------
    // subscription.canceled
    // -------------------------------------------------------

    public function test_subscription_canceled_webhook(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $creemPaymentProvider = $this->getCreemPaymentProvider();

        Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 3000,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_id' => $creemPaymentProvider->id,
            'payment_provider_subscription_id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
        ]);

        $payload = $this->getSubscriptionCanceledPayload($uuid);

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'status' => SubscriptionStatus::INACTIVE->value,
            'payment_provider_status' => 'canceled',
        ]);
    }

    // -------------------------------------------------------
    // subscription.past_due
    // -------------------------------------------------------

    public function test_subscription_past_due_webhook(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $creemPaymentProvider = $this->getCreemPaymentProvider();

        Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 3000,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_id' => $creemPaymentProvider->id,
            'payment_provider_subscription_id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
        ]);

        $payload = $this->getSubscriptionPastDuePayload($uuid);

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'status' => SubscriptionStatus::PAST_DUE->value,
            'payment_provider_status' => 'past_due',
        ]);
    }

    // -------------------------------------------------------
    // subscription.paused
    // -------------------------------------------------------

    public function test_subscription_paused_webhook(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $creemPaymentProvider = $this->getCreemPaymentProvider();

        Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 3000,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_id' => $creemPaymentProvider->id,
            'payment_provider_subscription_id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
        ]);

        $payload = $this->getSubscriptionPausedPayload($uuid);

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'status' => SubscriptionStatus::PAUSED->value,
            'payment_provider_status' => 'paused',
        ]);
    }

    // -------------------------------------------------------
    // checkout.completed
    // -------------------------------------------------------

    public function test_checkout_completed_webhook_creates_transaction_and_updates_order(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();
        $currency = Currency::where('code', 'USD')->firstOrFail();
        $creemPaymentProvider = $this->getCreemPaymentProvider();
        $transactionId = 'tran_checkout_'.Str::random(10);

        $orderUuid = (string) Str::uuid();
        $order = Order::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'uuid' => $orderUuid,
            'status' => OrderStatus::NEW,
            'currency_id' => $currency->id,
            'total_amount' => 3000,
        ]);

        $payload = $this->getCheckoutCompletedPayload($orderUuid, $transactionId);

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'amount' => 3000,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_status' => 'paid',
            'payment_provider_transaction_id' => $transactionId,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::SUCCESS->value,
            'payment_provider_id' => $creemPaymentProvider->id,
        ]);
    }

    // -------------------------------------------------------
    // refund.created
    // -------------------------------------------------------

    public function test_refund_created_webhook(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();
        $currency = Currency::where('code', 'USD')->firstOrFail();
        $creemPaymentProvider = $this->getCreemPaymentProvider();
        $transactionId = 'tran_refund_'.Str::random(10);

        $orderUuid = (string) Str::uuid();
        $order = Order::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'uuid' => $orderUuid,
            'status' => OrderStatus::SUCCESS,
            'currency_id' => $currency->id,
            'total_amount' => 3000,
        ]);

        $transaction = $order->transactions()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'currency_id' => $currency->id,
            'amount' => 1530,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_id' => $creemPaymentProvider->id,
            'payment_provider_status' => 'paid',
            'payment_provider_transaction_id' => $transactionId,
        ]);

        $payload = $this->getRefundCreatedPayload($transactionId);

        $response = $this->postCreemWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => TransactionStatus::REFUNDED->value,
            'payment_provider_status' => 'refunded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::REFUNDED->value,
        ]);
    }

    // -------------------------------------------------------
    // Payload helpers
    // -------------------------------------------------------

    private function getSubscriptionActivePayload(string $subscriptionUuid): array
    {
        return [
            'eventType' => 'subscription.active',
            'object' => [
                'id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
                'object' => 'subscription',
                'product' => [
                    'id' => 'prod_1TO0YYMQUsGCZHKmJbVfQX',
                    'object' => 'product',
                    'name' => 'Pro Monthly No Trial',
                    'price' => 3000,
                    'currency' => 'USD',
                    'billing_type' => 'recurring',
                    'billing_period' => 'every-month',
                    'status' => 'active',
                ],
                'customer' => [
                    'id' => 'cust_3OaBXmGvPbXGnpEgT9DVfa',
                    'object' => 'customer',
                    'email' => 'admin@admin.com',
                    'name' => 'sdfsfsdf',
                    'country' => 'AM',
                ],
                'items' => [
                    [
                        'object' => 'subscription_item',
                        'id' => 'sitem_1J7OlCw8Xs6XSka1jy8Mn4',
                        'product_id' => 'prod_1TO0YYMQUsGCZHKmJbVfQX',
                        'price_id' => 'pprice_6cHNlqQ2zSVZWyniHbYm6L',
                        'units' => 1,
                    ],
                ],
                'collection_method' => 'charge_automatically',
                'status' => 'active',
                'current_period_start_date' => '2026-04-02T14:34:09.000Z',
                'current_period_end_date' => '2026-05-02T14:34:09.000Z',
                'canceled_at' => null,
                'metadata' => [
                    'subscription_uuid' => $subscriptionUuid,
                ],
                'mode' => 'test',
            ],
            'id' => 'evt_4tT0vutMdFLAyo35pnSj27',
            'created_at' => 1775140457340,
        ];
    }

    private function getSubscriptionPaidPayload(string $subscriptionUuid, string $transactionId): array
    {
        return [
            'eventType' => 'subscription.paid',
            'object' => [
                'id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
                'object' => 'subscription',
                'product' => [
                    'id' => 'prod_1TO0YYMQUsGCZHKmJbVfQX',
                    'object' => 'product',
                    'name' => 'Pro Monthly No Trial',
                    'price' => 3000,
                    'currency' => 'USD',
                ],
                'customer' => [
                    'id' => 'cust_3OaBXmGvPbXGnpEgT9DVfa',
                    'email' => 'admin@admin.com',
                ],
                'status' => 'active',
                'last_transaction_id' => $transactionId,
                'last_transaction' => [
                    'id' => $transactionId,
                    'object' => 'transaction',
                    'amount' => 3000,
                    'amount_paid' => 3000,
                    'currency' => 'USD',
                    'type' => 'invoice',
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'status' => 'paid',
                    'order' => 'ord_qxqoHR9qCpBZzhYPfDGXn',
                    'subscription' => 'sub_9so0T0vg2CvNpAPkk3ErU',
                ],
                'current_period_start_date' => '2026-04-02T14:34:09.000Z',
                'current_period_end_date' => '2026-05-02T14:34:09.000Z',
                'metadata' => [
                    'subscription_uuid' => $subscriptionUuid,
                ],
                'mode' => 'test',
            ],
            'id' => 'evt_4ErrPdvfTIaI3Rs8EAOro8',
            'created_at' => 1775140459604,
        ];
    }

    private function getSubscriptionUpdatePayload(string $subscriptionUuid): array
    {
        return [
            'eventType' => 'subscription.update',
            'object' => [
                'id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
                'object' => 'subscription',
                'product' => [
                    'id' => 'prod_5A3fIRs1TZDpT7dMvNTvie',
                    'name' => 'Ultimate Monthly',
                    'price' => 5000,
                    'currency' => 'USD',
                ],
                'customer' => [
                    'id' => 'cust_3OaBXmGvPbXGnpEgT9DVfa',
                    'email' => 'admin@admin.com',
                ],
                'status' => 'active',
                'current_period_start_date' => '2026-04-02T14:34:09.000Z',
                'current_period_end_date' => '2026-05-02T14:34:09.000Z',
                'metadata' => [
                    'subscription_uuid' => $subscriptionUuid,
                ],
                'mode' => 'test',
            ],
            'id' => 'evt_2qEH9dGHlpDxHrVoVoVRVa',
            'created_at' => 1775140516326,
        ];
    }

    private function getSubscriptionScheduledCancelPayload(string $subscriptionUuid): array
    {
        return [
            'eventType' => 'subscription.scheduled_cancel',
            'object' => [
                'id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
                'object' => 'subscription',
                'status' => 'scheduled_cancel',
                'canceled_at' => '2026-05-02T14:34:09.000Z',
                'metadata' => [
                    'subscription_uuid' => $subscriptionUuid,
                ],
                'mode' => 'test',
            ],
            'id' => 'evt_scheduledcancel1',
            'created_at' => 1775140516326,
        ];
    }

    private function getSubscriptionCanceledPayload(string $subscriptionUuid): array
    {
        return [
            'eventType' => 'subscription.canceled',
            'object' => [
                'id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
                'object' => 'subscription',
                'status' => 'canceled',
                'metadata' => [
                    'subscription_uuid' => $subscriptionUuid,
                ],
                'mode' => 'test',
            ],
            'id' => 'evt_canceled1',
            'created_at' => 1775140516326,
        ];
    }

    private function getSubscriptionPastDuePayload(string $subscriptionUuid): array
    {
        return [
            'eventType' => 'subscription.past_due',
            'object' => [
                'id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
                'object' => 'subscription',
                'status' => 'past_due',
                'metadata' => [
                    'subscription_uuid' => $subscriptionUuid,
                ],
                'mode' => 'test',
            ],
            'id' => 'evt_pastdue1',
            'created_at' => 1775140516326,
        ];
    }

    private function getSubscriptionPausedPayload(string $subscriptionUuid): array
    {
        return [
            'eventType' => 'subscription.paused',
            'object' => [
                'id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
                'object' => 'subscription',
                'status' => 'paused',
                'metadata' => [
                    'subscription_uuid' => $subscriptionUuid,
                ],
                'mode' => 'test',
            ],
            'id' => 'evt_paused1',
            'created_at' => 1775140516326,
        ];
    }

    private function getCheckoutCompletedPayload(string $orderUuid, string $transactionId): array
    {
        return [
            'eventType' => 'checkout.completed',
            'object' => [
                'id' => 'ch_7bbJMq083oZ8gSicemWhBO',
                'object' => 'checkout',
                'order' => [
                    'object' => 'order',
                    'id' => 'ord_qxqoHR9qCpBZzhYPfDGXn',
                    'customer' => 'cust_3OaBXmGvPbXGnpEgT9DVfa',
                    'product' => 'prod_1TO0YYMQUsGCZHKmJbVfQX',
                    'amount' => 3000,
                    'currency' => 'USD',
                    'sub_total' => 3000,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'amount_due' => 3000,
                    'amount_paid' => 3000,
                    'status' => 'paid',
                    'type' => 'recurring',
                    'transaction' => $transactionId,
                ],
                'product' => [
                    'id' => 'prod_1TO0YYMQUsGCZHKmJbVfQX',
                    'name' => 'Pro Monthly No Trial',
                    'price' => 3000,
                    'currency' => 'USD',
                ],
                'customer' => [
                    'id' => 'cust_3OaBXmGvPbXGnpEgT9DVfa',
                    'email' => 'admin@admin.com',
                ],
                'subscription' => [
                    'id' => 'sub_9so0T0vg2CvNpAPkk3ErU',
                    'status' => 'active',
                    'metadata' => [
                        'subscription_uuid' => (string) Str::uuid(),
                    ],
                ],
                'status' => 'completed',
                'metadata' => [
                    'order_uuid' => $orderUuid,
                ],
                'mode' => 'test',
            ],
            'id' => 'evt_3tN6OOFJd7qidusp9hWZTR',
            'created_at' => 1775140458453,
        ];
    }

    private function getRefundCreatedPayload(string $transactionId): array
    {
        return [
            'eventType' => 'refund.created',
            'object' => [
                'id' => 'ref_6TDG3hkzO1dzCbVVqOvj9j',
                'object' => 'refund',
                'status' => 'succeeded',
                'refund_amount' => 1530,
                'refund_currency' => 'USD',
                'reason' => 'requested_by_customer',
                'transaction' => [
                    'id' => $transactionId,
                    'object' => 'transaction',
                    'amount' => 3000,
                    'amount_paid' => 1530,
                    'currency' => 'USD',
                    'type' => 'invoice',
                    'tax_amount' => 0,
                    'discount_amount' => 1470,
                    'status' => 'refunded',
                    'refunded_amount' => 1530,
                    'order' => 'ord_4AzpGeGSTXXE6iOOOlqYhg',
                    'subscription' => 'sub_nyxTS4cNCkpIzATkQJdLo',
                    'customer' => 'cust_3OaBXmGvPbXGnpEgT9DVfa',
                ],
                'subscription' => [
                    'id' => 'sub_nyxTS4cNCkpIzATkQJdLo',
                    'status' => 'active',
                    'metadata' => [
                        'subscription_uuid' => '3cdb3380-e8c0-4919-9263-01c2ba6d770e',
                    ],
                ],
                'order' => [
                    'id' => 'ord_4AzpGeGSTXXE6iOOOlqYhg',
                    'amount' => 3000,
                    'currency' => 'USD',
                    'status' => 'paid',
                ],
                'customer' => [
                    'id' => 'cust_3OaBXmGvPbXGnpEgT9DVfa',
                    'email' => 'admin@admin.com',
                ],
                'created_at' => 1775078028148,
                'mode' => 'test',
            ],
            'id' => 'evt_3TE6MO1pPLxM483Pe4iMiw',
            'created_at' => 1775078028289,
        ];
    }
}
