<?php

namespace App\Services;

use App\Models\MemoryRecord;
use App\Models\MemoryTag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MemoryRecordService
{
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

            return $record->refresh()->load([
                'author',
                'highlights',
                'lastEditor',
                'tags',
                'tenant',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{body: string, experience_date: string, location_name?: string|null, tags?: array<int, string|null>, highlights?: array<int, string|null>}
     */
    private function validateDiaryRecordData(array $data): array
    {
        $payload = [
            'body' => $data['body'] ?? null,
            'experience_date' => $data['experience_date'] ?? null,
            'location_name' => $data['location_name'] ?? null,
            'tags' => $data['tags'] ?? [],
            'highlights' => $this->normalizeHighlightInput($data['highlights'] ?? []),
        ];

        /** @var array{body: string, experience_date: string, location_name?: string|null, tags?: array<int, string|null>, highlights?: array<int, string|null>} $validated */
        $validated = Validator::make($payload, [
            'body' => ['required', 'string', 'max:20000'],
            'experience_date' => ['required', 'date'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'tags' => ['array', 'max:20'],
            'tags.*' => ['nullable', 'string', 'max:80'],
            'highlights' => ['array', 'max:20'],
            'highlights.*' => ['nullable', 'string', 'max:500'],
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
