<?php

namespace Tests\Feature\Services\PaymentProviders\Creem;

use App\Client\CreemClient;
use App\Models\Currency;
use App\Models\OneTimeProduct;
use App\Models\OneTimeProductPrice;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Services\CalculationService;
use App\Services\PaymentProviders\Creem\CreemProductValidator;
use Exception;
use Illuminate\Http\Client\Response;
use Mockery;
use Tests\Feature\FeatureTest;

class CreemProductValidatorTest extends FeatureTest
{
    private function mockCreemClient(array $productData): CreemClient
    {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturn(true);
        $response->shouldReceive('json')->andReturn($productData);

        $client = Mockery::mock(CreemClient::class);
        $client->shouldReceive('getProduct')->andReturn($response);

        return $client;
    }

    private function mockFailedCreemClient(): CreemClient
    {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturn(false);

        $client = Mockery::mock(CreemClient::class);
        $client->shouldReceive('getProduct')->andReturn($response);

        return $client;
    }

    // -------------------------------------------------------
    // Plan validation
    // -------------------------------------------------------

    public function test_validate_plan_succeeds_with_matching_product(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $plan = Plan::factory()->create();
        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 3000,
        ]);

        $client = $this->mockCreemClient([
            'price' => 3000,
            'currency' => 'USD',
            'billing_type' => 'recurring',
            'billing_period' => 'every-month',
        ]);

        $validator = new CreemProductValidator($client, resolve(CalculationService::class));

        $result = $validator->validatePlan('prod_123', $plan);

        $this->assertTrue($result);
    }

    public function test_validate_plan_fails_with_price_mismatch(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $plan = Plan::factory()->create();
        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 3000,
        ]);

        $client = $this->mockCreemClient([
            'price' => 5000,
            'currency' => 'USD',
            'billing_type' => 'recurring',
            'billing_period' => 'every-month',
        ]);

        $validator = new CreemProductValidator($client, resolve(CalculationService::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Price mismatch');

        $validator->validatePlan('prod_123', $plan);
    }

    public function test_validate_plan_fails_when_not_recurring(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $plan = Plan::factory()->create();
        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 3000,
        ]);

        $client = $this->mockCreemClient([
            'price' => 3000,
            'currency' => 'USD',
            'billing_type' => 'one_time',
            'billing_period' => null,
        ]);

        $validator = new CreemProductValidator($client, resolve(CalculationService::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not a recurring subscription');

        $validator->validatePlan('prod_123', $plan);
    }

    public function test_validate_plan_fails_with_billing_period_mismatch(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $plan = Plan::factory()->create(); // defaults to monthly
        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'currency_id' => $currency->id,
            'price' => 3000,
        ]);

        $client = $this->mockCreemClient([
            'price' => 3000,
            'currency' => 'USD',
            'billing_type' => 'recurring',
            'billing_period' => 'every-year',
        ]);

        $validator = new CreemProductValidator($client, resolve(CalculationService::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Billing period mismatch');

        $validator->validatePlan('prod_123', $plan);
    }

    public function test_validate_plan_fails_when_api_call_fails(): void
    {
        $plan = Plan::factory()->create();

        $client = $this->mockFailedCreemClient();

        $validator = new CreemProductValidator($client, resolve(CalculationService::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Failed to fetch product from Creem');

        $validator->validatePlan('prod_123', $plan);
    }

    // -------------------------------------------------------
    // One-time product validation
    // -------------------------------------------------------

    public function test_validate_one_time_product_succeeds_with_matching_product(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $product = OneTimeProduct::factory()->create();
        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => $currency->id,
            'price' => 2500,
        ]);

        $client = $this->mockCreemClient([
            'price' => 2500,
            'currency' => 'USD',
            'billing_type' => 'one_time',
        ]);

        $validator = new CreemProductValidator($client, resolve(CalculationService::class));

        $result = $validator->validateOneTimeProduct('prod_456', $product);

        $this->assertTrue($result);
    }

    public function test_validate_one_time_product_fails_with_price_mismatch(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $product = OneTimeProduct::factory()->create();
        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => $currency->id,
            'price' => 2500,
        ]);

        $client = $this->mockCreemClient([
            'price' => 3000,
            'currency' => 'USD',
            'billing_type' => 'one_time',
        ]);

        $validator = new CreemProductValidator($client, resolve(CalculationService::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Price mismatch');

        $validator->validateOneTimeProduct('prod_456', $product);
    }

    public function test_validate_one_time_product_fails_when_recurring(): void
    {
        $currency = Currency::where('code', 'USD')->firstOrFail();

        $product = OneTimeProduct::factory()->create();
        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => $currency->id,
            'price' => 2500,
        ]);

        $client = $this->mockCreemClient([
            'price' => 2500,
            'currency' => 'USD',
            'billing_type' => 'recurring',
        ]);

        $validator = new CreemProductValidator($client, resolve(CalculationService::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('recurring subscription, not a one-time product');

        $validator->validateOneTimeProduct('prod_456', $product);
    }
}
