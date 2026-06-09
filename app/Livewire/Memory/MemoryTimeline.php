<?php

namespace App\Livewire\Memory;

use App\Models\MemoryRecord;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
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
        /** @var Collection<int, MemoryRecord> $records */
        $records = MemoryRecord::query()
            ->whereBelongsTo($this->tenant)
            ->where('type', MemoryRecord::TYPE_DIARY)
            ->with(['highlights', 'tags'])
            ->orderByDesc('experience_date')
            ->orderByDesc('id')
            ->get();

        return view('livewire.memory.memory-timeline')
            ->with('records', $records)
            ->layout('components.layouts.memory', [
                'tenant' => $this->tenant,
                'title' => __('memory.timeline.title'),
            ]);
    }
}
