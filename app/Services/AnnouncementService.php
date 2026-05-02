<?php

namespace App\Services;

use App\Constants\AnnouncementPlacement;
use App\Constants\SessionConstants;
use App\Models\Announcement;

class AnnouncementService
{
    public function __construct(
        private OrderService $orderService,
        private SubscriptionService $subscriptionService,
    ) {}

    public function getAnnouncement(AnnouncementPlacement $announcementPlacement): ?Announcement
    {
        $user = auth()->user();

        $dismissedAnnouncementIds = session()->get(SessionConstants::DISMISSED_ANNOUNCEMENTS, []);

        $query = Announcement::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());

        if (! empty($dismissedAnnouncementIds)) {
            $query->whereNotIn('id', $dismissedAnnouncementIds);
        }

        if ($announcementPlacement === AnnouncementPlacement::USER_DASHBOARD) {
            $query->where('show_on_user_dashboard', true);
        } elseif ($announcementPlacement === AnnouncementPlacement::FRONTEND) {
            $query->where('show_on_frontend', true);
        }

        if ($user && ($this->subscriptionService->isUserSubscribedViaAnyTenant($user) || $this->orderService->hasUserOrderedViaAnyTenant($user))) {
            $query->where('show_for_customers', true);
        }

        return $query->first();
    }
}
