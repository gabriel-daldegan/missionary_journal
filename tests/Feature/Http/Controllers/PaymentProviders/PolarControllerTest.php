<?php

namespace Tests\Feature\Http\Controllers\PaymentProviders;

use App\Constants\OrderStatus;
use App\Constants\PaymentProviderConstants;
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

class PolarControllerTest extends FeatureTest
{
    private const SUBSCRIPTION_PROVIDER_ID = '73b8853a-106b-4191-b3dc-771a440f07d9';

    private const CUSTOMER_ID = '9d43f239-56d1-4825-9497-9fcc7b8259e6';

    private const PRODUCT_ID = '6b88d343-2e58-4db8-a72e-a1eec463c330';

    private const CURRENT_PERIOD_START = '2026-04-18T10:07:38.910686Z';

    private const CURRENT_PERIOD_END = '2026-04-26T10:07:35.196633Z';

    private function getPolarPaymentProvider(): PaymentProvider
    {
        return PaymentProvider::where('slug', PaymentProviderConstants::POLAR_SLUG)->firstOrFail();
    }

    private function createTenantAndUser(): array
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        return [$tenant, $user];
    }

    private function postPolarWebhook(array $payload, ?int $timestamp = null, ?string $secret = null): TestResponse
    {
        $secret = $secret ?? config('services.polar.webhook_secret');
        $timestamp = $timestamp ?? time();
        $webhookId = 'msg_'.Str::random(16);
        $payloadString = json_encode($payload);

        $signedContent = $webhookId.'.'.$timestamp.'.'.$payloadString;
        $signature = base64_encode(hash_hmac('sha256', $signedContent, $secret, true));

        return $this->postJson(route('payments-providers.polar.webhook'), $payload, [
            'webhook-id' => $webhookId,
            'webhook-timestamp' => (string) $timestamp,
            'webhook-signature' => 'v1,'.$signature,
            'Content-Type' => 'application/json',
        ]);
    }

    // -------------------------------------------------------
    // Signature validation
    // -------------------------------------------------------

    public function test_invalid_signature_returns_400(): void
    {
        $payload = $this->getSubscriptionCreatedPayload((string) Str::uuid());

        $response = $this->postJson(route('payments-providers.polar.webhook'), $payload, [
            'webhook-id' => 'msg_whatever',
            'webhook-timestamp' => (string) time(),
            'webhook-signature' => 'v1,invalid-signature',
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
    }

    public function test_missing_signature_headers_returns_400(): void
    {
        $payload = $this->getSubscriptionCreatedPayload((string) Str::uuid());

        $response = $this->postJson(route('payments-providers.polar.webhook'), $payload, [
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(400);
    }

    public function test_expired_timestamp_returns_400(): void
    {
        $payload = $this->getSubscriptionCreatedPayload((string) Str::uuid());

        // 10 minutes in the past (tolerance is 5 minutes)
        $response = $this->postPolarWebhook($payload, time() - 600);

        $response->assertStatus(400);
    }

    public function test_missing_event_type_returns_400(): void
    {
        $response = $this->postPolarWebhook(['data' => []]);

        $response->assertStatus(400);
    }

    // -------------------------------------------------------
    // subscription.created
    // -------------------------------------------------------

    public function test_subscription_created_webhook_updates_existing_subscription(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 4200,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::NEW->value,
        ]);

        $response = $this->postPolarWebhook($this->getSubscriptionCreatedPayload($uuid));

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_status' => 'trialing',
            'payment_provider_subscription_id' => self::SUBSCRIPTION_PROVIDER_ID,
            'type' => SubscriptionType::PAYMENT_PROVIDER_MANAGED,
        ]);
    }

    // -------------------------------------------------------
    // subscription.active
    // -------------------------------------------------------

    public function test_subscription_active_webhook(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 4200,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::NEW->value,
        ]);

        $payload = $this->getSubscriptionCreatedPayload($uuid);
        $payload['type'] = 'subscription.active';
        $payload['data']['status'] = 'active';

        $response = $this->postPolarWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_status' => 'active',
            'payment_provider_subscription_id' => self::SUBSCRIPTION_PROVIDER_ID,
            'type' => SubscriptionType::PAYMENT_PROVIDER_MANAGED,
        ]);
    }

    // -------------------------------------------------------
    // subscription.updated
    // -------------------------------------------------------

    public function test_subscription_updated_webhook(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $polarProvider = $this->getPolarPaymentProvider();

        Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 4200,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_id' => $polarProvider->id,
            'payment_provider_subscription_id' => self::SUBSCRIPTION_PROVIDER_ID,
        ]);

        $payload = $this->getSubscriptionCreatedPayload($uuid);
        $payload['type'] = 'subscription.updated';

        $response = $this->postPolarWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_status' => 'trialing',
            'is_canceled_at_end_of_cycle' => false,
        ]);
    }

    public function test_subscription_updated_with_cancel_at_period_end_marks_cancellation(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $polarProvider = $this->getPolarPaymentProvider();

        Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 4200,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_id' => $polarProvider->id,
            'payment_provider_subscription_id' => self::SUBSCRIPTION_PROVIDER_ID,
        ]);

        $payload = $this->getSubscriptionCreatedPayload($uuid);
        $payload['type'] = 'subscription.updated';
        $payload['data']['cancel_at_period_end'] = true;

        $response = $this->postPolarWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'is_canceled_at_end_of_cycle' => true,
        ]);

        $this->assertNotNull(Subscription::where('uuid', $uuid)->first()->cancelled_at);
    }

    // -------------------------------------------------------
    // subscription.revoked
    // -------------------------------------------------------

    public function test_subscription_revoked_webhook(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $polarProvider = $this->getPolarPaymentProvider();

        Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 4200,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_id' => $polarProvider->id,
            'payment_provider_subscription_id' => self::SUBSCRIPTION_PROVIDER_ID,
        ]);

        $payload = $this->getSubscriptionCreatedPayload($uuid);
        $payload['type'] = 'subscription.revoked';
        $payload['data']['status'] = 'revoked';

        $response = $this->postPolarWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'uuid' => $uuid,
            'status' => SubscriptionStatus::INACTIVE->value,
            'payment_provider_status' => 'revoked',
        ]);
    }

    // -------------------------------------------------------
    // order.paid (subscription order)
    // -------------------------------------------------------

    public function test_order_paid_webhook_creates_subscription_transaction(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $transactionId = 'polar_order_'.Str::random(10);

        $subscription = Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 4200,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $payload = $this->getOrderPaidPayload($uuid, $transactionId, totalAmount: 4200, platformFee: 150);

        $response = $this->postPolarWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'subscription_id' => $subscription->id,
            'amount' => 4200,
            'total_fees' => 150,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_status' => 'paid',
            'payment_provider_transaction_id' => $transactionId,
        ]);
    }

    public function test_order_paid_webhook_updates_existing_subscription_transaction(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $transactionId = 'polar_order_update_'.Str::random(10);
        $polarProvider = $this->getPolarPaymentProvider();
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $subscription = Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 4200,
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
            'amount' => 4200,
            'status' => TransactionStatus::NOT_STARTED->value,
            'subscription_id' => $subscription->id,
            'payment_provider_id' => $polarProvider->id,
            'payment_provider_status' => 'pending',
            'payment_provider_transaction_id' => $transactionId,
        ]);

        $payload = $this->getOrderPaidPayload($uuid, $transactionId);

        $response = $this->postPolarWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_status' => 'paid',
        ]);
    }

    public function test_order_paid_webhook_resolves_subscription_by_provider_id_when_uuid_missing(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $transactionId = 'polar_order_byid_'.Str::random(10);
        $providerSubscriptionId = 'sub_byid_'.Str::random(12);
        $polarProvider = $this->getPolarPaymentProvider();

        $subscription = Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 4200,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_id' => $polarProvider->id,
            'payment_provider_subscription_id' => $providerSubscriptionId,
        ]);

        $payload = $this->getOrderPaidPayload($uuid, $transactionId);
        $payload['data']['metadata'] = [];
        $payload['data']['subscription_id'] = $providerSubscriptionId;

        $response = $this->postPolarWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'subscription_id' => $subscription->id,
            'payment_provider_transaction_id' => $transactionId,
            'status' => TransactionStatus::SUCCESS->value,
        ]);
    }

    // -------------------------------------------------------
    // order.paid (one-time order)
    // -------------------------------------------------------

    public function test_order_paid_webhook_creates_transaction_for_one_time_order(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();
        $currency = Currency::where('code', 'USD')->firstOrFail();
        $polarProvider = $this->getPolarPaymentProvider();
        $transactionId = 'polar_onetime_'.Str::random(10);

        $orderUuid = (string) Str::uuid();
        $order = Order::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'uuid' => $orderUuid,
            'status' => OrderStatus::NEW,
            'currency_id' => $currency->id,
            'total_amount' => 10000,
        ]);

        $payload = $this->getOneTimeOrderPaidPayload($orderUuid, $transactionId);

        $response = $this->postPolarWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'amount' => 10000,
            'total_fees' => 350,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_status' => 'paid',
            'payment_provider_transaction_id' => $transactionId,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::SUCCESS->value,
            'payment_provider_id' => $polarProvider->id,
            'payment_provider_order_id' => $transactionId,
        ]);
    }

    // -------------------------------------------------------
    // order.updated
    // -------------------------------------------------------

    public function test_order_updated_webhook_creates_subscription_transaction(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();

        $uuid = (string) Str::uuid();
        $transactionId = 'polar_updated_'.Str::random(10);

        $subscription = Subscription::create([
            'uuid' => $uuid,
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'price' => 4200,
            'currency_id' => 1,
            'plan_id' => 1,
            'interval_id' => 2,
            'interval_count' => 1,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        $payload = $this->getOrderPaidPayload($uuid, $transactionId);
        $payload['type'] = 'order.updated';

        $response = $this->postPolarWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'subscription_id' => $subscription->id,
            'payment_provider_transaction_id' => $transactionId,
            'status' => TransactionStatus::SUCCESS->value,
        ]);
    }

    // -------------------------------------------------------
    // order.refunded
    // -------------------------------------------------------

    public function test_order_refunded_webhook_marks_transaction_and_order_refunded(): void
    {
        [$tenant, $user] = $this->createTenantAndUser();
        $currency = Currency::where('code', 'USD')->firstOrFail();
        $polarProvider = $this->getPolarPaymentProvider();
        $transactionId = 'polar_refund_'.Str::random(10);

        $orderUuid = (string) Str::uuid();
        $order = Order::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'uuid' => $orderUuid,
            'status' => OrderStatus::SUCCESS,
            'currency_id' => $currency->id,
            'total_amount' => 10000,
        ]);

        $transaction = $order->transactions()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'currency_id' => $currency->id,
            'amount' => 10000,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_id' => $polarProvider->id,
            'payment_provider_status' => 'paid',
            'payment_provider_transaction_id' => $transactionId,
        ]);

        $payload = [
            'type' => 'order.refunded',
            'data' => [
                'id' => $transactionId,
            ],
        ];

        $response = $this->postPolarWebhook($payload);

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

    public function test_unhandled_event_type_returns_200(): void
    {
        $payload = [
            'type' => 'customer.created',
            'data' => ['id' => 'cust_abc'],
        ];

        $response = $this->postPolarWebhook($payload);

        $response->assertStatus(200);
    }

    // -------------------------------------------------------
    // Payload helpers
    // -------------------------------------------------------

    private function getSubscriptionCreatedPayload(string $subscriptionUuid): array
    {
        return [
            'type' => 'subscription.created',
            'timestamp' => '2026-04-18T10:07:39.059127Z',
            'data' => [
                'id' => self::SUBSCRIPTION_PROVIDER_ID,
                'amount' => 4200,
                'currency' => 'usd',
                'recurring_interval' => 'month',
                'recurring_interval_count' => 1,
                'status' => 'trialing',
                'current_period_start' => self::CURRENT_PERIOD_START,
                'current_period_end' => self::CURRENT_PERIOD_END,
                'trial_start' => self::CURRENT_PERIOD_START,
                'trial_end' => self::CURRENT_PERIOD_END,
                'cancel_at_period_end' => false,
                'canceled_at' => null,
                'started_at' => self::CURRENT_PERIOD_START,
                'ends_at' => null,
                'ended_at' => null,
                'customer_id' => self::CUSTOMER_ID,
                'product_id' => self::PRODUCT_ID,
                'discount_id' => null,
                'checkout_id' => 'a1c8c513-6cf1-4fe0-a0a9-3eb571c9c588',
                'price_id' => '66edbfb5-a850-4851-b171-6ebe401f515b',
                'metadata' => [
                    'subscription_uuid' => $subscriptionUuid,
                ],
                'customer' => [
                    'id' => self::CUSTOMER_ID,
                    'email' => 'admin@admin.com',
                    'name' => '234234234',
                ],
            ],
        ];
    }

    private function getOrderPaidPayload(string $subscriptionUuid, string $transactionId, int $totalAmount = 4200, int $platformFee = 0): array
    {
        return [
            'type' => 'order.paid',
            'timestamp' => '2026-04-18T10:07:39.627154Z',
            'data' => [
                'id' => $transactionId,
                'status' => 'paid',
                'paid' => true,
                'subtotal_amount' => $totalAmount,
                'discount_amount' => 0,
                'net_amount' => $totalAmount,
                'tax_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => 'usd',
                'billing_reason' => 'subscription_cycle',
                'customer_id' => self::CUSTOMER_ID,
                'product_id' => self::PRODUCT_ID,
                'subscription_id' => self::SUBSCRIPTION_PROVIDER_ID,
                'platform_fee_amount' => $platformFee,
                'metadata' => [
                    'subscription_uuid' => $subscriptionUuid,
                ],
                'customer' => [
                    'id' => self::CUSTOMER_ID,
                    'email' => 'admin@admin.com',
                ],
            ],
        ];
    }

    private function getOneTimeOrderPaidPayload(string $orderUuid, string $transactionId): array
    {
        return [
            'type' => 'order.paid',
            'timestamp' => '2026-04-18T10:07:39.627154Z',
            'data' => [
                'id' => $transactionId,
                'status' => 'paid',
                'paid' => true,
                'subtotal_amount' => 10000,
                'discount_amount' => 0,
                'net_amount' => 10000,
                'tax_amount' => 500,
                'total_amount' => 10000,
                'currency' => 'usd',
                'billing_reason' => 'purchase',
                'customer_id' => self::CUSTOMER_ID,
                'product_id' => self::PRODUCT_ID,
                'subscription_id' => null,
                'platform_fee_amount' => 350,
                'metadata' => [
                    'order_uuid' => $orderUuid,
                ],
                'customer' => [
                    'id' => self::CUSTOMER_ID,
                    'email' => 'admin@admin.com',
                ],
            ],
        ];
    }
}
