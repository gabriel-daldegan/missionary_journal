<?php

namespace Tests\Feature\Services\PaymentProviders\Polar;

use App\Client\PolarClient;
use App\Constants\DiscountConstants;
use App\Constants\PaymentProviderConstants;
use App\Constants\PlanMeterConstants;
use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Models\Currency;
use App\Models\Discount;
use App\Models\DiscountPaymentProviderData;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\PlanMeter;
use App\Models\PlanMeterPaymentProviderData;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Services\PaymentProviders\PaymentService;
use App\Services\PaymentProviders\Polar\PolarProvider;
use Illuminate\Http\Client\Response;
use Mockery;
use ReflectionMethod;
use Tests\Feature\FeatureTest;

class PolarProviderTest extends FeatureTest
{
    private PaymentProvider $paymentProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentProvider = PaymentProvider::where('slug', PaymentProviderConstants::POLAR_SLUG)->firstOrFail();
    }

    public function test_find_or_create_polar_discount_returns_existing_id_when_already_persisted(): void
    {
        $discount = Discount::create([
            'name' => 'Existing Discount',
            'type' => DiscountConstants::TYPE_PERCENTAGE,
            'amount' => 10,
            'is_active' => true,
        ]);

        DiscountPaymentProviderData::create([
            'discount_id' => $discount->id,
            'payment_provider_id' => $this->paymentProvider->id,
            'payment_provider_discount_id' => 'polar_existing_id',
        ]);

        $client = Mockery::mock(PolarClient::class);
        $client->shouldNotReceive('createDiscount');

        $this->app->instance(PolarClient::class, $client);

        $provider = app(PolarProvider::class);

        $result = $this->invokeFindOrCreate($provider, $discount, $this->paymentProvider);

        $this->assertEquals('polar_existing_id', $result);
    }

    public function test_find_or_create_polar_discount_creates_percentage_discount_and_persists_id(): void
    {
        $discount = Discount::create([
            'name' => 'Percent Off',
            'type' => DiscountConstants::TYPE_PERCENTAGE,
            'amount' => 15,
            'is_active' => true,
            'is_recurring' => true,
        ]);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturn(true);
        $response->shouldReceive('json')->andReturn(['id' => 'polar_new_percent_id']);
        $response->shouldReceive('body')->andReturn('');

        $capturedParams = null;
        $client = Mockery::mock(PolarClient::class);
        $client->shouldReceive('createDiscount')
            ->once()
            ->with(Mockery::on(function ($params) use (&$capturedParams) {
                $capturedParams = $params;

                return true;
            }))
            ->andReturn($response);

        $this->app->instance(PolarClient::class, $client);

        $provider = app(PolarProvider::class);

        $result = $this->invokeFindOrCreate($provider, $discount, $this->paymentProvider);

        $this->assertEquals('polar_new_percent_id', $result);
        $this->assertEquals('percentage', $capturedParams['type']);
        $this->assertEquals(1500, $capturedParams['basis_points']);
        $this->assertEquals('forever', $capturedParams['duration']);

        $this->assertDatabaseHas('discount_payment_provider_data', [
            'discount_id' => $discount->id,
            'payment_provider_id' => $this->paymentProvider->id,
            'payment_provider_discount_id' => 'polar_new_percent_id',
        ]);
    }

    public function test_find_or_create_polar_discount_creates_fixed_discount_with_currency(): void
    {
        $discount = Discount::create([
            'name' => 'Ten Off',
            'type' => DiscountConstants::TYPE_FIXED,
            'amount' => 1000,
            'is_active' => true,
        ]);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturn(true);
        $response->shouldReceive('json')->andReturn(['id' => 'polar_new_fixed_id']);
        $response->shouldReceive('body')->andReturn('');

        $capturedParams = null;
        $client = Mockery::mock(PolarClient::class);
        $client->shouldReceive('createDiscount')
            ->once()
            ->with(Mockery::on(function ($params) use (&$capturedParams) {
                $capturedParams = $params;

                return true;
            }))
            ->andReturn($response);

        $this->app->instance(PolarClient::class, $client);

        $provider = app(PolarProvider::class);

        $result = $this->invokeFindOrCreate($provider, $discount, $this->paymentProvider);

        $this->assertEquals('polar_new_fixed_id', $result);
        $this->assertEquals('fixed', $capturedParams['type']);
        $this->assertEquals(1000, $capturedParams['amount']);
        $this->assertEquals('usd', $capturedParams['currency']);
        $this->assertEquals('once', $capturedParams['duration']);
    }

    public function test_report_usage_ingests_polar_event_with_customer_and_value(): void
    {
        $user = $this->createUser();
        $meter = PlanMeter::create(['name' => 'API Calls']);
        $plan = Plan::factory()->create([
            'type' => PlanType::USAGE_BASED->value,
            'meter_id' => $meter->id,
        ]);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'payment_provider_id' => $this->paymentProvider->id,
            'extra_payment_provider_data' => ['customer_id' => 'polar_cust_1'],
        ]);

        PlanMeterPaymentProviderData::create([
            'plan_meter_id' => $meter->id,
            'payment_provider_id' => $this->paymentProvider->id,
            'payment_provider_plan_meter_id' => 'polar_meter_1',
            'data' => [PlanMeterConstants::POLAR_METER_EVENT_NAME => 'api_calls_abcdef'],
        ]);

        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturn(true);
        $response->shouldReceive('body')->andReturn('');

        $capturedParams = null;
        $client = Mockery::mock(PolarClient::class);
        $client->shouldReceive('ingestEvents')
            ->once()
            ->with(Mockery::on(function ($params) use (&$capturedParams) {
                $capturedParams = $params;

                return true;
            }))
            ->andReturn($response);

        $this->app->instance(PolarClient::class, $client);

        $provider = app(PolarProvider::class);

        $this->assertTrue($provider->reportUsage($subscription, 42));
        $this->assertEquals('api_calls_abcdef', $capturedParams['events'][0]['name']);
        $this->assertEquals('polar_cust_1', $capturedParams['events'][0]['customer_id']);
        $this->assertEquals(42, $capturedParams['events'][0]['metadata']['value']);
    }

    public function test_report_usage_creates_polar_meter_on_demand_when_missing(): void
    {
        $user = $this->createUser();
        $meter = PlanMeter::create(['name' => 'API Calls']);
        $plan = Plan::factory()->create([
            'type' => PlanType::USAGE_BASED->value,
            'meter_id' => $meter->id,
        ]);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'payment_provider_id' => $this->paymentProvider->id,
            'extra_payment_provider_data' => ['customer_id' => 'polar_cust_2'],
        ]);

        $createResponse = Mockery::mock(Response::class);
        $createResponse->shouldReceive('successful')->andReturn(true);
        $createResponse->shouldReceive('json')->andReturn(['id' => 'polar_meter_new']);
        $createResponse->shouldReceive('body')->andReturn('');

        $ingestResponse = Mockery::mock(Response::class);
        $ingestResponse->shouldReceive('successful')->andReturn(true);
        $ingestResponse->shouldReceive('body')->andReturn('');

        $client = Mockery::mock(PolarClient::class);
        $client->shouldReceive('createMeter')->once()->andReturn($createResponse);
        $client->shouldReceive('ingestEvents')->once()->andReturn($ingestResponse);

        $this->app->instance(PolarClient::class, $client);

        $provider = app(PolarProvider::class);

        $this->assertTrue($provider->reportUsage($subscription, 7));
        $this->assertDatabaseHas('plan_meter_payment_provider_data', [
            'plan_meter_id' => $meter->id,
            'payment_provider_id' => $this->paymentProvider->id,
            'payment_provider_plan_meter_id' => 'polar_meter_new',
        ]);
    }

    public function test_report_usage_returns_false_when_customer_id_missing(): void
    {
        $user = $this->createUser();
        $meter = PlanMeter::create(['name' => 'API Calls']);
        $plan = Plan::factory()->create([
            'type' => PlanType::USAGE_BASED->value,
            'meter_id' => $meter->id,
        ]);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'payment_provider_id' => $this->paymentProvider->id,
        ]);

        PlanMeterPaymentProviderData::create([
            'plan_meter_id' => $meter->id,
            'payment_provider_id' => $this->paymentProvider->id,
            'payment_provider_plan_meter_id' => 'polar_meter_1',
            'data' => [PlanMeterConstants::POLAR_METER_EVENT_NAME => 'api_calls_abcdef'],
        ]);

        $client = Mockery::mock(PolarClient::class);
        $client->shouldNotReceive('ingestEvents');
        $this->app->instance(PolarClient::class, $client);

        $provider = app(PolarProvider::class);

        $this->assertFalse($provider->reportUsage($subscription, 10));
    }

    public function test_supports_plan_returns_true_for_flat_rate_plan(): void
    {
        $plan = Plan::factory()->create(['type' => PlanType::FLAT_RATE->value]);

        $provider = app(PolarProvider::class);

        $this->assertTrue($provider->supportsPlan($plan));
    }

    public function test_supports_plan_returns_true_for_usage_based_per_unit_plan(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();
        $meter = PlanMeter::create(['name' => 'API Calls']);
        $plan = Plan::factory()->create([
            'type' => PlanType::USAGE_BASED->value,
            'meter_id' => $meter->id,
        ]);
        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'type' => PlanPriceType::USAGE_BASED_PER_UNIT->value,
            'price_per_unit' => 10,
        ]);

        $provider = app(PolarProvider::class);

        $this->assertTrue($provider->supportsPlan($plan));
    }

    public function test_supports_plan_returns_false_for_usage_based_tiered_volume_plan(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();
        $meter = PlanMeter::create(['name' => 'API Calls']);
        $plan = Plan::factory()->create([
            'type' => PlanType::USAGE_BASED->value,
            'meter_id' => $meter->id,
        ]);
        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'type' => PlanPriceType::USAGE_BASED_TIERED_VOLUME->value,
            'tiers' => [],
        ]);

        $provider = app(PolarProvider::class);

        $this->assertFalse($provider->supportsPlan($plan));
    }

    public function test_supports_plan_returns_false_for_usage_based_tiered_graduated_plan(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();
        $meter = PlanMeter::create(['name' => 'API Calls']);
        $plan = Plan::factory()->create([
            'type' => PlanType::USAGE_BASED->value,
            'meter_id' => $meter->id,
        ]);
        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'type' => PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value,
            'tiers' => [],
        ]);

        $provider = app(PolarProvider::class);

        $this->assertFalse($provider->supportsPlan($plan));
    }

    public function test_payment_service_excludes_polar_for_tiered_usage_based_plan(): void
    {
        $this->paymentProvider->update(['is_active' => true, 'is_enabled_for_new_payments' => true]);

        $currency = Currency::where('code', 'USD')->firstOrFail();
        $meter = PlanMeter::create(['name' => 'API Calls']);
        $plan = Plan::factory()->create([
            'type' => PlanType::USAGE_BASED->value,
            'meter_id' => $meter->id,
        ]);
        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'type' => PlanPriceType::USAGE_BASED_TIERED_VOLUME->value,
            'tiers' => [],
        ]);

        $providers = app(PaymentService::class)->getActivePaymentProvidersForPlan($plan);

        $slugs = array_map(fn ($p) => $p->getSlug(), $providers);
        $this->assertNotContains($this->paymentProvider->slug, $slugs);
    }

    private function invokeFindOrCreate(PolarProvider $provider, Discount $discount, PaymentProvider $paymentProvider): string
    {
        $method = new ReflectionMethod($provider, 'findOrCreatePolarDiscount');

        return $method->invoke($provider, $discount, $paymentProvider);
    }
}
