<?php

namespace App\Livewire\Checkout;

use App\Models\OneTimeProduct;
use App\Services\OneTimeProductService;
use App\Services\SessionService;
use Livewire\Component;

class ProductQuantity extends Component
{
    public $quantity;

    public $productSlug;

    public $maxQuantity;

    private SessionService $sessionService;

    private OneTimeProductService $oneTimeProductService;

    public function boot(
        SessionService $sessionService,
        OneTimeProductService $oneTimeProductService,
    ) {
        $this->sessionService = $sessionService;
        $this->oneTimeProductService = $oneTimeProductService;
    }

    public function mount(OneTimeProduct $product)
    {
        $this->productSlug = $product->slug;
        $quantity = max($this->sessionService->getCartDto()->items[0]?->quantity ?? 1, 1);
        $this->quantity = $quantity;
        $this->maxQuantity = $product->max_quantity ?? 1;
    }

    public function updatedQuantity(int $value)
    {
        $product = $this->oneTimeProductService->getProductWithPriceBySlug($this->productSlug);

        $maxRule = '';
        if ($product->max_quantity > 0) {
            $maxRule = '|max:'.$product->max_quantity;
        }

        $checkoutDto = $this->sessionService->getCartDto();

        $min = 1;

        $this->validate([
            'quantity' => 'required|integer|min:'.$min.$maxRule,
        ]);

        $checkoutDto->items[0]->quantity = $value;
        $this->sessionService->saveCartDto($checkoutDto);

        $this->dispatch('calculations-updated')->to(ProductTotals::class);
    }

    public function render()
    {
        return view('livewire.checkout.product-quantity');
    }
}
