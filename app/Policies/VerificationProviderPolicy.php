<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationProvider;

class VerificationProviderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('update settings');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VerificationProvider $verificationProvider): bool
    {
        return $user->hasPermissionTo('update settings');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('update settings');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VerificationProvider $verificationProvider): bool
    {
        return $user->hasPermissionTo('update settings');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VerificationProvider $verificationProvider): bool
    {
        return $user->hasPermissionTo('update settings');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VerificationProvider $verificationProvider): bool
    {
        return $user->hasPermissionTo('update settings');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VerificationProvider $verificationProvider): bool
    {
        return $user->hasPermissionTo('update settings');
    }
}
