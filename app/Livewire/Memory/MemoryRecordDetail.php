<?php

namespace App\Livewire\Memory;

use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;

class MemoryRecordDetail extends Component
{
    public Tenant $tenant;

    public MemoryRecord $record;

    public function mount(Tenant $tenant, MemoryRecord $record): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null || $record->tenant_id !== $tenant->id || ! $user->can('view', $record)) {
            abort(404);
        }

        $this->tenant = $tenant;
        $this->record = $record->load([
            'author',
            'highlights',
            'lastEditor',
            'tags',
        ]);
    }

    public function render(): View
    {
        return view('livewire.memory.memory-record-detail')
            ->layout('components.layouts.memory', [
                'tenant' => $this->tenant,
                'title' => __('memory.record_detail.title'),
            ]);
    }
}
