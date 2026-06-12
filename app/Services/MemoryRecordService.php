<?php

namespace App\Services;

use App\Models\MemoryRecord;
use App\Models\MemoryTag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class MemoryRecordService
{
    /**
     * @var array<int, string>
     */
    private const DEFAULT_PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDiaryRecord(Tenant $tenant, User $author, array $data): MemoryRecord
    {
        Gate::forUser($author)->authorize('create', [MemoryRecord::class, $tenant]);

        $validated = $this->validateDiaryRecordData($data);

        return DB::transaction(function () use ($tenant, $author, $validated): MemoryRecord {
            $record = new MemoryRecord([
                'type' => MemoryRecord::TYPE_DIARY,
                'body' => $validated['body'],
                'experience_date' => $validated['experience_date'],
                'location_name' => $this->normalizeOptionalText($validated['location_name'] ?? null),
                'people' => [],
                'source' => null,
                'source_metadata' => null,
            ]);

            $record->forceFill([
                'uuid' => (string) Str::uuid(),
            ]);
            $record->tenant()->associate($tenant);
            $record->author()->associate($author);
            $record->lastEditor()->associate($author);
            $record->save();

            $record->tags()->sync($this->resolveTagIds($tenant, $validated['tags'] ?? []));
            $this->createHighlights($record, $validated['highlights'] ?? []);
            $this->attachPhotos($record, $tenant, $validated['photos'] ?? []);

            return $record->refresh()->load([
                'author',
                'highlights',
                'lastEditor',
                'media',
                'tags',
                'tenant',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPeriodRecord(Tenant $tenant, User $author, array $data): MemoryRecord
    {
        Gate::forUser($author)->authorize('create', [MemoryRecord::class, $tenant]);

        $validated = $this->validatePeriodRecordData($data);

        return DB::transaction(function () use ($tenant, $author, $validated): MemoryRecord {
            $record = new MemoryRecord([
                'type' => MemoryRecord::TYPE_PERIOD,
                'title' => $validated['title'],
                'notes' => $this->normalizeOptionalText($validated['notes'] ?? null),
                'experience_date' => null,
                'period_start_date' => $validated['period_start_date'],
                'period_end_date' => $validated['period_end_date'],
                'location_name' => $this->normalizeOptionalText($validated['location_name'] ?? null),
                'people' => $validated['people'] ?? [],
                'source' => null,
                'source_metadata' => null,
            ]);

            $record->forceFill([
                'uuid' => (string) Str::uuid(),
            ]);
            $record->tenant()->associate($tenant);
            $record->author()->associate($author);
            $record->lastEditor()->associate($author);
            $record->save();

            $record->tags()->sync($this->resolveTagIds($tenant, $validated['tags'] ?? []));
            $this->createHighlights($record, $validated['highlights'] ?? []);
            $this->attachPhotos($record, $tenant, $validated['photos'] ?? []);

            return $record->refresh()->load([
                'author',
                'highlights',
                'lastEditor',
                'media',
                'tags',
                'tenant',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDiaryRecord(MemoryRecord $record, Tenant $tenant, User $author, array $data): MemoryRecord
    {
        if ($record->tenant_id !== $tenant->id) {
            throw new AuthorizationException;
        }

        Gate::forUser($author)->authorize('update', $record);

        $validated = $this->validateDiaryRecordData($data);

        return DB::transaction(function () use ($record, $tenant, $author, $validated): MemoryRecord {
            $record->fill([
                'body' => $validated['body'],
                'experience_date' => $validated['experience_date'],
                'location_name' => $this->normalizeOptionalText($validated['location_name'] ?? null),
            ]);
            $record->lastEditor()->associate($author);
            $record->save();

            $record->tags()->sync($this->resolveTagIds($tenant, $validated['tags'] ?? []));
            $record->highlights()->delete();
            $this->createHighlights($record, $validated['highlights'] ?? []);

            return $record->refresh()->load([
                'author',
                'highlights',
                'lastEditor',
                'media',
                'tags',
                'tenant',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{body: string, experience_date: string, location_name?: string|null, tags?: array<int, string|null>, highlights?: array<int, string|null>, photos?: array<int, UploadedFile>}
     */
    private function validateDiaryRecordData(array $data): array
    {
        $payload = [
            'body' => $data['body'] ?? null,
            'experience_date' => $data['experience_date'] ?? null,
            'location_name' => $data['location_name'] ?? null,
            'tags' => $data['tags'] ?? [],
            'highlights' => $this->normalizeHighlightInput($data['highlights'] ?? []),
            'photos' => $this->normalizePhotoInput($data['photos'] ?? []),
        ];

        /** @var array{body: string, experience_date: string, location_name?: string|null, tags?: array<int, string|null>, highlights?: array<int, string|null>, photos?: array<int, UploadedFile>} $validated */
        $validated = Validator::make($payload, [
            'body' => ['required', 'string', 'max:20000'],
            'experience_date' => ['required', 'date'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'tags' => ['array', 'max:20'],
            'tags.*' => ['nullable', 'string', 'max:80'],
            'highlights' => ['array', 'max:20'],
            'highlights.*' => ['nullable', 'string', 'max:500'],
            'photos' => ['array', 'max:'.$this->maxPhotosPerRecord()],
            'photos.*' => ['file', File::image()->types($this->allowedPhotoExtensions())->max($this->maxImageSizeKilobytes())],
        ])->validate();

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{title: string, period_start_date: string, period_end_date: string, location_name?: string|null, notes?: string|null, people?: array<int, string>, tags?: array<int, string|null>, highlights?: array<int, string|null>, photos?: array<int, UploadedFile>}
     */
    private function validatePeriodRecordData(array $data): array
    {
        $payload = [
            'title' => $this->normalizeOptionalText(is_string($data['title'] ?? null) ? $data['title'] : null),
            'period_start_date' => $data['period_start_date'] ?? null,
            'period_end_date' => $data['period_end_date'] ?? null,
            'location_name' => $data['location_name'] ?? null,
            'notes' => $data['notes'] ?? null,
            'people' => $this->normalizeStringList($data['people'] ?? []),
            'tags' => $data['tags'] ?? [],
            'highlights' => $this->normalizeHighlightInput($data['highlights'] ?? []),
            'photos' => $this->normalizePhotoInput($data['photos'] ?? []),
        ];

        /** @var array{title: string, period_start_date: string, period_end_date: string, location_name?: string|null, notes?: string|null, people?: array<int, string>, tags?: array<int, string|null>, highlights?: array<int, string|null>, photos?: array<int, UploadedFile>} $validated */
        $validated = Validator::make($payload, [
            'title' => ['required', 'string', 'max:255'],
            'period_start_date' => ['required', 'date'],
            'period_end_date' => ['required', 'date', 'after_or_equal:period_start_date'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'people' => ['array', 'max:30'],
            'people.*' => ['nullable', 'string', 'max:120'],
            'tags' => ['array', 'max:20'],
            'tags.*' => ['nullable', 'string', 'max:80'],
            'highlights' => ['array', 'max:20'],
            'highlights.*' => ['nullable', 'string', 'max:500'],
            'photos' => ['array', 'max:'.$this->maxPhotosPerRecord()],
            'photos.*' => ['file', File::image()->types($this->allowedPhotoExtensions())->max($this->maxImageSizeKilobytes())],
        ])->validate();

        return $validated;
    }

    /**
     * @return array<int, string|null>
     */
    private function normalizeHighlightInput(mixed $highlights): array
    {
        if (! is_array($highlights)) {
            return [];
        }

        return collect($highlights)
            ->map(function (mixed $highlight): ?string {
                if (is_array($highlight)) {
                    $highlight = $highlight['text'] ?? null;
                }

                if (! is_string($highlight)) {
                    return null;
                }

                return $highlight;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string|null>  $tagNames
     * @return array<int, int>
     */
    private function resolveTagIds(Tenant $tenant, array $tagNames): array
    {
        return collect($tagNames)
            ->map(fn (?string $tagName): ?string => $this->normalizeOptionalText($tagName))
            ->filter()
            ->map(fn (string $tagName): array => [
                'name' => Str::limit($tagName, 80, ''),
                'slug' => Str::slug($tagName),
            ])
            ->filter(fn (array $tag): bool => $tag['slug'] !== '')
            ->unique('slug')
            ->map(function (array $tag) use ($tenant): int {
                /** @var MemoryTag $memoryTag */
                $memoryTag = Model::unguarded(fn (): MemoryTag => MemoryTag::query()->createOrFirst(
                    [
                        'tenant_id' => $tenant->id,
                        'slug' => $tag['slug'],
                    ],
                    [
                        'name' => $tag['name'],
                    ],
                ));

                return $memoryTag->id;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string|null>  $highlightTexts
     */
    private function createHighlights(MemoryRecord $record, array $highlightTexts): void
    {
        collect($highlightTexts)
            ->map(fn (?string $text): ?string => $this->normalizeOptionalText($text))
            ->filter()
            ->values()
            ->each(function (string $text, int $index) use ($record): void {
                $record->highlights()->create([
                    'text' => $text,
                    'sort_order' => $index,
                ]);
            });
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    private function attachPhotos(MemoryRecord $record, Tenant $tenant, array $photos): void
    {
        if ($photos === []) {
            return;
        }

        $this->ensurePhotoLimits($tenant, $photos);

        /** @var array<int, Media> $attachedMedia */
        $attachedMedia = [];

        try {
            foreach ($photos as $photo) {
                $attachedMedia[] = $record
                    ->addMedia($photo)
                    ->usingFileName($photo->hashName())
                    ->toMediaCollection($record->mediaCollectionName(), $record->mediaDiskName());
            }
        } catch (Throwable $exception) {
            foreach ($attachedMedia as $media) {
                $media->delete();
            }

            throw $exception;
        }
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    private function ensurePhotoLimits(Tenant $tenant, array $photos): void
    {
        $uploadBytes = collect($photos)
            ->sum(fn (UploadedFile $photo): int => (int) $photo->getSize());

        if ($this->tenantMemoryMediaBytes($tenant) + $uploadBytes > $this->workspaceStorageCapBytes()) {
            throw ValidationException::withMessages([
                'photos' => __('memory.record_editor.photos_workspace_limit'),
            ]);
        }
    }

    private function tenantMemoryMediaBytes(Tenant $tenant): int
    {
        return (int) Media::query()
            ->where('model_type', (new MemoryRecord)->getMorphClass())
            ->where('collection_name', (new MemoryRecord)->mediaCollectionName())
            ->whereIn('model_id', MemoryRecord::query()
                ->select('id')
                ->whereBelongsTo($tenant))
            ->sum('size');
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function normalizePhotoInput(mixed $photos): array
    {
        if ($photos instanceof UploadedFile) {
            return [$photos];
        }

        if (! is_array($photos)) {
            return [];
        }

        return collect($photos)
            ->filter(fn (mixed $photo): bool => $photo instanceof UploadedFile)
            ->values()
            ->all();
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
        return max(1, (int) config('memory.media.max_image_size_kilobytes', 10 * 1024));
    }

    private function maxPhotosPerRecord(): int
    {
        return max(1, (int) config('memory.media.max_photos_per_record', 25));
    }

    private function workspaceStorageCapBytes(): int
    {
        return (int) config('memory.media.workspace_storage_cap_bytes', 2 * 1024 * 1024 * 1024);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $values): array
    {
        if (is_string($values)) {
            $values = explode(',', $values);
        }

        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->map(fn (mixed $value): ?string => is_string($value) ? $this->normalizeOptionalText($value) : null)
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = Str::squish($value);

        if ($value === '') {
            return null;
        }

        return $value;
    }
}
