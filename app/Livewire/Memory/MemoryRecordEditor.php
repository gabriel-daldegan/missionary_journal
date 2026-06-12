<?php

namespace App\Livewire\Memory;

use App\Models\MemoryHighlight;
use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MemoryRecordService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class MemoryRecordEditor extends Component
{
    use WithFileUploads;

    /**
     * @var array<int, string>
     */
    private const DEFAULT_PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public Tenant $tenant;

    public ?MemoryRecord $record = null;

    public string $type = MemoryRecord::TYPE_DIARY;

    public bool $isEditing = false;

    public string $body = '';

    public string $title = '';

    public string $experienceDate = '';

    public string $periodStartDate = '';

    public string $periodEndDate = '';

    public ?string $locationName = null;

    public ?string $notes = null;

    public string $peopleInput = '';

    public string $tagInput = '';

    /**
     * @var array<int, mixed>
     */
    public array $photos = [];

    /**
     * @var array<int, array{uid: string, text: string}>
     */
    public array $highlights = [];

    public function mount(Tenant $tenant, string $type = MemoryRecord::TYPE_DIARY): void
    {
        if (! in_array($type, MemoryRecord::ACTIVE_TYPES, true)) {
            abort(404);
        }

        $user = $this->ensureAuthenticatedTenantMember($tenant);

        $this->tenant = $tenant;
        $this->type = $type;
        $this->experienceDate = now()->toDateString();
        $this->periodStartDate = now()->toDateString();
        $this->periodEndDate = now()->toDateString();
        $this->highlights = [$this->newHighlight()];

        if ($this->record !== null) {
            $this->mountRecordForEditing($this->record, $tenant, $user);
        }
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

        $user = $this->ensureAuthenticatedTenantMember($this->tenant);

        if ($this->isEditing && $this->record !== null) {
            $payload = [
                'body' => $validated['body'],
                'experience_date' => $validated['experienceDate'],
                'location_name' => $validated['locationName'] ?? null,
                'tags' => $this->tagNames(),
                'highlights' => $this->highlightPayload($validated['highlights'] ?? []),
            ];
            $record = $memoryRecordService->updateDiaryRecord($this->record, $this->tenant, $user, $payload);

            $this->redirectRoute('memories.records.show', [
                'tenant' => $this->tenant,
                'record' => $record,
            ]);

            return;
        }

        if ($this->type === MemoryRecord::TYPE_PERIOD) {
            $memoryRecordService->createPeriodRecord($this->tenant, $user, [
                'title' => $validated['title'],
                'period_start_date' => $validated['periodStartDate'],
                'period_end_date' => $validated['periodEndDate'],
                'location_name' => $validated['locationName'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'people' => $this->peopleNames(),
                'tags' => $this->tagNames(),
                'highlights' => $this->highlightPayload($validated['highlights'] ?? []),
                'photos' => $validated['photos'] ?? [],
            ]);
        } else {
            $memoryRecordService->createDiaryRecord($this->tenant, $user, [
                'body' => $validated['body'],
                'experience_date' => $validated['experienceDate'],
                'location_name' => $validated['locationName'] ?? null,
                'tags' => $this->tagNames(),
                'highlights' => $this->highlightPayload($validated['highlights'] ?? []),
                'photos' => $validated['photos'] ?? [],
            ]);
        }

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
                'title' => $this->isEditing
                    ? __('memory.record_editor.edit_title')
                    : $this->createPageTitle(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $sharedRules = [
            'locationName' => ['nullable', 'string', 'max:255'],
            'tagInput' => ['nullable', 'string', 'max:500'],
            'highlights' => ['array', 'max:20'],
            'highlights.*.uid' => ['required', 'string', 'max:64'],
            'highlights.*.text' => ['nullable', 'string', 'max:500'],
        ];

        if (! $this->isEditing) {
            $sharedRules += [
                'photos' => ['array', 'max:'.$this->maxPhotosPerRecord()],
                'photos.*' => ['file', File::image()->types($this->allowedPhotoExtensions())->max($this->maxImageSizeKilobytes())],
            ];
        }

        if ($this->type === MemoryRecord::TYPE_PERIOD && ! $this->isEditing) {
            return $sharedRules + [
                'title' => ['required', 'string', 'max:255'],
                'periodStartDate' => ['required', 'date'],
                'periodEndDate' => ['required', 'date', 'after_or_equal:periodStartDate'],
                'notes' => ['nullable', 'string', 'max:10000'],
                'peopleInput' => ['nullable', 'string', 'max:1000'],
            ];
        }

        return $sharedRules + [
            'body' => ['required', 'string', 'max:20000'],
            'experienceDate' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'body' => __('memory.record_editor.body'),
            'title' => __('memory.record_editor.period_title_label'),
            'experienceDate' => __('memory.record_editor.experience_date'),
            'periodStartDate' => __('memory.record_editor.period_start_date'),
            'periodEndDate' => __('memory.record_editor.period_end_date'),
            'locationName' => __('memory.record_editor.location_name'),
            'notes' => __('memory.record_editor.notes'),
            'peopleInput' => __('memory.record_editor.people'),
            'tagInput' => __('memory.record_editor.tags'),
            'highlights.*.text' => __('memory.record_editor.highlight_text'),
            'photos' => __('memory.record_editor.photos'),
            'photos.*' => __('memory.record_editor.photo'),
        ];
    }

    public function removePhoto(int $index): void
    {
        if (! array_key_exists($index, $this->photos)) {
            return;
        }

        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
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
     * @return array<int, string>
     */
    private function peopleNames(): array
    {
        return collect(explode(',', $this->peopleInput))
            ->map(fn (string $person): string => trim($person))
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

    private function mountRecordForEditing(MemoryRecord $record, Tenant $tenant, User $user): void
    {
        if ($record->tenant_id !== $tenant->id || $record->type !== MemoryRecord::TYPE_DIARY || ! $user->can('update', $record)) {
            abort(404);
        }

        $record->load(['highlights', 'tags']);

        $this->record = $record;
        $this->isEditing = true;
        $this->type = MemoryRecord::TYPE_DIARY;
        $this->body = $record->body ?? '';
        $this->experienceDate = $record->experience_date?->toDateString() ?? now()->toDateString();
        $this->locationName = $record->location_name;
        $this->tagInput = $record->tags
            ->pluck('name')
            ->implode(', ');
        $this->highlights = $record->highlights
            ->map(fn (MemoryHighlight $highlight): array => [
                'uid' => (string) Str::uuid(),
                'text' => $highlight->text,
            ])
            ->values()
            ->all();

        if ($this->highlights === []) {
            $this->highlights[] = $this->newHighlight();
        }
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

    private function createPageTitle(): string
    {
        if ($this->type === MemoryRecord::TYPE_PERIOD) {
            return __('memory.record_editor.period_title');
        }

        return __('memory.record_editor.title');
    }

    /**
     * @return array<int, string>
     */
    private function allowedPhotoExtensions(): array
    {
        $extensions = config('memory.media.allowed_extensions', self::DEFAULT_PHOTO_EXTENSIONS);

        if (! is_array($extensions)) {
            return self::DEFAULT_PHOTO_EXTENSIONS;
        }

        $extensions = array_values(array_filter(
            $extensions,
            fn (mixed $extension): bool => is_string($extension) && $extension !== '',
        ));

        if ($extensions === []) {
            return self::DEFAULT_PHOTO_EXTENSIONS;
        }

        return $extensions;
    }

    private function maxImageSizeKilobytes(): int
    {
        return (int) config('memory.media.max_image_size_kilobytes', 10 * 1024);
    }

    private function maxPhotosPerRecord(): int
    {
        return max(1, (int) config('memory.media.max_photos_per_record', 25));
    }
}
