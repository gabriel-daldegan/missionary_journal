<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

class OneTimePasswordService
{
    private const RATE_LIMIT_KEY = 'one-time-password-send-code.';

    public function sendCode(User $user): bool
    {
        if ($this->rateLimitHit($user->email)) {
            return false;
        }

        $user->sendOneTimePassword();

        return true;
    }

    protected function rateLimitHit(string $email): bool
    {
        $rateLimitKey = self::RATE_LIMIT_KEY.$email;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            return true;
        }

        RateLimiter::hit($rateLimitKey, 60); // 60 seconds decay time

        return false;
    }
}
