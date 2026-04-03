<?php

namespace App\Livewire\Checkout;

use App\Dto\TotalsDto;
use App\Exceptions\LoginException;
use App\Exceptions\NoPaymentProvidersAvailableException;
use App\Services\CalculationService;
use App\Services\CheckoutService;
use App\Services\DiscountService;
use App\Services\LoginService;
use App\Services\OneTimeProductService;
use App\Services\PaymentProviders\PaymentService;
use App\Services\SessionService;
use App\Services\UserService;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;
use Livewire\Attributes\On;

class ProductCheckoutForm extends CheckoutForm
{
    private OneTimeProductService $productService;

    private SessionService $sessionService;

    private CalculationService $calculationService;

    private OneTimeProductService $oneTimeProductService;

    public function boot(
        OneTimeProductService $productService,
        SessionService $sessionService,
        CalculationService $calculationService,
        OneTimeProductService $oneTimeProductService,
    ) {
        $this->productService = $productService;
        $this->sessionService = $sessionService;
        $this->calculationService = $calculationService;
        $this->oneTimeProductService = $oneTimeProductService;
    }

    public function checkout(
        LoginValidator $loginValidator,
        RegisterValidator $registerValidator,
        CheckoutService $checkoutService,
        PaymentService $paymentService,
        DiscountService $discountService,
        UserService $userService,
        LoginService $loginService,
    ) {
        try {
            parent::handleLoginOrRegistration($loginValidator, $registerValidator, $userService, $loginService);
        } catch (LoginException $exception) { // 2fa is enabled, user has to go through typical login flow to enter 2fa code
            return redirect()->route('login');
        }

        if (auth()->user() === null) {
            return redirect()->route('login');
        }

        $cartDto = $this->sessionService->getCartDto();

        $discount = null;
        if ($cartDto->discountCode !== null) {
            $discount = $discountService->getActiveDiscountByCode($cartDto->discountCode);
            $product = $this->oneTimeProductService->getOneTimeProductById($cartDto->items[0]->productId);

            if (! $discountService->isCodeRedeemableForOneTimeProduct($cartDto->discountCode, auth()->user(), $product)) {
                // this is to handle the case when user adds discount code that has max redemption limit per customer,
                // then logs-in during the checkout process and the discount code is not valid anymore
                $cartDto->discountCode = null;
                $discount = null;
                $this->dispatch('calculations-updated')->to(ProductTotals::class);
            }
        }

        $user = auth()->user();
        $totals = $this->calculationService->calculateCartTotals($cartDto, $user);

        $order = $checkoutService->initProductCheckout(
            $cartDto,
            $cartDto->tenantUuid,
            $totals,
            $cartDto->shouldCreateNewTenant,
        );

        $cartDto->orderId = $order->id;

        $this->sessionService->saveCartDto($cartDto);

        if ($this->requiresPayment($totals)) {
            $paymentProvider = $paymentService->getPaymentProviderBySlug(
                $this->paymentProvider
            );

            $initData = $paymentProvider->initProductCheckout($order, $discount);

            if ($paymentProvider->isRedirectProvider()) {
                $link = $paymentProvider->createProductCheckoutRedirectLink(
                    $order,
                    $discount,
                );

                return redirect()->away($link);
            }

            if ($paymentProvider->isOverlayProvider()) {
                return $this->dispatch('start-overlay-checkout',
                    paymentProvider: $paymentProvider->getSlug(),
                    initData: $initData,
                    successUrl: route('checkout.product.success'),
                    email: $user->email,
                    orderUuid: $order->uuid,
                );
            }
        }

        return redirect()->route('checkout.product.success');
    }

    public function render(PaymentService $paymentService)
    {
        $cartDto = $this->sessionService->getCartDto();

        $product = $this->productService->getOneTimeProductById($cartDto->items[0]->productId);

        $totals = $this->calculationService->calculateCartTotals($cartDto, auth()->user());

        $productHasEditableQuantity = intval($product->max_quantity) !== 1;

        $paymentProviders = $this->getPaymentProvidersForProduct($paymentService, $productHasEditableQuantity);

        return view('livewire.checkout.product-checkout-form', [
            'product' => $product,
            'cartDto' => $cartDto,
            'successUrl' => route('checkout.product.success'),
            'userExists' => $this->userExists($this->email),
            'paymentProviders' => $paymentProviders,
            'totals' => $totals,
            'requiresPayment' => $this->requiresPayment($totals),
            'otpEnabled' => config('app.otp_login_enabled'),
            'otpVerified' => $this->otpVerified,
        ]);
    }

    #[On('refresh-product-checkout')]
    public function refresh()
    {
        // do nothing, just re-render the component
    }

    protected function getPaymentProvidersForProduct(PaymentService $paymentService, bool $requireQuantitySupport): array
    {
        if (count($this->paymentProviders) > 0) {
            return $this->paymentProviders;
        }

        $this->paymentProviders = $paymentService->getActivePaymentProvidersForOneTimePurchase($requireQuantitySupport, true);

        if (empty($this->paymentProviders)) {
            logger()->error('No payment providers available');

            throw new NoPaymentProvidersAvailableException('No payment providers available');
        }

        if ($this->paymentProvider === null) {
            $this->paymentProvider = $this->paymentProviders[0]->getSlug();
        }

        return $this->paymentProviders;
    }

    public function requiresPayment(TotalsDto $totals): bool
    {
        return $totals->amountDue > 0;
    }
}
