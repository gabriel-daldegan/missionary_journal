<?php

namespace Tests\Feature\Services;

use App\Constants\OrderStatus;
use App\Events\Order\Ordered;
use App\Models\Currency;
use App\Models\OneTimeProduct;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class OrderServiceTest extends FeatureTest
{
    public function test_find_all_tenant_successful_orders(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $tenant = $this->createTenant();

        $product1Slug = Str::random(10);
        $product1 = OneTimeProduct::factory()->create([
            'slug' => $product1Slug,
        ]);

        $order1 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::SUCCESS->value,
            'tenant_id' => $tenant->id,
        ]);

        $order1->items()->createMany([
            [
                'one_time_product_id' => $product1->id,
                'price' => 100,
                'currency' => Currency::find(1)->id,
                'quantity' => 1,
                'price_per_unit' => 100,
            ],
        ]);

        $product2Slug = Str::random(10);
        $product2 = OneTimeProduct::factory()->create([
            'slug' => $product2Slug,
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::SUCCESS->value,
            'tenant_id' => $tenant->id,
        ]);

        $order2->items()->createMany([
            [
                'one_time_product_id' => $product2->id,
                'price' => 100,
                'currency' => Currency::find(1)->id,
                'quantity' => 1,
                'price_per_unit' => 100,
            ],
        ]);

        $orderService = app()->make(OrderService::class);

        $orders = $orderService->findAllTenantSuccessfulOrders($tenant);

        $this->assertCount(2, $orders);

        $this->assertEquals($order1->id, $orders[0]->id);
        $this->assertEquals($order2->id, $orders[1]->id);
    }

    public function test_find_all_tenant_ordered_products(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $tenant = $this->createTenant();

        $product1Slug = Str::random(10);
        $product1 = OneTimeProduct::factory()->create([
            'slug' => $product1Slug,
        ]);

        $order1 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::SUCCESS->value,
            'tenant_id' => $tenant->id,
        ]);

        $order1->items()->createMany([
            [
                'one_time_product_id' => $product1->id,
                'price' => 100,
                'currency' => Currency::find(1)->id,
                'quantity' => 1,
                'price_per_unit' => 100,
            ],
        ]);

        $product2Slug = Str::random(10);
        $product2 = OneTimeProduct::factory()->create([
            'slug' => $product2Slug,
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::SUCCESS->value,
            'tenant_id' => $tenant->id,
        ]);

        $order2->items()->createMany([
            [
                'one_time_product_id' => $product2->id,
                'price' => 100,
                'currency' => Currency::find(1)->id,
                'quantity' => 1,
                'price_per_unit' => 100,
            ],
        ]);

        $orderService = app()->make(OrderService::class);

        $orderedProducts = $orderService->findAllTenantOrderedProducts($tenant);

        $this->assertCount(2, $orderedProducts);

        $this->assertEquals($product1->slug, $orderedProducts[0]->slug);
        $this->assertEquals($product2->slug, $orderedProducts[1]->slug);
    }

    public function test_get_user_ordered_products_metadata(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        $product1Slug = Str::random(10);
        $product1 = OneTimeProduct::factory()->create([
            'slug' => $product1Slug,
            'metadata' => ['key1' => 'value1'],
        ]);

        $order1 = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::SUCCESS->value,
        ]);

        $order1->items()->createMany([
            [
                'one_time_product_id' => $product1->id,
                'price' => 100,
                'currency' => Currency::find(1)->id,
                'quantity' => 1,
                'price_per_unit' => 100,
            ],
        ]);

        $product2Slug = Str::random(10);
        $product2 = OneTimeProduct::factory()->create([
            'slug' => $product2Slug,
            'metadata' => ['key2' => 'value2'],
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::SUCCESS->value,
        ]);

        $order2->items()->createMany([
            [
                'one_time_product_id' => $product2->id,
                'price' => 100,
                'currency' => Currency::find(1)->id,
                'quantity' => 1,
                'price_per_unit' => 100,
            ],
        ]);

        $orderService = app()->make(OrderService::class);

        $metadata = $orderService->getUserOrderedProductsMetadata($user);

        $this->assertCount(2, $metadata);
        $this->assertEquals(['key1' => 'value1'], $metadata[$product1Slug]);
        $this->assertEquals(['key2' => 'value2'], $metadata[$product2Slug]);
    }

    public function test_create()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        $orderService = app()->make(OrderService::class);

        $product1Slug = Str::random(10);
        $product = OneTimeProduct::factory()->create([
            'slug' => $product1Slug,
        ]);

        $currency = Currency::query()->where('code', 'USD')->first();
        $order = $orderService->create(
            $user,
            $tenant,
            null,
            1000,
            100,
            900,
            $currency,
            [
                [
                    'one_time_product_id' => $product->id,
                    'currency' => $currency->id,
                    'quantity' => 1,
                    'price_per_unit' => 1000,
                ],
            ],
            null,
            false
        );

        $this->assertInstanceOf(Order::class, $order);

        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals(OrderStatus::NEW->value, $order->status);
        $this->assertEquals(1000, $order->total_amount);
        $this->assertEquals(100, $order->total_discount_amount);
        $this->assertEquals(900, $order->total_amount_after_discount);
        $this->assertEquals(1, $order->items->count());
        $this->assertEquals($product->id, $order->items[0]->one_time_product_id);
        $this->assertEquals(1000, $order->items[0]->price_per_unit);
        $this->assertEquals(1, $order->items[0]->quantity);
    }

    public function test_create_local()
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $this->actingAs($user);

        $orderService = app()->make(OrderService::class);

        $product1Slug = Str::random(10);
        $product = OneTimeProduct::factory()->create([
            'slug' => $product1Slug,
        ]);

        $currency = Currency::query()->where('code', 'USD')->first();

        Event::fake();

        $order = $orderService->create(
            $user,
            $tenant,
            null,
            1000,
            100,
            900,
            $currency,
            [
                [
                    'one_time_product_id' => $product->id,
                    'currency' => $currency->id,
                    'quantity' => 1,
                    'price_per_unit' => 1000,
                ],
            ],
            null,
            true // Local order
        );

        $this->assertDatabaseHas('orders', [
            'uuid' => $order->uuid,
            'user_id' => $user->id,
            'status' => OrderStatus::SUCCESS->value,
            'total_amount' => 1000,
            'total_discount_amount' => 100,
            'total_amount_after_discount' => 900,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'one_time_product_id' => $product->id,
            'price_per_unit' => 1000,
            'quantity' => 1,
        ]);

        Event::assertDispatched(Ordered::class);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(OrderStatus::SUCCESS->value, $order->status); // Local orders are successful immediately
    }
}
