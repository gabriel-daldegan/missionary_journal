<?php

namespace App\Client;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CreemClient
{
    public function createCheckout(array $params): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/checkouts'), $params);
    }

    public function getSubscription(string $id): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/subscriptions'), ['subscription_id' => $id]);
    }

    public function cancelSubscription(string $id): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/subscriptions/'.$id.'/cancel'), [
            'mode' => 'scheduled',
        ]);
    }

    public function pauseSubscription(string $id): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/subscriptions/'.$id.'/pause'));
    }

    public function resumeSubscription(string $id): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/subscriptions/'.$id.'/resume'));
    }

    public function updateSubscription(string $id, array $items, string $updateBehavior = 'proration-charge'): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/subscriptions/'.$id), [
            'items' => $items,
            'update_behavior' => $updateBehavior,
        ]);
    }

    public function upgradeSubscription(string $id, string $productId, string $updateBehavior = 'proration-charge-immediately'): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/subscriptions/'.$id.'/upgrade'), [
            'product_id' => $productId,
            'update_behavior' => $updateBehavior,
        ]);
    }

    public function createDiscount(array $params): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/discounts'), $params);
    }

    public function getDiscount(string $id): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/discounts'), ['id' => $id]);
    }

    public function getProduct(string $productId): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/products'), ['product_id' => $productId]);
    }

    public function getCustomerBillingPortal(string $customerId): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/customers/billing'), [
            'customer_id' => $customerId,
        ]);
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'x-api-key' => config('services.creem.api_key'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
    }

    private function getApiUrl(string $endpoint): string
    {
        $baseUrl = config('services.creem.is_test_mode')
            ? 'https://test-api.creem.io'
            : 'https://api.creem.io';

        return $baseUrl.$endpoint;
    }
}
