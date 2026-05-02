<?php

namespace App\Filament\Dashboard\Resources\Invitations\Pages;

use App\Filament\Dashboard\Resources\Invitations\InvitationResource;
use App\Services\TenantService;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateInvitation extends CreateRecord
{
    protected static string $resource = InvitationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $emails = preg_split('/[\s,]+/', $data['email'], -1, PREG_SPLIT_NO_EMPTY);
        $lastInvitation = null;

        foreach ($emails as $email) {
            $individualData = $data;
            $individualData['email'] = trim($email);
            $individualData['token'] = Str::random(60);
            $individualData['uuid'] = (string) Str::uuid();
            $individualData['expires_at'] = now()->addDays(7);
            $individualData['user_id'] = auth()->id();
            $individualData['tenant_id'] = Filament::getTenant()->id;

            $invitation = static::getModel()::create($individualData);

            /** @var TenantService $tenantService */
            $tenantService = app(TenantService::class);
            $tenantService->handleAfterInvitationCreated($invitation);

            $lastInvitation = $invitation;
        }

        return $lastInvitation;
    }
}
