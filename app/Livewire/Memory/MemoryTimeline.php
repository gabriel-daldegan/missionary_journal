<?php

namespace App\Livewire\Memory;

use App\Models\Tenant;
use App\Services\MemoryTimelineService;
use Illuminate\View\View;
use Livewire\Component;

class MemoryTimeline extends Component
{
    public Tenant $tenant;

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function render(): View
    {
        $timelineGroups = app(MemoryTimelineService::class)->getMonthlyTimelineGroups($this->tenant);

        return view('livewire.memory.memory-timeline')
            ->with('timelineGroups', $timelineGroups)
            ->layout('components.layouts.memory', [
                'tenant' => $this->tenant,
                'title' => __('memory.timeline.title'),
            ]);
    }
}
