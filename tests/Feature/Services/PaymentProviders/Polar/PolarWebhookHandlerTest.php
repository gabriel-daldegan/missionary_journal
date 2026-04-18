<?php

namespace Tests\Feature\Services\PaymentProviders\Polar;

use App\Constants\PaymentProviderConstants;
use App\Models\Currency;
use App\Models\Order;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\PaymentProviders\Polar\PolarWebhookHandler;
use Illuminate\Http\Request;
use Tests\Feature\FeatureTest;

class PolarWebhookHandlerTest extends FeatureTest
{
    private PolarWebhookHandler $handler;

    private PaymentProvider $paymentProvider;

    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = app(PolarWebhookHandler::class);
        $this->paymentProvider = PaymentProvider::where('slug', PaymentProviderConstants::POLAR_SLUG)->firstOrFail();
        $this->currency = Currency::where('code', 'USD')->firstOrFail();
    }

    public function test_one_time_order_paid_sets_total_fees_from_platform_fee_amount(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'currency_id' => $this->currency->id,
            'payment_provider_id' => $this->paymentProvider->id,
        ]);

        $data = [
            'type' => 'order.paid',
            'data' => [
                'id' => 'polar_tx_123',
                'metadata' => [
                    'order_uuid' => $order->uuid,
                ],
                'net_amount' => 9000,
                'total_amount' => 10000,
                'subtotal_amount' => 10000,
                'tax_amount' => 500,
                'discount_amount' => 0,
                'platform_fee_amount' => 350,
                'currency' => 'usd',
            ],
        ];

        $request = $this->createWebhookRequest($data);

        // Skip signature validation by calling handleEvent via reflection
        $this->invokeHandleEvent('order.paid', $data['data'], $this->paymentProvider);

        $transaction = Transaction::where('payment_provider_transaction_id', 'polar_tx_123')->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(350, $transaction->total_fees);
    }

    public function test_subscription_order_paid_sets_total_fees_from_platform_fee_amount(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $plan = Plan::first();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'payment_provider_id' => $this->paymentProvider->id,
        ]);

        $data = [
            'id' => 'polar_tx_456',
            'metadata' => [
                'subscription_uuid' => $subscription->uuid,
            ],
            'total_amount' => 2000,
            'tax_amount' => 200,
            'discount_amount' => 0,
            'platform_fee_amount' => 150,
            'currency' => 'usd',
        ];

        $this->invokeHandleEvent('order.paid', $data, $this->paymentProvider);

        $transaction = Transaction::where('payment_provider_transaction_id', 'polar_tx_456')->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(150, $transaction->total_fees);
    }

    public function test_order_paid_defaults_total_fees_to_zero_when_platform_fee_amount_missing(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'currency_id' => $this->currency->id,
            'payment_provider_id' => $this->paymentProvider->id,
        ]);

        $data = [
            'id' => 'polar_tx_789',
            'metadata' => [
                'order_uuid' => $order->uuid,
            ],
            'net_amount' => 9000,
            'total_amount' => 10000,
            'subtotal_amount' => 10000,
            'tax_amount' => 500,
            'discount_amount' => 0,
            'currency' => 'usd',
        ];

        $this->invokeHandleEvent('order.paid', $data, $this->paymentProvider);

        $transaction = Transaction::where('payment_provider_transaction_id', 'polar_tx_789')->first();

        $this->assertNotNull($transaction);
        $this->assertEquals(0, $transaction->total_fees);
    }

    private function invokeHandleEvent(string $eventType, array $data, PaymentProvider $paymentProvider): void
    {
        $reflection = new \ReflectionMethod($this->handler, 'handleEvent');
        $reflection->invoke($this->handler, $eventType, $data, $paymentProvider);
    }

    private function createWebhookRequest(array $data): Request
    {
        return new Request([], [], [], [], [], [], json_encode($data));
    }
}
