<?php

namespace App\Livewire\Roadmap;

use App\Services\RoadmapService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class All extends Component
{
    use WithPagination;

    #[Url]
    public $done = false;

    public function render(RoadmapService $roadmapService)
    {
        return view('livewire.roadmap.all', [
            'items' => $this->done ? $roadmapService->getCompleted() : $roadmapService->getAll(),
        ]);
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
