<?php

namespace App\Services;

use App\Models\MemoryRecord;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * @return Collection<int, array{
     *     month_key: string,
     *     month_label: string,
     *     records: Collection<int, MemoryRecord>
     * }>
     */
    public function getMonthlyTimelineGroups(Tenant $tenant): Collection
    {
        /** @var Collection<int, MemoryRecord> $records */
        $records = MemoryRecord::query()
            ->whereBelongsTo($tenant)
            ->whereIn('type', self::ALLOWED_TYPES)
            ->with(['highlights', 'tags'])
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
}
