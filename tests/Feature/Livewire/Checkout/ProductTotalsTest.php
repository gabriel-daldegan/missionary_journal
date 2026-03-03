<?php

namespace Tests\Feature\Livewire\Checkout;

use App\Dto\CartDto;
use App\Dto\CartItemDto;
use App\Dto\TotalsDto;
use App\Livewire\Checkout\ProductTotals;
use App\Models\Currency;
use App\Models\Discount;
use App\Models\OneTimeProduct;
use App\Models\OneTimeProductPrice;
use App\Models\User;
use App\Services\SessionService;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\FeatureTest;

class ProductTotalsTest extends FeatureTest
{
    public function test_coupon_from_session_is_applied_on_mount(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->createProductWithPrice();
        $code = $this->createDiscountCodeForProduct($product);

        $sessionService = app(SessionService::class);
        $sessionService->saveCouponCode($code);
        $this->setUpCartSession($sessionService, $product);

        Livewire::test(ProductTotals::class, [
            'totals' => $this->makeTotals(),
            'product' => $product,
            'page' => 'http://localhost/buy/product',
        ]);

        // Coupon code should be consumed from session
        $this->assertNull($sessionService->getCouponCode());

        // Discount code should be stored in cart DTO
        $updatedCart = $sessionService->getCartDto();
        $this->assertEquals($code, $updatedCart->discountCode);
    }

    public function test_invalid_coupon_from_session_is_kept_in_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->createProductWithPrice();

        $sessionService = app(SessionService::class);
        $sessionService->saveCouponCode('INVALID_CODE');
        $this->setUpCartSession($sessionService, $product);

        Livewire::test(ProductTotals::class, [
            'totals' => $this->makeTotals(),
            'product' => $product,
            'page' => 'http://localhost/buy/product',
        ]);

        // Coupon code should still be in session (not redeemable, kept for later)
        $this->assertEquals('INVALID_CODE', $sessionService->getCouponCode());

        // Discount code should NOT be stored in cart DTO
        $updatedCart = $sessionService->getCartDto();
        $this->assertNull($updatedCart->discountCode);
    }

    public function test_coupon_not_redeemable_for_product_is_kept_in_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->createProductWithPrice();

        // Create a discount NOT attached to this product
        $discount = Discount::create([
            'name' => 'other product discount',
            'description' => 'test',
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => true,
            'valid_until' => null,
            'action_type' => null,
            'max_redemptions' => -1,
            'max_redemptions_per_user' => -1,
            'is_recurring' => false,
            'redemptions' => 0,
        ]);

        $code = Str::random(10);
        $discount->codes()->create(['code' => $code]);

        $sessionService = app(SessionService::class);
        $sessionService->saveCouponCode($code);
        $this->setUpCartSession($sessionService, $product);

        Livewire::test(ProductTotals::class, [
            'totals' => $this->makeTotals(),
            'product' => $product,
            'page' => 'http://localhost/buy/product',
        ]);

        // Coupon code should still be in session (not redeemable for this product)
        $this->assertEquals($code, $sessionService->getCouponCode());

        // Discount code should NOT be stored in cart DTO
        $updatedCart = $sessionService->getCartDto();
        $this->assertNull($updatedCart->discountCode);
    }

    public function test_no_coupon_in_session_does_nothing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = $this->createProductWithPrice();

        $sessionService = app(SessionService::class);
        $this->setUpCartSession($sessionService, $product);

        Livewire::test(ProductTotals::class, [
            'totals' => $this->makeTotals(),
            'product' => $product,
            'page' => 'http://localhost/buy/product',
        ]);

        $this->assertNull($sessionService->getCouponCode());

        $updatedCart = $sessionService->getCartDto();
        $this->assertNull($updatedCart->discountCode);
    }

    private function createProductWithPrice(): OneTimeProduct
    {
        $product = OneTimeProduct::factory()->create([
            'slug' => 'product-'.Str::random(10),
            'is_active' => true,
        ]);

        OneTimeProductPrice::create([
            'one_time_product_id' => $product->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 5000,
        ]);

        return $product;
    }

    private function createDiscountCodeForProduct(OneTimeProduct $product): string
    {
        $discount = Discount::create([
            'name' => 'product coupon',
            'description' => 'test',
            'type' => 'percentage',
            'amount' => 15,
            'is_active' => true,
            'valid_until' => null,
            'action_type' => null,
            'max_redemptions' => -1,
            'max_redemptions_per_user' => -1,
            'is_recurring' => false,
            'redemptions' => 0,
        ]);

        $discount->oneTimeProducts()->attach($product);

        $code = Str::random(10);
        $discount->codes()->create(['code' => $code]);

        return $code;
    }

    private function setUpCartSession(SessionService $sessionService, OneTimeProduct $product): void
    {
        $cartItem = new CartItemDto;
        $cartItem->productId = (string) $product->id;
        $cartItem->quantity = 1;

        $cartDto = new CartDto;
        $cartDto->items = [$cartItem];

        $sessionService->saveCartDto($cartDto);
    }

    private function makeTotals(): TotalsDto
    {
        $totals = new TotalsDto;
        $totals->subtotal = 5000;
        $totals->discountAmount = 0;
        $totals->amountDue = 5000;
        $totals->currencyCode = 'USD';

        return $totals;
    }
}
