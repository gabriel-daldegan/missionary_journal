<?php

namespace App\Services\PaymentProviders\Creem;

use App\Client\CreemClient;
use App\Models\OneTimeProduct;
use App\Models\Plan;
use App\Services\CalculationService;
use Exception;

class CreemProductValidator
{
    public function __construct(
        private CreemClient $client,
        private CalculationService $calculationService,
    ) {}

    public function validatePlan(string $productId, Plan $plan): bool
    {
        $product = $this->fetchProduct($productId);

        $planPrice = $this->calculationService->getPlanPrice($plan);

        if ($planPrice->price != $product['price']) {
            throw new Exception(sprintf('Price mismatch. Plan price: %d, Creem price: %d', $planPrice->price, $product['price']));
        }

        if ($product['billing_type'] !== 'recurring') {
            throw new Exception('Creem product is not a recurring subscription.');
        }

        $expectedBillingPeriod = $this->mapIntervalToBillingPeriod($plan->interval->slug, $plan->interval_count);
        if ($product['billing_period'] !== $expectedBillingPeriod) {
            throw new Exception(sprintf('Billing period mismatch. Expected: %s, Creem billing period: %s', $expectedBillingPeriod, $product['billing_period']));
        }

        return true;
    }

    public function validateOneTimeProduct(string $productId, OneTimeProduct $oneTimeProduct): bool
    {
        $product = $this->fetchProduct($productId);

        $price = $this->calculationService->getOneTimeProductPrice($oneTimeProduct);

        if ($price->price != $product['price']) {
            throw new Exception(sprintf('Price mismatch. One time product price: %d, Creem price: %d', $price->price, $product['price']));
        }

        if ($product['billing_type'] === 'recurring') {
            throw new Exception('Creem product is a recurring subscription, not a one-time product.');
        }

        return true;
    }

    private function fetchProduct(string $productId): array
    {
        $response = $this->client->getProduct($productId);

        if (! $response->successful()) {
            throw new Exception('Failed to fetch product from Creem. Please check the product ID.');
        }

        return $response->json();
    }

    private function mapIntervalToBillingPeriod(string $intervalSlug, int $intervalCount): string
    {
        if ($intervalCount === 3 && $intervalSlug === 'month') {
            return 'every-3-months';
        }

        if ($intervalCount === 6 && $intervalSlug === 'month') {
            return 'every-6-months';
        }

        return match ($intervalSlug) {
            'week' => 'every-week',
            'month' => 'every-month',
            'year' => 'every-year',
            default => 'every-'.$intervalSlug,
        };
    }
}
