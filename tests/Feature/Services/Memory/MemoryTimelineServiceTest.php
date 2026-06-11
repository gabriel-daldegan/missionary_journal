<?php

namespace Tests\Feature\Services\Memory;

use App\Models\MemoryRecord;
use App\Models\MemoryTag;
use App\Services\MemoryRecordService;
use App\Services\MemoryTimelineService;
use Tests\Feature\FeatureTest;

class MemoryTimelineServiceTest extends FeatureTest
{
    public function test_timeline_groups_are_tenant_scoped_and_ordered_with_tie_breaking(): void
    {
        $tenant = $this->createTenant();
        $foreignTenant = $this->createTenant();

        $this->createUser($tenant);
        $this->createUser($foreignTenant);

        $foreignRecord = MemoryRecord::factory()->create([
            'tenant_id' => $foreignTenant->id,
            'body' => 'Foreign timeline memory.',
            'experience_date' => '2026-07-01',
        ]);

        $olderSameDate = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Older same day memory.',
            'experience_date' => '2026-06-10',
        ]);

        $newerSameDate = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Newer same day memory.',
            'experience_date' => '2026-06-10',
        ]);

        MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'First month memory.',
            'experience_date' => '2026-06-05',
        ]);

        MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_PERIOD,
            'body' => 'Period memory.',
            'experience_date' => null,
            'period_start_date' => '2026-07-18',
            'period_end_date' => '2026-07-19',
        ]);

        $grouped = $this->timelineService()->getMonthlyTimelineGroups($tenant);
        $allRecordIds = $grouped->flatMap(fn (array $group): array => $group['records']->pluck('id')->all());

        $this->assertCount(2, $grouped);
        $this->assertSame('July 2026', $grouped[0]['month_label']);
        $this->assertSame('June 2026', $grouped[1]['month_label']);
        $this->assertFalse($allRecordIds->contains($foreignRecord->id));

        $recordsInJune = $grouped[1]['records']->pluck('id')->all();
        $this->assertSame([$newerSameDate->id, $olderSameDate->id], array_slice($recordsInJune, 0, 2));
    }

    public function test_timeline_records_eager_load_card_dependencies_and_keep_tags_highlights_available(): void
    {
        $tenant = $this->createTenant();

        $record = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_PERIOD,
            'body' => 'Timeline card test.',
            'period_start_date' => '2026-08-02',
            'period_end_date' => '2026-08-03',
            'experience_date' => null,
            'source_metadata' => ['photo_count' => 4],
        ]);
        $tag = MemoryTag::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Family',
            'slug' => 'family',
        ]);

        $record->tags()->attach($tag);
        $record->highlights()->create([
            'text' => 'A meaningful highlight.',
            'sort_order' => 0,
        ]);

        $grouped = $this->timelineService()->getMonthlyTimelineGroups($tenant);
        /** @var MemoryRecord $groupRecord */
        $groupRecord = $grouped[0]['records']->first();

        $this->assertSame(1, $grouped[0]['records']->count());
        $this->assertTrue($groupRecord->relationLoaded('tags'));
        $this->assertTrue($groupRecord->relationLoaded('highlights'));
        $this->assertSame(['family'], $groupRecord->tags->pluck('slug')->all());
        $this->assertSame(['A meaningful highlight.'], $groupRecord->highlights->pluck('text')->all());
        $this->assertSame(4, $groupRecord->source_metadata['photo_count']);
        $this->assertCount(1, $grouped);
    }

    public function test_timeline_date_resolution_uses_the_date_field_for_the_record_type(): void
    {
        $tenant = $this->createTenant();

        $periodRecord = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_PERIOD,
            'body' => 'Period date wins for period records.',
            'experience_date' => '2026-01-01',
            'period_start_date' => '2026-08-01',
            'period_end_date' => '2026-08-03',
        ]);

        $diaryRecord = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Experience date wins for diary records.',
            'experience_date' => '2026-04-01',
            'period_start_date' => '2026-09-01',
        ]);

        $grouped = $this->timelineService()->getMonthlyTimelineGroups($tenant);

        $this->assertSame('August 2026', $grouped[0]['month_label']);
        $this->assertSame($periodRecord->id, $grouped[0]['records']->first()->id);
        $this->assertSame('April 2026', $grouped[1]['month_label']);
        $this->assertSame($diaryRecord->id, $grouped[1]['records']->first()->id);
    }

    public function test_created_period_records_are_ordered_by_period_start_date_with_diary_records(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);

        $periodRecord = app(MemoryRecordService::class)->createPeriodRecord($tenant, $author, [
            'title' => 'Period starts later.',
            'period_start_date' => '2026-07-14',
            'period_end_date' => '2026-07-18',
        ]);
        $diaryRecord = app(MemoryRecordService::class)->createDiaryRecord($tenant, $author, [
            'body' => 'Diary earlier in July.',
            'experience_date' => '2026-07-12',
        ]);

        $grouped = $this->timelineService()->getMonthlyTimelineGroups($tenant);

        $this->assertSame('July 2026', $grouped[0]['month_label']);
        $this->assertSame([
            $periodRecord->id,
            $diaryRecord->id,
        ], $grouped[0]['records']->pluck('id')->all());
    }

    private function timelineService(): MemoryTimelineService
    {
        return app(MemoryTimelineService::class);
    }
}
