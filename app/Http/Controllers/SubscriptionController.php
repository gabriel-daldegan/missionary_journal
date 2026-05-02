<?php

namespace App\Http\Controllers;

use App\Constants\TenancyPermissionConstants;
use App\Services\CalculationService;
use App\Services\PaymentProviders\PaymentService;
use App\Services\PlanService;
use App\Services\SubscriptionService;
use App\Services\TenantPermissionService;
use App\Services\TenantService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private PlanService $planService,
        private SubscriptionService $subscriptionService,
        private PaymentService $paymentService,
        private CalculationService $calculationService,
        private TenantPermissionService $tenantPermissionService,
        private TenantService $tenantService,
    ) {}

    public function changePlan(string $subscriptionUuid, string $newPlanSlug, string $tenantUuid, Request $request)
    {
        $user = auth()->user();

        $tenant = $this->tenantService->getTenantByUuid($tenantUuid);

        if (! $this->tenantPermissionService->tenantUserHasPermissionTo($tenant, $user, TenancyPermissionConstants::PERMISSION_UPDATE_SUBSCRIPTIONS)) {
            return redirect()->back()->with('error', __('You do not have permission to change plans.'));
        }

        $subscription = $this->subscriptionService->findActiveByTenantAndSubscriptionUuid($tenant, $subscriptionUuid);

        if (! $subscription) {
            return redirect()->back()->with('error', __('You do not have an active subscription.'));
        }

        if ($subscription->plan->slug === $newPlanSlug) {
            return redirect()->back()->with('error', __('You are already subscribed to this plan.'));
        }

        $newPlan = $this->planService->getActivePlanBySlug($newPlanSlug);

        if (! $newPlan) {
            return redirect()->back()->with('error', __('Plan not found.'));
        }

        $tenantUserCount = $tenant->users()->count();
        if ($newPlan->max_users_per_tenant > 0 && $tenantUserCount > $newPlan->max_users_per_tenant) {
            return redirect()->back()->with('error', __('This plan supports a maximum of :max users, but your workspace currently has :count users. Please remove :excess user(s) before switching to this plan.', [
                'max' => $newPlan->max_users_per_tenant,
                'count' => $tenantUserCount,
                'excess' => $tenantUserCount - $newPlan->max_users_per_tenant,
            ]));
        }

        $paymentProvider = $subscription->paymentProvider()->first();

        if (! $paymentProvider) {
            return redirect()->back()->with('error', __('Error finding payment provider.'));
        }

        $paymentProviderStrategy = $this->paymentService->getPaymentProviderBySlug(
            $paymentProvider->slug
        );

        $isProrated = config('app.payment.proration_enabled', true);

        $totals = $this->calculationService->calculateNewPlanTotals(
            $subscription,
            $newPlanSlug,
            $isProrated,
        );

        if ($request->isMethod('post')) {
            $result = $this->subscriptionService->changePlan($subscription, $paymentProviderStrategy, $newPlanSlug, $isProrated);

            if ($result) {
                return redirect()->route('subscription.change-plan.thank-you');
            } else {
                return redirect()->route('home')->with('error', __('Error changing plan.'));
            }
        }

        return view('subscription.change', [
            'subscription' => $subscription,
            'newPlan' => $newPlan,
            'isProrated' => $isProrated,
            'user' => $user,
            'totals' => $totals,
        ]);
    }

    public function success()
    {
        return view('subscription.change-thank-you');
    }
}
