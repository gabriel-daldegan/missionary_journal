<?php

namespace Tests\Feature\Services;

use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Models\Currency;
use App\Models\Interval;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Product;
use App\Models\Subscription;
use App\Services\CalculationService;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class CalculationServiceTest extends FeatureTest
{
    private function createSeatBasedWithIncludedSeatsPlan(int $basePrice, int $includedSeats, int $extraSeatPrice): Plan
    {
        $product = Product::factory()->create();
        $currency = Currency::where('code', 'USD')->first();
        $interval = Interval::where('slug', 'month')->first();

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'is_active' => true,
            'type' => PlanType::SEAT_BASED->value,
            'product_id' => $product->id,
            'interval_id' => $interval->id,
            'interval_count' => 1,
        ]);

        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'price' => $basePrice,
            'currency_id' => $currency->id,
            'type' => PlanPriceType::SEAT_BASED_WITH_INCLUDED_SEATS->value,
            'included_seats' => $includedSeats,
            'extra_seat_price' => $extraSeatPrice,
        ]);

        return $plan;
    }

    public function test_seat_based_with_included_no_extra_seats()
    {
        $plan = $this->createSeatBasedWithIncludedSeatsPlan(5000, 5, 1000);

        /** @var CalculationService $service */
        $service = app()->make(CalculationService::class);

        $totals = $service->calculatePlanTotals(null, $plan->slug, quantity: 3);

        $this->assertEquals(5000, $totals->subtotal);
        $this->assertEquals(5000, $totals->basePrice);
        $this->assertEquals(5, $totals->includedSeats);
        $this->assertEquals(1000, $totals->extraSeatPrice);
        $this->assertEquals(0, $totals->extraSeats);
    }

    public function test_seat_based_with_included_at_limit()
    {
        $plan = $this->createSeatBasedWithIncludedSeatsPlan(5000, 5, 1000);

        /** @var CalculationService $service */
        $service = app()->make(CalculationService::class);

        $totals = $service->calculatePlanTotals(null, $plan->slug, quantity: 5);

        $this->assertEquals(5000, $totals->subtotal);
        $this->assertEquals(0, $totals->extraSeats);
    }

    public function test_seat_based_with_included_extra_seats()
    {
        $plan = $this->createSeatBasedWithIncludedSeatsPlan(5000, 5, 1000);

        /** @var CalculationService $service */
        $service = app()->make(CalculationService::class);

        $totals = $service->calculatePlanTotals(null, $plan->slug, quantity: 7);

        $this->assertEquals(7000, $totals->subtotal);
        $this->assertEquals(5000, $totals->basePrice);
        $this->assertEquals(2, $totals->extraSeats);
        $this->assertEquals(1000, $totals->extraSeatPrice);
    }

    public function test_seat_based_with_included_plan_change_totals()
    {
        $plan = $this->createSeatBasedWithIncludedSeatsPlan(5000, 5, 1000);

        $tenant = $this->createTenant();
        // Add 7 users to tenant
        for ($i = 0; $i < 7; $i++) {
            $this->createUser($tenant);
        }

        $subscription = Subscription::factory()->create([
            'user_id' => $tenant->users()->first()->id,
            'plan_id' => $plan->id,
            'tenant_id' => $tenant->id,
            'quantity' => 7,
        ]);

        /** @var CalculationService $service */
        $service = app()->make(CalculationService::class);

        $totals = $service->calculateNewPlanTotals($subscription, $plan->slug);

        $this->assertEquals(7000, $totals->subtotal);
        $this->assertEquals(5000, $totals->basePrice);
        $this->assertEquals(2, $totals->extraSeats);
        $this->assertEquals(7, $totals->quantity);
    }

    public function test_seat_based()
    {
        $product = Product::factory()->create();
        $currency = Currency::where('code', 'USD')->first();
        $interval = Interval::where('slug', 'month')->first();

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'is_active' => true,
            'type' => PlanType::SEAT_BASED->value,
            'product_id' => $product->id,
            'interval_id' => $interval->id,
            'interval_count' => 1,
        ]);

        PlanPrice::factory()->create([
            'plan_id' => $plan->id,
            'price' => 1000,
            'currency_id' => $currency->id,
            'type' => PlanPriceType::FLAT_RATE->value,
        ]);

        /** @var CalculationService $service */
        $service = app()->make(CalculationService::class);

        $totals = $service->calculatePlanTotals(null, $plan->slug, quantity: 5);

        $this->assertEquals(5000, $totals->subtotal);
        $this->assertNull($totals->basePrice);
        $this->assertNull($totals->includedSeats);
    }
}
