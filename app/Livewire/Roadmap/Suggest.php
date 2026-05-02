<?php

namespace App\Livewire\Roadmap;

use App\Livewire\Forms\RoadmapItemForm;
use App\Services\RoadmapService;
use Livewire\Component;

class Suggest extends Component
{
    public RoadmapItemForm $form;

    private RoadmapService $roadmapService;

    // boot
    public function boot(RoadmapService $roadmapService)
    {
        $this->roadmapService = $roadmapService;
    }

    public function save()
    {
        $this->validate();

        $this->roadmapService->createItem(
            $this->form->title,
            $this->form->description,
            $this->form->type
        );

        $this->redirectRoute('roadmap');
    }

    public function render()
    {
        return view('livewire.roadmap.suggest');
    }
}
