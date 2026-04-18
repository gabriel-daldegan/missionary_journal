<?php

namespace App\Client;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PolarClient
{
    public function createCheckout(array $params): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/checkouts/'), $params);
    }

    public function getCheckout(string $id): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/checkouts/'.$id));
    }

    public function getSubscription(string $id): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/subscriptions/'.$id));
    }

    public function updateSubscription(string $id, array $params): Response
    {
        return $this->request()->patch($this->getApiUrl('/v1/subscriptions/'.$id), $params);
    }

    public function cancelSubscription(string $id): Response
    {
        return $this->request()->delete($this->getApiUrl('/v1/subscriptions/'.$id));
    }

    public function getOrder(string $id): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/orders/'.$id));
    }

    public function getCustomer(string $id): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/customers/'.$id));
    }

    public function getCustomerByExternalId(string $externalId): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/customers/external/'.$externalId));
    }

    public function createCustomer(array $params): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/customers/'), $params);
    }

    public function getCustomerState(string $id): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/customers/'.$id.'/state'));
    }

    public function createCustomerSession(array $params): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/customer-sessions/'), $params);
    }

    public function createProduct(array $params): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/products/'), $params);
    }

    public function getProduct(string $id): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/products/'.$id));
    }

    public function createDiscount(array $params): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/discounts/'), $params);
    }

    public function getDiscount(string $id): Response
    {
        return $this->request()->get($this->getApiUrl('/v1/discounts/'.$id));
    }

    public function createMeter(array $params): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/meters/'), $params);
    }

    public function ingestEvents(array $params): Response
    {
        return $this->request()->post($this->getApiUrl('/v1/events/ingest'), $params);
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.polar.access_token'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
    }

    private function getApiUrl(string $endpoint): string
    {
        $baseUrl = config('services.polar.is_sandbox')
            ? 'https://sandbox-api.polar.sh'
            : 'https://api.polar.sh';

        return $baseUrl.$endpoint;
    }
}
