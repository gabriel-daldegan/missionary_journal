<?php

namespace App\Livewire\Memory;

use App\Models\MemoryTag;
use App\Models\Tenant;
use App\Services\MemoryTimelineService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class MemoryTimeline extends Component
{
    public Tenant $tenant;

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    #[Url(as: 'tag', except: '')]
    public string $selectedTag = '';

    public string $location = '';

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clearFilters(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->selectedTag = '';
        $this->location = '';
    }

    public function render(): View
    {
        $timelineGroups = app(MemoryTimelineService::class)->getMonthlyTimelineGroups($this->tenant, $this->filters());

        return view('livewire.memory.memory-timeline')
            ->with('timelineGroups', $timelineGroups)
            ->with('tagOptions', $this->tagOptions())
            ->with('activeFilterLabels', $this->activeFilterLabels())
            ->with('hasActiveFilters', $this->hasActiveFilters())
            ->layout('components.layouts.memory', [
                'tenant' => $this->tenant,
                'title' => __('memory.timeline.title'),
            ]);
    }

    /**
     * @return array{date_from: string|null, date_to: string|null, tag: string|null, location: string|null}
     */
    private function filters(): array
    {
        return [
            'date_from' => $this->blankToNull($this->dateFrom),
            'date_to' => $this->blankToNull($this->dateTo),
            'tag' => $this->blankToNull($this->selectedTag),
            'location' => $this->blankToNull($this->location),
        ];
    }

    /**
     * @return Collection<int, MemoryTag>
     */
    private function tagOptions(): Collection
    {
        /** @var Collection<int, MemoryTag> $tags */
        $tags = MemoryTag::query()
            ->whereBelongsTo($this->tenant)
            ->orderBy('name')
            ->get(['id', 'tenant_id', 'name', 'slug']);

        return $tags;
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    private function activeFilterLabels(): array
    {
        $labels = [];

        if ($this->blankToNull($this->dateFrom) !== null && $this->blankToNull($this->dateTo) !== null) {
            $labels[] = [
                'key' => 'date-range',
                'label' => __('memory.timeline.filters.active.date_range', [
                    'from' => $this->dateFrom,
                    'to' => $this->dateTo,
                ]),
            ];
        } elseif ($this->blankToNull($this->dateFrom) !== null) {
            $labels[] = [
                'key' => 'date-from',
                'label' => __('memory.timeline.filters.active.date_from', [
                    'date' => $this->dateFrom,
                ]),
            ];
        } elseif ($this->blankToNull($this->dateTo) !== null) {
            $labels[] = [
                'key' => 'date-to',
                'label' => __('memory.timeline.filters.active.date_to', [
                    'date' => $this->dateTo,
                ]),
            ];
        }

        $selectedTag = $this->selectedTagOption();

        if ($selectedTag instanceof MemoryTag) {
            $labels[] = [
                'key' => 'tag',
                'label' => __('memory.timeline.filters.active.tag', [
                    'tag' => $selectedTag->name,
                ]),
            ];
        }

        if ($this->blankToNull($this->location) !== null) {
            $labels[] = [
                'key' => 'location',
                'label' => __('memory.timeline.filters.active.location', [
                    'location' => $this->location,
                ]),
            ];
        }

        return $labels;
    }

    private function selectedTagOption(): ?MemoryTag
    {
        $selectedTag = $this->blankToNull($this->selectedTag);

        if ($selectedTag === null) {
            return null;
        }

        return MemoryTag::query()
            ->whereBelongsTo($this->tenant)
            ->where('slug', $selectedTag)
            ->first(['id', 'tenant_id', 'name', 'slug']);
    }

    private function hasActiveFilters(): bool
    {
        return $this->blankToNull($this->dateFrom) !== null
            || $this->blankToNull($this->dateTo) !== null
            || $this->blankToNull($this->selectedTag) !== null
            || $this->blankToNull($this->location) !== null;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
