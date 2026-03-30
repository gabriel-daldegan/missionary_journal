<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Currency;
use App\Models\OneTimeProduct;
use App\Models\OneTimeProductPrice;
use Tests\Feature\FeatureTest;

class ProductCheckoutControllerTest extends FeatureTest
{
    public function test_checkout_loads()
    {
        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-5'.rand(1, 1000),
            'is_active' => true,
            'max_quantity' => 1,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $response = $this->followingRedirects()->get(route('buy.product', [
            'productSlug' => $product->slug,
        ]));

        $response->assertStatus(200);

        $response->assertSee('Complete your purchase');
        $response->assertDontSeeHtml('wire:model.blur="quantity"');
    }

    public function test_checkout_quantity()
    {
        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-slug-5'.rand(1, 100),
            'is_active' => true,
            'max_quantity' => 5,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $response = $this->followingRedirects()->get(route('buy.product', [
            'productSlug' => $product->slug,
        ]));

        $response->assertStatus(200);

        $response->assertSee('Complete your purchase');
        $response->assertSeeHtml('wire:model.live.blur="quantity"');
    }
}
