<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->callOnce([
            IntervalsSeeder::class,
            CurrenciesSeeder::class,
            OAuthLoginProvidersSeeder::class,
            PaymentProvidersSeeder::class,
            RolesAndPermissionsSeeder::class,
            EmailProvidersSeeder::class,
            VerificationProvidersSeeder::class,
        ]);
    }
}
