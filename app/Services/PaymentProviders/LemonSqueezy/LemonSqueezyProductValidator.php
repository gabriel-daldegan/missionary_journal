<?php

namespace App\Services\PaymentProviders\LemonSqueezy;

use App\Client\LemonSqueezyClient;
use App\Constants\PlanPriceTierConstants;
use App\Constants\PlanPriceType;
use App\Models\OneTimeProduct;
use App\Models\Plan;
use App\Services\CalculationService;
use Exception;

class LemonSqueezyProductValidator
{
    public function __construct(
        private LemonSqueezyClient $client,
        private CalculationService $calculationService,
    ) {}

    public function validatePlan(string $variantId, Plan $plan): bool
    {
        $response = $this->client->getVariant($variantId);

        if (! $response->successful()) {
            throw new Exception('Failed to validate product with Lemon Squeezy.');
        }

        $variantPriceModelResponse = $this->client->getVariantPriceModel($variantId);

        if (! $variantPriceModelResponse->successful()) {
            throw new Exception('Failed to retrieve variant price model from Lemon Squeezy.');
        }

        $planPrice = $this->calculationService->getPlanPrice($plan);

        if ($variantPriceModelResponse['data']['attributes']['scheme'] === 'standard') {
            if ($planPrice->price != $response['data']['attributes']['price']) {
                throw new Exception(sprintf('Price mismatch. Plan price: %d, Lemon Squeezy price: %d', $planPrice->price, $response['data']['attributes']['price']));
            }
        } elseif ($variantPriceModelResponse['data']['attributes']['scheme'] === 'graduated' || $variantPriceModelResponse['data']['attributes']['scheme'] === 'volume') {

            if ($planPrice->price > 0) {
                throw new Exception(sprintf('Lemon Squeezy does not support fixed fee price for tiered pricing. Be aware of that. Plan fixed fee price: %d', $planPrice->price));
            }

            if ($planPrice->type == PlanPriceType::USAGE_BASED_TIERED_GRADUATED->value && $variantPriceModelResponse['data']['attributes']['scheme'] !== 'graduated') {
                throw new Exception('Plan is tiered graduated pricing, but Lemon Squeezy product is '.$variantPriceModelResponse['data']['attributes']['scheme']);
            }

            if ($planPrice->type == PlanPriceType::USAGE_BASED_TIERED_VOLUME->value && $variantPriceModelResponse['data']['attributes']['scheme'] !== 'volume') {
                throw new Exception('Plan is tiered volume pricing, but Lemon Squeezy product is '.$variantPriceModelResponse['data']['attributes']['scheme']);
            }

            $lemonSqueezyTiers = $variantPriceModelResponse['data']['attributes']['tiers'];
            $planTiers = $planPrice->tiers;

            if ($planPrice->type == PlanPriceType::USAGE_BASED_PER_UNIT->value) {
                if (($lemonSqueezyTiers[0]['unit_price_decimal'] ?? null) != $planPrice->price_per_unit) {
                    throw new Exception(sprintf('Price per unit mismatch. Lemon Squeezy price: %d, Plan price: %d', $lemonSqueezyTiers[0]['unit_price_decimal'], $planPrice->price_per_unit));
                }
            } else {

                if (count($lemonSqueezyTiers) != count($planTiers)) {
                    throw new Exception(sprintf('Price Tier count mismatch. Lemon Squeezy tiers count: %d, Plan tiers count: %d', count($lemonSqueezyTiers), count($planTiers)));
                }

                foreach ($lemonSqueezyTiers as $index => $lemonSqueezyTier) {
                    $planTier = $planTiers[$index];

                    if ($lemonSqueezyTier['unit_price_decimal'] != $planTier[PlanPriceTierConstants::PER_UNIT]) {
                        throw new Exception(sprintf('Price Tier mismatch. Lemon Squeezy price: %d, Plan price: %d', $lemonSqueezyTier['unit_price_decimal'], $planTier[PlanPriceTierConstants::PER_UNIT]));
                    }

                    if ($lemonSqueezyTier['last_unit'] != ($planTier[PlanPriceTierConstants::UNTIL_UNIT] === '∞' ? 'inf' : $planTier[PlanPriceTierConstants::UNTIL_UNIT])) {
                        throw new Exception(sprintf('Price Tier up_to mismatch. Lemon Squeezy up_to: %d, Plan up_to: %d', $lemonSqueezyTier['last_unit'], $planTier[PlanPriceTierConstants::UNTIL_UNIT]));
                    }

                    if ($lemonSqueezyTier['fixed_fee'] != $planTier[PlanPriceTierConstants::FLAT_FEE]) {
                        throw new Exception(sprintf('Price Tier flat fee mismatch. Lemon Squeezy flat fee: %d, Plan flat fee: %d', $lemonSqueezyTier['fixed_fee'], $planTier[PlanPriceTierConstants::FLAT_FEE]));
                    }
                }
            }

            if ($variantPriceModelResponse['data']['attributes']['usage_aggregation'] !== 'sum') {
                throw new Exception('You need to select "sum of usage during period" when defining metered-usage pricing in Lemon Squeezy.');
            }
        }

        if ($plan->interval->slug != $response['data']['attributes']['interval']) {
            throw new Exception(sprintf('Interval mismatch. Plan interval: %s, Lemon Squeezy interval: %s', $plan->interval->slug, $response['data']['attributes']['interval']));
        }

        if ($plan->interval_count != $response['data']['attributes']['interval_count']) {
            throw new Exception(sprintf('Interval count mismatch. Plan interval count: %s, Lemon Squeezy interval count: %s', $plan->interval_count, $response['data']['attributes']['interval_count']));
        }

        if ($plan->has_trial != $response['data']['attributes']['has_free_trial']) {
            throw new Exception(sprintf('Has trial mismatch. Plan has trial: %s, Lemon Squeezy has trial: %s', $plan->has_trial, $response['data']['attributes']['has_free_trial']));
        }

        if ($plan->has_trial && $plan->trialInterval->slug != $response['data']['attributes']['trial_interval']) {
            throw new Exception(sprintf('Trial interval mismatch. Plan trial interval: %s, Lemon Squeezy trial interval: %s', $plan->trialInterval->slug, $response['data']['attributes']['trial_interval']));
        }

        if ($plan->has_trial && $plan->trial_interval_count != $response['data']['attributes']['trial_interval_count']) {
            throw new Exception(sprintf('Trial interval count mismatch. Plan trial interval count: %s, Lemon Squeezy trial interval count: %s', $plan->trial_interval_count, $response['data']['attributes']['trial_interval_count']));
        }

        if (! $response['data']['attributes']['is_subscription']) {
            throw new Exception('Lemon Squeezy product is not a subscription.');
        }

        return true;
    }

    public function validateOneTimeProduct(string $variantId, OneTimeProduct $oneTimeProduct): bool
    {
        $response = $this->client->getVariant($variantId);

        $price = $this->calculationService->getOneTimeProductPrice($oneTimeProduct);

        if (! $response->successful()) {
            throw new Exception('Failed to validate product with Lemon Squeezy.');
        }

        if ($price->price != $response['data']['attributes']['price']) {
            throw new Exception(sprintf('Price mismatch. One time product price: %d, Lemon Squeezy price: %d', $price->price, $response['data']['attributes']['price']));
        }

        if ($response['data']['attributes']['is_subscription']) {
            throw new Exception('Lemon Squeezy product is a subscription.');
        }

        return true;
    }
}
