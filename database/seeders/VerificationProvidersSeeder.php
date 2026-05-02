<?php

namespace Database\Seeders;

use App\Constants\VerificationProviderConstants;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VerificationProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('verification_providers')->upsert([
            [
                'name' => 'Twilio',
                'slug' => VerificationProviderConstants::TWILIO_SLUG,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['slug']);

    }
}
