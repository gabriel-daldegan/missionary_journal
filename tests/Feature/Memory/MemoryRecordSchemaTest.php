<?php

namespace Tests\Feature\Memory;

use App\Models\MemoryHighlight;
use App\Models\MemoryRecord;
use App\Models\MemoryTag;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class MemoryRecordSchemaTest extends FeatureTest
{
    public function test_memory_record_schema_contains_expected_columns_and_boundaries(): void
    {
        $columns = collect(Schema::getColumns('memory_records'))->keyBy('name');

        $this->assertTrue(Schema::hasColumns('memory_records', [
            'id',
            'uuid',
            'tenant_id',
            'author_user_id',
            'last_edited_by_user_id',
            'type',
            'title',
            'body',
            'notes',
            'experience_date',
            'period_start_date',
            'period_end_date',
            'location_name',
            'people',
            'source',
            'source_metadata',
            'created_at',
            'updated_at',
        ]));

        $this->assertFalse($columns->get('tenant_id')['nullable']);
        $this->assertTrue($columns->get('author_user_id')['nullable']);
        $this->assertTrue($columns->get('last_edited_by_user_id')['nullable']);
        $this->assertTrue($columns->get('body')['nullable']);
        $this->assertTrue($columns->get('experience_date')['nullable']);
        $this->assertTrue($columns->get('period_start_date')['nullable']);
        $this->assertTrue($columns->get('period_end_date')['nullable']);

        foreach ([
            'ai_classification',
            'approval_status',
            'family_visibility',
            'media_id',
            'public_slug',
            'visibility',
        ] as $excludedColumn) {
            $this->assertFalse(Schema::hasColumn('memory_records', $excludedColumn));
        }

        $this->assertSame([MemoryRecord::TYPE_DIARY, MemoryRecord::TYPE_PERIOD], MemoryRecord::ACTIVE_TYPES);
        $this->assertSame([], MemoryRecord::RESERVED_TYPES);
    }

    public function test_can_create_tenant_scoped_diary_record_with_author_and_last_editor(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);
        $editor = $this->createUser($tenant);

        $record = MemoryRecord::factory()
            ->for($tenant)
            ->for($author, 'author')
            ->for($editor, 'lastEditor')
            ->create([
                'type' => MemoryRecord::TYPE_DIARY,
                'body' => 'Today we visited a family and recorded the experience.',
                'experience_date' => '2026-06-05',
                'location_name' => 'Sao Paulo',
                'people' => [
                    'Elder Santos',
                    'Sister Santos',
                ],
            ]);

        $record->refresh();

        $this->assertModelExists($record);
        $this->assertTrue($record->tenant->is($tenant));
        $this->assertTrue($record->author->is($author));
        $this->assertTrue($record->lastEditor->is($editor));
        $this->assertSame('2026-06-05', $record->experience_date->toDateString());
        $this->assertSame([
            'Elder Santos',
            'Sister Santos',
        ], $record->people);
        $this->assertSame('uuid', $record->getRouteKeyName());
    }

    public function test_factories_create_valid_record_tag_and_highlight_relationships(): void
    {
        $record = MemoryRecord::factory()->create();
        $tenant = $record->tenant;
        $tag = MemoryTag::factory()
            ->for($tenant)
            ->create([
                'name' => 'Faith Building',
                'slug' => 'faith-building',
            ]);
        $highlight = MemoryHighlight::factory()
            ->forRecord($record, 1)
            ->create([
                'text' => 'A family asked to learn more.',
            ]);

        $record->tags()->attach($tag);

        $this->assertTrue($tenant->memoryRecords()->first()->is($record));
        $this->assertTrue($record->author->canAccessTenant($tenant));
        $this->assertTrue($record->lastEditor->canAccessTenant($tenant));
        $this->assertTrue($record->tags()->first()->is($tag));
        $this->assertTrue($tag->records()->first()->is($record));
        $this->assertTrue($record->highlights()->first()->is($highlight));
        $this->assertTrue($highlight->memoryRecord->is($record));
    }

    public function test_tag_slugs_are_unique_per_tenant_and_reusable_across_tenants(): void
    {
        $firstTenant = $this->createTenant();
        $secondTenant = $this->createTenant();
        $slug = 'shared-tag-'.Str::uuid();

        MemoryTag::factory()->for($firstTenant)->create([
            'name' => 'Shared Tag',
            'slug' => $slug,
        ]);
        $secondTenantTag = MemoryTag::factory()->for($secondTenant)->create([
            'name' => 'Shared Tag',
            'slug' => $slug,
        ]);

        $this->assertModelExists($secondTenantTag);

        $this->expectException(QueryException::class);

        MemoryTag::factory()->for($firstTenant)->create([
            'name' => 'Shared Tag Again',
            'slug' => $slug,
        ]);
    }

    public function test_highlights_are_stored_with_ordered_sort_values(): void
    {
        $record = MemoryRecord::factory()->create();

        MemoryHighlight::factory()
            ->forRecord($record, 20)
            ->create([
                'text' => 'Second highlight',
            ]);
        MemoryHighlight::factory()
            ->forRecord($record, 10)
            ->create([
                'text' => 'First highlight',
            ]);

        $this->assertSame([10, 20], $record->refresh()->highlights->pluck('sort_order')->all());

        $this->expectException(QueryException::class);

        MemoryHighlight::factory()
            ->forRecord($record, 10)
            ->create([
                'text' => 'Duplicate sort position',
            ]);
    }

    public function test_highlight_factory_generates_non_colliding_default_sort_order_values_for_one_record(): void
    {
        $record = MemoryRecord::factory()->create();

        $highlights = MemoryHighlight::factory()
            ->count(12)
            ->for($record, 'memoryRecord')
            ->create();

        $this->assertSame(12, $highlights->pluck('sort_order')->unique()->count());
    }
}
