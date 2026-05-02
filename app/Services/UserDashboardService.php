<?php

namespace App\Services;

use App\Models\User;

class UserDashboardService
{
    public function getUserDashboardUrl(User $user): string
    {
        $tenant = $user->tenants()->orderByPivot('is_default', 'desc')->first();

        if ($tenant !== null) {
            return route('filament.dashboard.pages.dashboard', ['tenant' => $tenant]);
        }

        return route('home');
    }
}
