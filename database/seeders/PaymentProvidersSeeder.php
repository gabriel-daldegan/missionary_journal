<?php

namespace Database\Seeders;

use App\Constants\PaymentProviderConstants;
use App\Models\PaymentProvider;
use Illuminate\Database\Seeder;

class PaymentProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'name' => 'Stripe',
                'slug' => PaymentProviderConstants::STRIPE_SLUG,
                'type' => 'multi',
                'is_active' => true,
                'sort' => 1,
            ],
            [
                'name' => 'Paddle',
                'slug' => PaymentProviderConstants::PADDLE_SLUG,
                'type' => 'multi',
                'is_active' => true,
                'sort' => 2,
            ],
            [
                'name' => 'Lemon Squeezy',
                'slug' => PaymentProviderConstants::LEMON_SQUEEZY_SLUG,
                'type' => 'multi',
                'is_active' => true,
                'sort' => 3,
            ],
            [
                'name' => 'Creem',
                'slug' => PaymentProviderConstants::CREEM_SLUG,
                'type' => 'multi',
                'is_active' => false,
                'sort' => 4,
            ],
            [
                'name' => 'Polar',
                'slug' => PaymentProviderConstants::POLAR_SLUG,
                'type' => 'multi',
                'is_active' => false,
                'sort' => 5,
            ],
            [
                'name' => 'Offline',
                'slug' => PaymentProviderConstants::OFFLINE_SLUG,
                'type' => 'multi',
                'is_active' => false,
                'sort' => 6,
            ],
        ];

        foreach ($providers as $provider) {
            PaymentProvider::query()->firstOrCreate(
                ['slug' => $provider['slug']],
                $provider
            );
        }
    }
}
