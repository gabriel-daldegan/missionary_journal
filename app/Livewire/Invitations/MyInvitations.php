<?php

namespace App\Livewire\Invitations;

use App\Models\Invitation;
use App\Services\TenantService;
use App\Services\UserDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class MyInvitations extends Component
{
    public function render(TenantService $tenantService): View
    {
        return view('livewire.invitations.my-invitations', [
            'invitations' => $tenantService->getUserInvitations(auth()->user()),
        ]);
    }

    public function acceptInvitation(string $invitationUuid, TenantService $tenantService, UserDashboardService $userDashboardService)
    {
        $invitation = Invitation::where('uuid', $invitationUuid)->firstOrFail();
        $result = $tenantService->acceptInvitation($invitation, auth()->user());

        if ($result === false) {
            throw ValidationException::withMessages([
                'invitation' => __('You cannot accept this invitation, please contact support.'),
            ]);
        }

        return redirect($userDashboardService->getUserDashboardUrl(auth()->user()));
    }

    public function rejectInvitation(string $invitationUuid, TenantService $tenantService)
    {
        $invitation = Invitation::where('uuid', $invitationUuid)->firstOrFail();
        $tenantService->rejectInvitation($invitation, auth()->user());
    }
}
