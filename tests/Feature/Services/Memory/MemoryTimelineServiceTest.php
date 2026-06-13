<?php

namespace Tests\Feature\Services\Memory;

use App\Models\MemoryRecord;
use App\Models\MemoryTag;
use App\Services\MemoryRecordService;
use App\Services\MemoryTimelineService;
use Illuminate\Support\Collection;
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

    public function test_timeline_filters_by_date_range_and_single_date_using_record_timeline_dates(): void
    {
        $tenant = $this->createTenant();

        $juneDiary = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'June diary.',
            'experience_date' => '2026-06-10',
        ]);
        $junePeriod = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_PERIOD,
            'title' => 'June period.',
            'experience_date' => null,
            'period_start_date' => '2026-06-15',
            'period_end_date' => '2026-06-18',
        ]);
        MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'July diary.',
            'experience_date' => '2026-07-01',
        ]);

        $rangeGroups = $this->timelineService()->getMonthlyTimelineGroups($tenant, [
            'date_from' => '2026-06-01',
            'date_to' => '2026-06-30',
        ]);
        $singleDateGroups = $this->timelineService()->getMonthlyTimelineGroups($tenant, [
            'date_from' => '2026-06-15',
            'date_to' => '2026-06-15',
        ]);

        $this->assertSame([$junePeriod->id, $juneDiary->id], $this->recordIds($rangeGroups));
        $this->assertSame([$junePeriod->id], $this->recordIds($singleDateGroups));
    }

    public function test_timeline_filters_by_tenant_scoped_tag_and_rejects_foreign_tag_slug(): void
    {
        $tenant = $this->createTenant();
        $foreignTenant = $this->createTenant();

        $tag = MemoryTag::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Family',
            'slug' => 'family',
        ]);
        MemoryTag::factory()->create([
            'tenant_id' => $foreignTenant->id,
            'name' => 'Foreign Family',
            'slug' => 'foreign-family',
        ]);

        $matchingRecord = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Tagged family memory.',
            'experience_date' => '2026-06-10',
        ]);
        $matchingRecord->tags()->attach($tag);

        MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Untagged memory.',
            'experience_date' => '2026-06-11',
        ]);
        MemoryRecord::factory()->create([
            'tenant_id' => $foreignTenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Foreign tagged memory.',
            'experience_date' => '2026-06-12',
        ]);

        $tenantGroups = $this->timelineService()->getMonthlyTimelineGroups($tenant, [
            'tag' => 'family',
        ]);
        $foreignSlugGroups = $this->timelineService()->getMonthlyTimelineGroups($tenant, [
            'tag' => 'foreign-family',
        ]);

        $this->assertSame([$matchingRecord->id], $this->recordIds($tenantGroups));
        $this->assertSame([], $this->recordIds($foreignSlugGroups));
    }

    public function test_timeline_filters_by_location_without_private_body_search(): void
    {
        $tenant = $this->createTenant();

        $matchingRecord = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Body does not control location filters.',
            'experience_date' => '2026-06-10',
            'location_name' => 'Curitiba',
        ]);
        MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Curitiba appears only in private body text.',
            'experience_date' => '2026-06-11',
            'location_name' => 'Recife',
        ]);

        $grouped = $this->timelineService()->getMonthlyTimelineGroups($tenant, [
            'location' => 'curit',
        ]);

        $this->assertSame([$matchingRecord->id], $this->recordIds($grouped));
    }

    public function test_clear_filter_payload_matches_full_tenant_timeline_and_preserves_eager_loading(): void
    {
        $tenant = $this->createTenant();

        $record = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Full timeline memory.',
            'experience_date' => '2026-06-10',
        ]);
        $tag = MemoryTag::factory()->create([
            'tenant_id' => $tenant->id,
            'slug' => 'family',
        ]);
        $record->tags()->attach($tag);
        $record->highlights()->create([
            'text' => 'A visible highlight.',
            'sort_order' => 0,
        ]);

        $fullGroups = $this->timelineService()->getMonthlyTimelineGroups($tenant);
        $clearedGroups = $this->timelineService()->getMonthlyTimelineGroups($tenant, [
            'date_from' => null,
            'date_to' => '',
            'tag' => null,
            'location' => '',
        ]);
        /** @var MemoryRecord $groupRecord */
        $groupRecord = $clearedGroups[0]['records']->first();

        $this->assertSame($this->recordIds($fullGroups), $this->recordIds($clearedGroups));
        $this->assertTrue($groupRecord->relationLoaded('tags'));
        $this->assertTrue($groupRecord->relationLoaded('highlights'));
        $this->assertTrue($groupRecord->relationLoaded('media'));
    }

    /**
     * @param  Collection<int, array{month_key: string, month_label: string, records: Collection<int, MemoryRecord>}>  $groups
     * @return array<int, int>
     */
    private function recordIds(Collection $groups): array
    {
        return $groups
            ->flatMap(fn (array $group): array => $group['records']->pluck('id')->all())
            ->values()
            ->all();
    }

    private function timelineService(): MemoryTimelineService
    {
        return app(MemoryTimelineService::class);
    }
}
