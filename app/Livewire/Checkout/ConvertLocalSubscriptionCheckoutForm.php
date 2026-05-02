<?php

namespace App\Livewire\Checkout;

use App\Constants\PlanPriceType;
use App\Exceptions\LoginException;
use App\Exceptions\NoPaymentProvidersAvailableException;
use App\Services\CalculationService;
use App\Services\DiscountService;
use App\Services\LoginService;
use App\Services\PaymentProviders\PaymentService;
use App\Services\PlanService;
use App\Services\SessionService;
use App\Services\SubscriptionService;
use App\Services\TenantSubscriptionService;
use App\Services\UserService;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;

class ConvertLocalSubscriptionCheckoutForm extends CheckoutForm
{
    private PlanService $planService;

    private SessionService $sessionService;

    private CalculationService $calculationService;

    private SubscriptionService $subscriptionService;

    private TenantSubscriptionService $tenantSubscriptionService;

    public function boot(
        PlanService $planService,
        SessionService $sessionService,
        CalculationService $calculationService,
        SubscriptionService $subscriptionService,
        TenantSubscriptionService $tenantSubscriptionService
    ) {
        $this->planService = $planService;
        $this->sessionService = $sessionService;
        $this->calculationService = $calculationService;
        $this->subscriptionService = $subscriptionService;
        $this->tenantSubscriptionService = $tenantSubscriptionService;
    }

    public function render(PaymentService $paymentService)
    {
        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();
        $planSlug = $subscriptionCheckoutDto->planSlug;

        $plan = $this->planService->getActivePlanBySlug($planSlug);

        $totals = $this->calculationService->calculatePlanTotals(
            auth()->user(),
            $planSlug,
            $subscriptionCheckoutDto?->discountCode,
            $subscriptionCheckoutDto->quantity,
        );

        return view('livewire.checkout.convert-local-subscription-checkout-form', [
            'plan' => $plan,
            'totals' => $totals,
            'userExists' => $this->userExists($this->email),
            'paymentProviders' => $this->getPaymentProviders($paymentService),
            'isTenantPickerEnabled' => false,
            'otpEnabled' => config('app.otp_login_enabled'),
            'otpVerified' => $this->otpVerified,
        ]);
    }

    public function checkout(
        LoginValidator $loginValidator,
        RegisterValidator $registerValidator,
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

        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();
        $planSlug = $subscriptionCheckoutDto->planSlug;

        $plan = $this->planService->getActivePlanBySlug($planSlug);

        if ($plan === null) {
            return redirect()->route('home');
        }

        $paymentProvider = $paymentService->getPaymentProviderBySlug(
            $this->paymentProvider
        );

        $user = auth()->user();

        $discount = null;
        if ($subscriptionCheckoutDto->discountCode !== null) {
            $discount = $discountService->getActiveDiscountByCode($subscriptionCheckoutDto->discountCode);

            if (! $discountService->isCodeRedeemableForPlan($subscriptionCheckoutDto->discountCode, $user, $plan)) {
                // this is to handle the case when user adds discount code that has max redemption limit per customer,
                // then logs-in during the checkout process and the discount code is not valid anymore
                $subscriptionCheckoutDto->discountCode = null;
                $discount = null;
                $this->dispatch('calculations-updated')->to(SubscriptionTotals::class);
            }
        }

        $subscription = $this->subscriptionService->findById($subscriptionCheckoutDto->subscriptionId);

        if (! $subscription) {
            return redirect()->route('home');
        }

        if ($subscription->user_id !== $user->id) {
            return redirect()->route('home');
        }

        $quantity = max($subscriptionCheckoutDto->quantity, $this->tenantSubscriptionService->calculateCurrentSubscriptionQuantity($subscription));

        $initData = $paymentProvider->initSubscriptionCheckout($plan, $subscription, $discount, $quantity);

        $this->sessionService->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        if ($paymentProvider->isRedirectProvider()) {
            $link = $paymentProvider->createSubscriptionCheckoutRedirectLink(
                $plan,
                $subscription,
                $discount,
                $quantity,
            );

            return redirect()->away($link);
        }

        if ($paymentProvider->isOverlayProvider()) {
            return $this->dispatch('start-overlay-checkout',
                paymentProvider: $paymentProvider->getSlug(),
                initData: $initData,
                successUrl: route('checkout.subscription.success'),
                email: $user->email,
                subscriptionUuid: $subscription->uuid,
            );
        }

        return redirect()->route('checkout.subscription.success');
    }

    protected function getPaymentProviders(PaymentService $paymentService)
    {
        if (count($this->paymentProviders) > 0) {
            return $this->paymentProviders;
        }

        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();
        $planSlug = $subscriptionCheckoutDto->planSlug;

        $plan = $this->planService->getActivePlanBySlug($planSlug);

        $planPrice = $this->calculationService->getPlanPrice($plan);
        $shouldSupportSetupFees = ! empty($planPrice->setup_fee);

        $this->paymentProviders = $paymentService->getActivePaymentProvidersForPlan(
            $plan,
            true,
            true,
            $planPrice->type === PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value,
            $shouldSupportSetupFees,
        );

        if (empty($this->paymentProviders)) {
            logger()->error('No payment providers available for plan', [
                'plan' => $plan->slug,
            ]);

            throw new NoPaymentProvidersAvailableException('No payment providers available for plan'.$plan->slug);
        }

        if ($this->paymentProvider === null) {
            $this->paymentProvider = $this->paymentProviders[0]->getSlug();
        }

        return $this->paymentProviders;
    }
}
