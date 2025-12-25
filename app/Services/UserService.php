<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function createUser(array $data, bool $dispatchRegisterEvent = false): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => isset($data['password']) ? Hash::make($data['password']) : Hash::make(Str::random(32)),
        ]);

        if ($dispatchRegisterEvent) {
            event(new Registered($user));
        }

        return $user;
    }

    public function updateUserLastSeen(User $user)
    {
        $user->last_seen_at = now();
        $user->save();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', strtolower($email))->first();
    }

    public function anonymize(User $user): void
    {
        $id = $user->id;

        $user->name = "Anonymized User $id";
        $user->email = "anonymized_$id@example.com";
        $user->public_name = "Anonymized User $id";
        $user->phone_number = null;
        $user->email_verified_at = null;
        $user->phone_number_verified_at = null;
        $user->password = Hash::make(Str::random(40));
        $user->save();

        $user->address()?->delete();
    }
}
