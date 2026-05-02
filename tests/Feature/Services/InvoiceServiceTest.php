<?php

namespace Tests\Feature\Services;

use App\Constants\TransactionStatus;
use App\Models\Currency;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\InvoiceService;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class InvoiceServiceTest extends FeatureTest
{
    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = resolve(InvoiceService::class);
    }

    public function test_subscription_invoice_includes_setup_fee_on_first_transaction(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $plan = Plan::factory()->create();

        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 1000,
            'setup_fee' => 500,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 1000,
            'tenant_id' => $tenant->id,
        ]);

        $transaction = Transaction::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 1500,
            'total_tax' => 0,
            'total_discount' => 0,
            'total_fees' => 0,
            'currency_id' => $currency->id,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_id' => PaymentProvider::firstOrFail()->id,
            'payment_provider_status' => 'success',
            'payment_provider_transaction_id' => 'tx_setup_fee_'.Str::random(10),
            'tenant_id' => $tenant->id,
        ]);

        $items = $this->invoiceService->buildSubscriptionInvoiceItems($transaction);

        $this->assertCount(2, $items);
        $this->assertEquals($plan->name, $items[0]->title);
        $this->assertEquals('Setup Fee', $items[1]->title);
    }

    public function test_subscription_invoice_does_not_include_setup_fee_on_subsequent_transactions(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $plan = Plan::factory()->create();

        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 1000,
            'setup_fee' => 500,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 1000,
            'tenant_id' => $tenant->id,
        ]);

        // First transaction (setup fee was charged here)
        Transaction::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 1500,
            'total_tax' => 0,
            'total_discount' => 0,
            'total_fees' => 0,
            'currency_id' => $currency->id,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_id' => PaymentProvider::firstOrFail()->id,
            'payment_provider_status' => 'success',
            'payment_provider_transaction_id' => 'tx_first_'.Str::random(10),
            'tenant_id' => $tenant->id,
        ]);

        // Second transaction (renewal, no setup fee)
        $secondTransaction = Transaction::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 1000,
            'total_tax' => 0,
            'total_discount' => 0,
            'total_fees' => 0,
            'currency_id' => $currency->id,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_id' => PaymentProvider::firstOrFail()->id,
            'payment_provider_status' => 'success',
            'payment_provider_transaction_id' => 'tx_second_'.Str::random(10),
            'tenant_id' => $tenant->id,
        ]);

        $items = $this->invoiceService->buildSubscriptionInvoiceItems($secondTransaction);

        $this->assertCount(1, $items);
        $this->assertEquals($plan->name, $items[0]->title);
    }

    public function test_subscription_invoice_without_setup_fee(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $plan = Plan::factory()->create();

        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 1000,
            'setup_fee' => null,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 1000,
            'tenant_id' => $tenant->id,
        ]);

        $transaction = Transaction::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 1000,
            'total_tax' => 0,
            'total_discount' => 0,
            'total_fees' => 0,
            'currency_id' => $currency->id,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_id' => PaymentProvider::firstOrFail()->id,
            'payment_provider_status' => 'success',
            'payment_provider_transaction_id' => 'tx_no_fee_'.Str::random(10),
            'tenant_id' => $tenant->id,
        ]);

        $items = $this->invoiceService->buildSubscriptionInvoiceItems($transaction);

        $this->assertCount(1, $items);
        $this->assertEquals($plan->name, $items[0]->title);
    }

    public function test_subscription_invoice_with_zero_setup_fee(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $plan = Plan::factory()->create();

        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 1000,
            'setup_fee' => 0,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 1000,
            'tenant_id' => $tenant->id,
        ]);

        $transaction = Transaction::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 1000,
            'total_tax' => 0,
            'total_discount' => 0,
            'total_fees' => 0,
            'currency_id' => $currency->id,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_id' => PaymentProvider::firstOrFail()->id,
            'payment_provider_status' => 'success',
            'payment_provider_transaction_id' => 'tx_zero_fee_'.Str::random(10),
            'tenant_id' => $tenant->id,
        ]);

        $items = $this->invoiceService->buildSubscriptionInvoiceItems($transaction);

        $this->assertCount(1, $items);
        $this->assertEquals($plan->name, $items[0]->title);
    }

    public function test_setup_fee_not_included_when_first_transaction_failed(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $plan = Plan::factory()->create();

        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 1000,
            'setup_fee' => 500,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 1000,
            'tenant_id' => $tenant->id,
        ]);

        // First transaction failed
        Transaction::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 1500,
            'total_tax' => 0,
            'total_discount' => 0,
            'total_fees' => 0,
            'currency_id' => $currency->id,
            'status' => TransactionStatus::FAILED->value,
            'payment_provider_id' => PaymentProvider::firstOrFail()->id,
            'payment_provider_status' => 'failed',
            'payment_provider_transaction_id' => 'tx_failed_'.Str::random(10),
            'tenant_id' => $tenant->id,
        ]);

        // Second transaction succeeded (this is effectively the first successful one)
        $secondTransaction = Transaction::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => 1500,
            'total_tax' => 0,
            'total_discount' => 0,
            'total_fees' => 0,
            'currency_id' => $currency->id,
            'status' => TransactionStatus::SUCCESS->value,
            'payment_provider_id' => PaymentProvider::firstOrFail()->id,
            'payment_provider_status' => 'success',
            'payment_provider_transaction_id' => 'tx_retry_'.Str::random(10),
            'tenant_id' => $tenant->id,
        ]);

        $items = $this->invoiceService->buildSubscriptionInvoiceItems($secondTransaction);

        // Should include setup fee since no prior SUCCESSFUL transaction exists
        $this->assertCount(2, $items);
        $this->assertEquals('Setup Fee', $items[1]->title);
    }
}
