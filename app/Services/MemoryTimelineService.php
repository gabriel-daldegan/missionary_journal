<?php

namespace App\Services;

use App\Models\MemoryRecord;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class MemoryTimelineService
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_TYPES = [
        MemoryRecord::TYPE_DIARY,
        MemoryRecord::TYPE_PERIOD,
    ];

    /**
     * @param  array{
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     tag?: string|null,
     *     location?: string|null
     * }  $filters
     * @return Collection<int, array{
     *     month_key: string,
     *     month_label: string,
     *     records: Collection<int, MemoryRecord>
     * }>
     */
    public function getMonthlyTimelineGroups(Tenant $tenant, array $filters = []): Collection
    {
        $dateFrom = $this->normalizeDate($filters['date_from'] ?? null);
        $dateTo = $this->normalizeDate($filters['date_to'] ?? null);
        $tag = $this->normalizeText($filters['tag'] ?? null);
        $location = $this->normalizeText($filters['location'] ?? null);

        /** @var Collection<int, MemoryRecord> $records */
        $records = MemoryRecord::query()
            ->whereBelongsTo($tenant)
            ->whereIn('type', self::ALLOWED_TYPES)
            ->when($dateFrom !== null || $dateTo !== null, function (Builder $query) use ($dateFrom, $dateTo): void {
                $query->where(function (Builder $query) use ($dateFrom, $dateTo): void {
                    $this->applyTimelineDateFilter($query, MemoryRecord::TYPE_DIARY, 'experience_date', $dateFrom, $dateTo);
                    $this->applyTimelineDateFilter($query, MemoryRecord::TYPE_PERIOD, 'period_start_date', $dateFrom, $dateTo, or: true);
                });
            })
            ->when($tag !== null, function (Builder $query) use ($tenant, $tag): void {
                $query->whereHas('tags', function (Builder $query) use ($tenant, $tag): void {
                    $query
                        ->whereBelongsTo($tenant)
                        ->where('slug', $tag);
                });
            })
            ->when($location !== null, function (Builder $query) use ($location): void {
                $query->where('location_name', 'like', '%'.$this->escapeLike($location).'%');
            })
            ->with(['highlights', 'media', 'tags'])
            ->get();

        return $records
            ->filter(fn (MemoryRecord $record): bool => $this->resolveTimelineDate($record) !== null)
            ->sort(function (MemoryRecord $firstRecord, MemoryRecord $secondRecord): int {
                $firstDate = $this->resolveTimelineDate($firstRecord);
                $secondDate = $this->resolveTimelineDate($secondRecord);

                if ($firstDate === null || $secondDate === null) {
                    return 0;
                }

                $dateComparison = $secondDate->getTimestamp() <=> $firstDate->getTimestamp();

                if ($dateComparison !== 0) {
                    return $dateComparison;
                }

                return $secondRecord->id <=> $firstRecord->id;
            })
            ->values()
            ->groupBy(function (MemoryRecord $record): string {
                $timelineDate = $this->resolveTimelineDate($record);

                return $timelineDate?->format('Y-m') ?? 'unknown';
            })
            ->sortKeysDesc()
            ->map(function (Collection $groupedRecords): array {
                $timelineDate = $this->resolveTimelineDate($groupedRecords->first());
                $monthLabel = $timelineDate?->locale(app()->getLocale())->translatedFormat('F Y') ?? '';

                return [
                    'month_key' => $timelineDate?->format('Y-m') ?? '',
                    'month_label' => $monthLabel,
                    'records' => $groupedRecords->values(),
                ];
            })
            ->values();
    }

    public function resolveTimelineDate(MemoryRecord $record): ?Carbon
    {
        return $record->timelineDate();
    }

    private function applyTimelineDateFilter(
        Builder $query,
        string $type,
        string $dateColumn,
        ?string $dateFrom,
        ?string $dateTo,
        bool $or = false
    ): void {
        $method = $or ? 'orWhere' : 'where';

        $query->{$method}(function (Builder $query) use ($type, $dateColumn, $dateFrom, $dateTo): void {
            $query
                ->where('type', $type)
                ->whereNotNull($dateColumn)
                ->when($dateFrom !== null, fn (Builder $query): Builder => $query->whereDate($dateColumn, '>=', $dateFrom))
                ->when($dateTo !== null, fn (Builder $query): Builder => $query->whereDate($dateColumn, '<=', $dateTo));
        });
    }

    private function normalizeDate(?string $value): ?string
    {
        $value = $this->normalizeText($value);

        if ($value === null) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date->toDateString();
    }

    private function normalizeText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
