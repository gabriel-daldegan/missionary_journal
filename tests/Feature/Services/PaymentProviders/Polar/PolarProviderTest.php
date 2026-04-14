<?php

namespace Tests\Feature\Services\PaymentProviders\Polar;

use App\Client\PolarClient;
use App\Constants\DiscountConstants;
use App\Constants\PaymentProviderConstants;
use App\Models\Discount;
use App\Models\DiscountPaymentProviderData;
use App\Models\PaymentProvider;
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

        $result = $this->invokeFindOrCreate($provider, $discount, $this->paymentProvider, 'prod_abc');

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

        $result = $this->invokeFindOrCreate($provider, $discount, $this->paymentProvider, 'prod_xyz');

        $this->assertEquals('polar_new_percent_id', $result);
        $this->assertEquals('percentage', $capturedParams['type']);
        $this->assertEquals(1500, $capturedParams['basis_points']);
        $this->assertEquals('forever', $capturedParams['duration']);
        $this->assertEquals(['prod_xyz'], $capturedParams['products']);

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

        $result = $this->invokeFindOrCreate($provider, $discount, $this->paymentProvider, 'prod_xyz');

        $this->assertEquals('polar_new_fixed_id', $result);
        $this->assertEquals('fixed', $capturedParams['type']);
        $this->assertEquals(1000, $capturedParams['amount']);
        $this->assertEquals('usd', $capturedParams['currency']);
        $this->assertEquals('once', $capturedParams['duration']);
    }

    private function invokeFindOrCreate(PolarProvider $provider, Discount $discount, PaymentProvider $paymentProvider, string $productId): string
    {
        $method = new ReflectionMethod($provider, 'findOrCreatePolarDiscount');

        return $method->invoke($provider, $discount, $paymentProvider, $productId);
    }
}
