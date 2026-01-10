<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\UserService;
use Tests\Feature\FeatureTest;

class UserServiceTest extends FeatureTest
{
    public function test_user_can_be_anonymized()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'public_name' => 'JohnD',
            'phone_number' => '123456789',
            'notes' => 'Some notes',
        ]);

        $user->address()->create([
            'address_line_1' => '123 Street',
            'city' => 'New York',
            'country_code' => 'US',
        ]);

        $this->assertNotNull($user->address);

        $userService = app()->make(UserService::class);
        $userService->anonymize($user);
        $user->refresh();

        $this->assertEquals("Anonymized User {$user->id}", $user->name);
        $this->assertEquals("anonymized_{$user->id}@example.com", $user->email);
        $this->assertEquals("Anonymized User {$user->id}", $user->public_name);
        $this->assertNull($user->phone_number);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->phone_number_verified_at);
        $this->assertEquals('Some notes', $user->notes); // Should remain as is
        $this->assertNull($user->address);
        $this->assertNotEquals('password', $user->password);
    }
}
