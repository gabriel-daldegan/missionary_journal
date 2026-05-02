<?php

namespace App\Livewire\Checkout;

use App\Exceptions\LoginException;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Services\CalculationService;
use App\Services\CheckoutService;
use App\Services\LoginService;
use App\Services\PaymentProviders\PaymentService;
use App\Services\PlanService;
use App\Services\SessionService;
use App\Services\SubscriptionService;
use App\Services\UserService;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;

class LocalSubscriptionCheckoutForm extends CheckoutForm
{
    private PlanService $planService;

    private SessionService $sessionService;

    private CalculationService $calculationService;

    private SubscriptionService $subscriptionService;

    public function boot(
        PlanService $planService,
        SessionService $sessionService,
        CalculationService $calculationService,
        SubscriptionService $subscriptionService,
    ) {
        $this->planService = $planService;
        $this->sessionService = $sessionService;
        $this->calculationService = $calculationService;
        $this->subscriptionService = $subscriptionService;
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

        return view('livewire.checkout.local-subscription-checkout-form', [
            'userExists' => $this->userExists($this->email),
            'plan' => $plan,
            'totals' => $totals,
            'otpEnabled' => config('app.otp_login_enabled'),
            'otpVerified' => $this->otpVerified,
        ]);
    }

    public function checkout(
        LoginValidator $loginValidator,
        RegisterValidator $registerValidator,
        CheckoutService $checkoutService,
        UserService $userService,
        LoginService $loginService,
    ) {
        if (! config('app.trial_without_payment.enabled')) {
            return redirect()->route('home');
        }

        try {
            parent::handleLoginOrRegistration($loginValidator, $registerValidator, $userService, $loginService);
        } catch (LoginException $exception) { // 2fa is enabled, user has to go through typical login flow to enter 2fa code
            return redirect()->route('login');
        }

        if (auth()->user() === null) {
            return redirect()->route('login');
        }

        if (! $this->subscriptionService->canUserHaveSubscriptionTrial(auth()->user())) {
            return redirect()->route('home');
        }

        $subscriptionCheckoutDto = $this->sessionService->getSubscriptionCheckoutDto();
        $planSlug = $subscriptionCheckoutDto->planSlug;

        $plan = $this->planService->getActivePlanBySlug($planSlug);

        if ($plan === null) {
            return redirect()->route('home');
        }

        try {
            $subscription = $checkoutService->initLocalSubscriptionCheckout(
                $planSlug,
                $subscriptionCheckoutDto->tenantUuid,
                $subscriptionCheckoutDto->quantity,
                $subscriptionCheckoutDto->shouldCreateNewTenant,
            );
        } catch (SubscriptionCreationNotAllowedException $e) {
            return redirect()->route('checkout.subscription.already-subscribed');
        }

        $subscriptionCheckoutDto->subscriptionId = $subscription->id;
        $this->sessionService->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        if ($this->subscriptionService->shouldUserVerifyPhoneNumberForTrial(auth()->user())) {
            $this->redirect(route('user.phone-verify'));

            return;
        }

        $this->redirect(route('checkout.subscription.success'));
    }
}
