<?php

namespace App\Filament\Admin\Resources\Discounts\Pages;

use App\Filament\Admin\Resources\Discounts\DiscountResource;
use App\Services\ReferralService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDiscount extends EditRecord
{
    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function ($record, ReferralService $referralService) {
                    if ($referralService->isDiscountUsedAsReward($record)) {
                        Notification::make()
                            ->warning()
                            ->body(__('This discount cannot be deleted because it is being used as a referral reward.'))
                            ->send();

                        $this->halt();
                    }
                }),

        ];
    }
}
