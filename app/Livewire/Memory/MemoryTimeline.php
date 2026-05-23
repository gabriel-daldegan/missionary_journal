<?php

namespace App\Livewire\Memory;

use App\Models\Tenant;
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
        return view('livewire.memory.memory-timeline')
            ->layout('components.layouts.memory', [
                'tenant' => $this->tenant,
                'title' => __('Memory Timeline'),
            ]);
    }
}
