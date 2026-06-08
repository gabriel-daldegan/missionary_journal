<?php

namespace App\Livewire\Memory;

use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MemoryRecordService;
use Illuminate\View\View;
use Livewire\Component;

class MemoryRecordEditor extends Component
{
    public Tenant $tenant;

    public string $type = MemoryRecord::TYPE_DIARY;

    public string $body = '';

    public string $experienceDate = '';

    public ?string $locationName = null;

    public string $tagInput = '';

    /**
     * @var array<int, array{text: string}>
     */
    public array $highlights = [
        ['text' => ''],
    ];

    public function mount(Tenant $tenant, string $type): void
    {
        if ($type !== MemoryRecord::TYPE_DIARY) {
            abort(404);
        }

        $this->tenant = $tenant;
        $this->type = $type;
        $this->experienceDate = now()->toDateString();
    }

    public function addHighlight(): void
    {
        if (count($this->highlights) >= 20) {
            return;
        }

        $this->highlights[] = ['text' => ''];
    }

    public function removeHighlight(int $index): void
    {
        unset($this->highlights[$index]);

        $this->highlights = array_values($this->highlights);

        if ($this->highlights === []) {
            $this->highlights[] = ['text' => ''];
        }
    }

    public function moveHighlightUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->highlights[$index])) {
            return;
        }

        $previous = $index - 1;
        [$this->highlights[$previous], $this->highlights[$index]] = [
            $this->highlights[$index],
            $this->highlights[$previous],
        ];

        $this->highlights = array_values($this->highlights);
    }

    public function moveHighlightDown(int $index): void
    {
        if (! isset($this->highlights[$index], $this->highlights[$index + 1])) {
            return;
        }

        $next = $index + 1;
        [$this->highlights[$index], $this->highlights[$next]] = [
            $this->highlights[$next],
            $this->highlights[$index],
        ];

        $this->highlights = array_values($this->highlights);
    }

    public function save(MemoryRecordService $memoryRecordService): void
    {
        $validated = $this->validate();

        $memoryRecordService->createDiaryRecord($this->tenant, $this->authenticatedTenantMember(), [
            'body' => $validated['body'],
            'experience_date' => $validated['experienceDate'],
            'location_name' => $validated['locationName'] ?? null,
            'tags' => $this->tagNames(),
            'highlights' => $validated['highlights'] ?? [],
        ]);

        $this->redirectRoute('memories.timeline', [
            'tenant' => $this->tenant,
        ]);
    }

    public function render(): View
    {
        return view('livewire.memory.memory-record-editor')
            ->layout('components.layouts.memory', [
                'tenant' => $this->tenant,
                'title' => __('memory.record_editor.title'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:20000'],
            'experienceDate' => ['required', 'date'],
            'locationName' => ['nullable', 'string', 'max:255'],
            'tagInput' => ['nullable', 'string', 'max:500'],
            'highlights' => ['array', 'max:20'],
            'highlights.*.text' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'body' => __('memory.record_editor.body'),
            'experienceDate' => __('memory.record_editor.experience_date'),
            'locationName' => __('memory.record_editor.location_name'),
            'tagInput' => __('memory.record_editor.tags'),
            'highlights.*.text' => __('memory.record_editor.highlight_text'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function tagNames(): array
    {
        return collect(explode(',', $this->tagInput))
            ->map(fn (string $tag): string => trim($tag))
            ->filter()
            ->values()
            ->all();
    }

    private function authenticatedTenantMember(): User
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null || ! $user->canAccessTenant($this->tenant)) {
            abort(404);
        }

        return $user;
    }
}
