<?php

namespace App\Livewire\Roadmap;

use App\Services\RoadmapService;
use Livewire\Component;

class View extends Component
{
    public $slug;

    public function render(RoadmapService $roadmapService)
    {
        return view(
            'livewire.roadmap.view', [
                'item' => $roadmapService->getItemBySlug($this->slug),
            ]
        );
    }

    public function upvote(int $id, RoadmapService $roadmapService)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $roadmapService->upvote($id);
    }

    public function removeUpvote(int $id, RoadmapService $roadmapService)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $roadmapService->removeUpvote($id);
    }
}
