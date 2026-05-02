<?php

namespace App\Livewire\Checkout;

use App\Dto\CartDto;
use App\Dto\TotalsDto;
use App\Models\OneTimeProduct;
use App\Services\CalculationService;
use App\Services\DiscountService;
use App\Services\SessionService;
use Livewire\Attributes\On;
use Livewire\Component;

class ProductTotals extends Component
{
    public $page;

    public $subtotal;

    public $product;

    public $discountAmount;

    public $amountDue;

    public $currencyCode;

    public $code;

    private DiscountService $discountService;

    private CalculationService $calculationService;

    private SessionService $sessionService;

    public function boot(DiscountService $discountService, CalculationService $calculationService, SessionService $sessionService)
    {
        $this->discountService = $discountService;
        $this->calculationService = $calculationService;
        $this->sessionService = $sessionService;
    }

    public function mount(TotalsDto $totals, OneTimeProduct $product, $page)
    {
        $this->page = $page;
        $this->product = $product;
        $this->subtotal = $totals->subtotal;
        $this->discountAmount = $totals->discountAmount;
        $this->amountDue = $totals->amountDue;
        $this->currencyCode = $totals->currencyCode;

        $this->applyCouponFromSession($product);
    }

    private function applyCouponFromSession(OneTimeProduct $product): void
    {
        $couponCode = $this->sessionService->getCouponCode();

        if ($couponCode === null) {
            return;
        }

        if (! $this->discountService->isCodeRedeemableForOneTimeProduct($couponCode, auth()->user(), $product)) {
            return;
        }

        $this->sessionService->clearCouponCode();

        $cartDto = $this->getCartDto();
        $cartDto->discountCode = $couponCode;
        $this->saveCartDto($cartDto);

        $this->updateTotals();
    }

    private function getCartDto(): ?CartDto
    {
        return $this->sessionService->getCartDto();
    }

    private function saveCartDto(CartDto $cartDto): void
    {
        $this->sessionService->saveCartDto($cartDto);
    }

    public function add()
    {
        $code = $this->code;

        if ($code === null) {
            session()->flash('error', __('Please enter a discount code.'));

            return;
        }

        $isRedeemable = $this->discountService->isCodeRedeemableForOneTimeProduct($code, auth()->user(), $this->product);

        if (! $isRedeemable) {
            session()->flash('error', __('This discount code is invalid.'));

            return;
        }

        $cartDto = $this->getCartDto();
        $cartDto->discountCode = $code;

        $this->saveCartDto($cartDto);

        $this->updateTotals();

        session()->flash('success', __('The discount code has been applied.'));
    }

    public function remove()
    {
        $cartDto = $this->getCartDto();
        $cartDto->discountCode = null;
        $this->saveCartDto($cartDto);

        session()->flash('success', __('The discount code has been removed.'));

        $this->updateTotals();
    }

    #[On('calculations-updated')]
    public function updateTotals()
    {
        $totals = $this->calculationService->calculateCartTotals(
            $this->getCartDto(),
            auth()->user()
        );

        $this->subtotal = $totals->subtotal;
        $this->discountAmount = $totals->discountAmount;
        $this->amountDue = $totals->amountDue;
        $this->currencyCode = $totals->currencyCode;

        $this->dispatch('refresh-product-checkout');
    }

    public function render()
    {
        return view('livewire.checkout.product-totals', [
            'addedCode' => $this->getCartDto()->discountCode,
        ]);
    }
}
