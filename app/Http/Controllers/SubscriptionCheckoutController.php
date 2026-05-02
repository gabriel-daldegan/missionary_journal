<?php

namespace App\Http\Controllers;

use App\Constants\SubscriptionType;
use App\Models\Plan;
use App\Services\CalculationService;
use App\Services\DiscountService;
use App\Services\SessionService;
use App\Services\SubscriptionService;
use App\Services\TenantSubscriptionService;

class SubscriptionCheckoutController extends Controller
{
    public function __construct(
        private DiscountService $discountService,
        private CalculationService $calculationService,
        private SubscriptionService $subscriptionService,
        private SessionService $sessionService,
        private TenantSubscriptionService $tenantSubscriptionService,
    ) {}

    public function subscriptionCheckout(string $planSlug)
    {
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->firstOrFail();
        $checkoutDto = $this->sessionService->getSubscriptionCheckoutDto();

        if ($checkoutDto->planSlug !== $planSlug) {
            $checkoutDto = $this->sessionService->resetSubscriptionCheckoutDto();
        }

        $checkoutDto->planSlug = $planSlug;

        $this->sessionService->saveSubscriptionCheckoutDto($checkoutDto);

        $user = auth()->user();

        if ($plan->has_trial &&
            config('app.trial_without_payment.enabled') &&
            $this->subscriptionService->canUserHaveSubscriptionTrial($user)
        ) {
            return view('checkout.local-subscription');
        }

        return view('checkout.subscription');
    }

    public function convertLocalSubscriptionCheckout(?string $subscriptionUuid = null)
    {
        $subscription = $this->subscriptionService->findByUuidOrFail($subscriptionUuid);

        if (! $this->subscriptionService->isLocalSubscription($subscription)) {
            return redirect()->route('home');
        }

        $planSlug = $subscription->plan->slug;
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->firstOrFail();

        $checkoutDto = $this->sessionService->getSubscriptionCheckoutDto();

        if ($checkoutDto->planSlug !== $planSlug) {
            $checkoutDto = $this->sessionService->resetSubscriptionCheckoutDto();
        }

        $checkoutDto->quantity = max($checkoutDto->quantity, $this->tenantSubscriptionService->calculateCurrentSubscriptionQuantity($subscription));
        $checkoutDto->planSlug = $planSlug;
        $checkoutDto->subscriptionId = $subscription->id;

        $this->sessionService->saveSubscriptionCheckoutDto($checkoutDto);

        $totals = $this->calculationService->calculatePlanTotals(
            auth()->user(),
            $planSlug,
            $checkoutDto?->discountCode,
            $checkoutDto->quantity,
        );

        return view('checkout.convert-local-subscription', [
            'plan' => $plan,
            'totals' => $totals,
            'checkoutDto' => $checkoutDto,
        ]);
    }

    public function subscriptionCheckoutSuccess()
    {
        $result = $this->handleSubscriptionSuccess();

        if (! $result) {
            return redirect()->route('home');
        }

        $checkoutDto = $this->sessionService->getSubscriptionCheckoutDto();
        $subscription = $this->subscriptionService->findById($checkoutDto->subscriptionId);

        $this->sessionService->resetSubscriptionCheckoutDto();

        if ($subscription && $subscription->type === SubscriptionType::LOCALLY_MANAGED) {
            return view('checkout.local-subscription-thank-you');
        }

        return view('checkout.subscription-thank-you');
    }

    public function convertLocalSubscriptionCheckoutSuccess()
    {
        $result = $this->handleSubscriptionSuccess();

        if (! $result) {
            return redirect()->route('home');
        }

        $this->sessionService->resetSubscriptionCheckoutDto();

        return view('checkout.convert-local-subscription-thank-you');
    }

    private function handleSubscriptionSuccess(): bool
    {
        $checkoutDto = $this->sessionService->getSubscriptionCheckoutDto();

        if ($checkoutDto->subscriptionId === null) {
            return false;
        }

        $this->subscriptionService->setAsPending($checkoutDto->subscriptionId);
        $this->subscriptionService->updateUserSubscriptionTrials($checkoutDto->subscriptionId);

        if ($checkoutDto->discountCode !== null) {
            $this->discountService->redeemCodeForSubscription($checkoutDto->discountCode, auth()->user(), $checkoutDto->subscriptionId);
        }

        return true;
    }
}
