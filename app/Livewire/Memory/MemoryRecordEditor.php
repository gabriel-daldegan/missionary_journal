<?php

namespace App\Livewire\Memory;

use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MemoryRecordService;
use Illuminate\Support\Str;
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
     * @var array<int, array{uid: string, text: string}>
     */
    public array $highlights = [];

    public function mount(Tenant $tenant, string $type): void
    {
        if ($type !== MemoryRecord::TYPE_DIARY) {
            abort(404);
        }

        $this->ensureAuthenticatedTenantMember($tenant);

        $this->tenant = $tenant;
        $this->type = $type;
        $this->experienceDate = now()->toDateString();
        $this->highlights = [$this->newHighlight()];
    }

    public function addHighlight(): void
    {
        $this->ensureHighlightUids();

        if (count($this->highlights) >= 20) {
            return;
        }

        $this->highlights[] = $this->newHighlight();
    }

    public function removeHighlight(int $index): void
    {
        $this->ensureHighlightUids();

        unset($this->highlights[$index]);

        $this->highlights = array_values($this->highlights);

        if ($this->highlights === []) {
            $this->highlights[] = $this->newHighlight();
        }
    }

    public function moveHighlightUp(int $index): void
    {
        $this->ensureHighlightUids();

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
        $this->ensureHighlightUids();

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
        $this->ensureHighlightUids();

        $validated = $this->validate();

        $memoryRecordService->createDiaryRecord($this->tenant, $this->ensureAuthenticatedTenantMember($this->tenant), [
            'body' => $validated['body'],
            'experience_date' => $validated['experienceDate'],
            'location_name' => $validated['locationName'] ?? null,
            'tags' => $this->tagNames(),
            'highlights' => $this->highlightPayload($validated['highlights'] ?? []),
        ]);

        $this->redirectRoute('memories.timeline', [
            'tenant' => $this->tenant,
        ]);
    }

    public function render(): View
    {
        $this->ensureHighlightUids();

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
            'highlights.*.uid' => ['required', 'string', 'max:64'],
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

    /**
     * @param  array<int, array{uid?: string, text?: string|null}>  $highlights
     * @return array<int, array{text: string|null}>
     */
    private function highlightPayload(array $highlights): array
    {
        return collect($highlights)
            ->map(fn (array $highlight): array => [
                'text' => $highlight['text'] ?? null,
            ])
            ->values()
            ->all();
    }

    private function ensureHighlightUids(): void
    {
        $this->highlights = collect($this->highlights)
            ->map(function (mixed $highlight): array {
                if (! is_array($highlight)) {
                    return $this->newHighlight();
                }

                $uid = $highlight['uid'] ?? null;
                $text = $highlight['text'] ?? '';

                return [
                    'uid' => is_string($uid) && $uid !== '' ? $uid : (string) Str::uuid(),
                    'text' => is_string($text) ? $text : '',
                ];
            })
            ->values()
            ->all();

        if ($this->highlights === []) {
            $this->highlights[] = $this->newHighlight();
        }
    }

    /**
     * @return array{uid: string, text: string}
     */
    private function newHighlight(): array
    {
        return [
            'uid' => (string) Str::uuid(),
            'text' => '',
        ];
    }

    private function ensureAuthenticatedTenantMember(Tenant $tenant): User
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null || ! $user->canAccessTenant($tenant)) {
            abort(404);
        }

        return $user;
    }
}
