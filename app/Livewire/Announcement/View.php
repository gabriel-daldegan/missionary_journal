<?php

namespace App\Livewire\Announcement;

use App\Constants\AnnouncementPlacement;
use App\Constants\SessionConstants;
use App\Services\AnnouncementService;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class View extends Component
{
    public ?string $placement;

    public function mount(?string $placement = null)
    {
        $this->placement = $placement;
    }

    public function dismiss($id)
    {
        if (session()->exists(SessionConstants::DISMISSED_ANNOUNCEMENTS)) {
            $dismissedAnnouncements = session()->get(SessionConstants::DISMISSED_ANNOUNCEMENTS);
        } else {
            $dismissedAnnouncements = [];
        }

        if (! in_array($id, $dismissedAnnouncements)) {
            $dismissedAnnouncements[] = $id;
        }

        session()->put(SessionConstants::DISMISSED_ANNOUNCEMENTS, $dismissedAnnouncements);

        $this->skipRender();
    }

    public function render(AnnouncementService $announcementService)
    {
        $placement = $this->placement ? AnnouncementPlacement::tryFrom($this->placement) : AnnouncementPlacement::FRONTEND;

        return view(
            'livewire.announcement.view', [
                'announcement' => $announcementService->getAnnouncement($placement),
            ]
        );
    }
}
