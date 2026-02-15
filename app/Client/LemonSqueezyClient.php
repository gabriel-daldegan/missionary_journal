<?php

namespace App\Client;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class LemonSqueezyClient
{
    public function createCheckout(array $attributes, string $variantId): Response
    {
        $testMode = config('services.lemon-squeezy.is_test_mode');
        if ($testMode) {
            $attributes['test_mode'] = true;
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.lemon-squeezy.api_key'),
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->post($this->getApiUrl('/v1/checkouts'), [
            'data' => [
                'type' => 'checkouts',
                'attributes' => $attributes,
                'relationships' => [
                    'variant' => [
                        'data' => [
                            'type' => 'variants',
                            'id' => $variantId,
                        ],
                    ],
                    'store' => [
                        'data' => [
                            'type' => 'stores',
                            'id' => config('services.lemon-squeezy.store_id'),
                        ],
                    ],
                ],
            ],
        ]);

    }

    public function updateSubscription(string $subscriptionId, string $newVariantId, bool $withProration): Response
    {
        $attributes = [
            'variant_id' => $newVariantId,
        ];

        if ($withProration) {
            $attributes['invoice_immediately'] = true;
        } else {
            $attributes['disable_prorations'] = true;
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.lemon-squeezy.api_key'),
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->patch($this->getApiUrl('/v1/subscriptions/'.$subscriptionId), [
            'data' => [
                'type' => 'subscriptions',
                'id' => $subscriptionId,
                'attributes' => $attributes,
            ],
        ]);
    }

    public function getVariant(string $variantId): Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.lemon-squeezy.api_key'),
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->get($this->getApiUrl('/v1/variants/'.$variantId));
    }

    public function getVariantPriceModel(string $variantId): Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.lemon-squeezy.api_key'),
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->get($this->getApiUrl('/v1/variants/'.$variantId.'/price-model'));
    }

    public function createDiscount(
        string $name,
        string $couponCode,
        int $amount,
        string $amountType,
        ?int $maxRedemptions = null,
        string $duration = 'forever',
        ?int $durationInMonths = null,
        ?Carbon $expiresAt = null,
    ) {
        $attributes = [
            'name' => $name,
            'code' => $couponCode,
            'amount' => $amount,
            'amount_type' => $amountType,
            'duration' => $duration,
        ];

        if ($expiresAt !== null) {
            $attributes['expires_at'] = $expiresAt->toISOString();
        }

        if ($duration === 'repeating') {
            $attributes['duration_in_months'] = $durationInMonths;
        }

        if ($maxRedemptions !== null) {
            $attributes['is_limited_redemptions'] = true;
            $attributes['max_redemptions'] = $maxRedemptions;
        }

        $testMode = config('services.lemon-squeezy.is_test_mode');
        if ($testMode) {
            $attributes['test_mode'] = true;
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.lemon-squeezy.api_key'),
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->post($this->getApiUrl('/v1/discounts'), [
            'data' => [
                'type' => 'discounts',
                'attributes' => $attributes,
                'relationships' => [
                    'store' => [
                        'data' => [
                            'type' => 'stores',
                            'id' => config('services.lemon-squeezy.store_id'),
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function getSubscription(string $subscriptionId): Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.lemon-squeezy.api_key'),
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->get($this->getApiUrl('/v1/subscriptions/'.$subscriptionId));
    }

    public function cancelSubscription(string $subscriptionId): Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.lemon-squeezy.api_key'),
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->delete($this->getApiUrl('/v1/subscriptions/'.$subscriptionId));
    }

    public function updateSubscriptionQuantity(string $subscriptionItemId, int $newQuantity, bool $withProration = true): Response
    {
        $attributes = [
            'quantity' => $newQuantity,
        ];

        if ($withProration) {
            $attributes['invoice_immediately'] = true;
        } else {
            $attributes['disable_prorations'] = true;
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.lemon-squeezy.api_key'),
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->patch($this->getApiUrl('/v1/subscription-items/'.$subscriptionItemId), [
            'data' => [
                'type' => 'subscription-items',
                'id' => $subscriptionItemId,
                'attributes' => $attributes,
            ],
        ]);
    }

    public function discardSubscriptionCancellation(string $subscriptionId): Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.lemon-squeezy.api_key'),
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->patch($this->getApiUrl('/v1/subscriptions/'.$subscriptionId), [
            'data' => [
                'type' => 'subscriptions',
                'id' => $subscriptionId,
                'attributes' => [
                    'cancelled' => false,
                ],
            ],
        ]);
    }

    public function reportUsage(string $subscriptionItemId, int $unitCount): Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.lemon-squeezy.api_key'),
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->post($this->getApiUrl('/v1/usage-records'), [
            'data' => [
                'type' => 'usage-records',
                'attributes' => [
                    'quantity' => $unitCount,
                    'action' => 'increment',
                ],
                'relationships' => [
                    'subscription-item' => [
                        'data' => [
                            'type' => 'subscription-items',
                            'id' => $subscriptionItemId,
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function getApiUrl(string $endpoint): string
    {
        return 'https://api.lemonsqueezy.com'.$endpoint;
    }
}
