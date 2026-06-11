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
            ->orderByRaw('COALESCE(period_start_date, experience_date) DESC')
            ->orderByDesc('id')
            ->get();

        return $records
            ->filter(fn (MemoryRecord $record): bool => $this->resolveTimelineDate($record) !== null)
            ->groupBy(fn (MemoryRecord $record): string => $this->resolveTimelineDate($record)->format('Y-m'))
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
