<?php

namespace App\Services;

use App\Events\Team\UserJoinedTeam;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

class TeamService
{
    public function getUserTeams(User $user, Tenant $tenant): Collection
    {
        $tenantUser = $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot;

        if (! $tenantUser) {
            return collect();
        }

        return $tenantUser->teams()->get();
    }

    public function addUserToTeam(User $user, Team $team, Tenant $tenant): bool
    {
        $tenantUser = $user->tenants()->where('tenant_id', $tenant->id)?->first()?->pivot;

        if (! $tenantUser) {
            return false;
        }

        $team->tenantUsers()->attach($tenantUser->id);

        $this->userJoinedTeam($user, $team, $tenant);

        return true;
    }

    public function userJoinedTeam(User $user, Team $team, Tenant $tenant)
    {
        UserJoinedTeam::dispatch($user, $team, $tenant);
    }
}
