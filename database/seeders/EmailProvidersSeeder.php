<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmailProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('email_providers')->upsert([
            [
                'name' => 'Mailgun',
                'slug' => 'mailgun',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Postmark',
                'slug' => 'postmark',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Amazon SES',
                'slug' => 'ses',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Resend',
                'slug' => 'resend',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SMTP',
                'slug' => 'smtp',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['slug']);
    }
}
