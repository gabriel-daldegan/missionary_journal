<?php

namespace Tests\Feature\Services\Memory;

use App\Models\MemoryRecord;
use App\Models\MemoryTag;
use App\Services\MemoryRecordService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tests\Feature\FeatureTest;

class MemoryRecordServiceTest extends FeatureTest
{
    public function test_authorized_tenant_member_can_create_diary_record(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);

        $record = $this->service()->createDiaryRecord($tenant, $author, [
            'body' => 'We met a family who wanted to preserve their conversion story.',
            'experience_date' => '2026-06-06',
            'location_name' => 'Sao Paulo',
            'tags' => ['Faith Building'],
            'highlights' => ['They asked for another visit.'],
        ]);

        $this->assertModelExists($record);
        $this->assertTrue($record->tenant->is($tenant));
        $this->assertTrue($record->author->is($author));
        $this->assertTrue($record->lastEditor->is($author));
        $this->assertSame(MemoryRecord::TYPE_DIARY, $record->type);
        $this->assertSame('We met a family who wanted to preserve their conversion story.', $record->body);
        $this->assertSame('2026-06-06', $record->experience_date->toDateString());
        $this->assertSame('Sao Paulo', $record->location_name);
        $this->assertSame('uuid', $record->getRouteKeyName());
    }

    public function test_tags_are_created_once_and_reused_inside_the_same_tenant(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);

        $firstRecord = $this->service()->createDiaryRecord($tenant, $author, [
            'body' => 'First memory',
            'experience_date' => '2026-06-06',
            'tags' => ['Faith Building', ' faith building ', 'Service'],
        ]);
        $secondRecord = $this->service()->createDiaryRecord($tenant, $author, [
            'body' => 'Second memory',
            'experience_date' => '2026-06-07',
            'tags' => ['Faith Building'],
        ]);

        $this->assertSame(2, $tenant->memoryTags()->count());
        $this->assertSame(['faith-building', 'service'], $firstRecord->tags->pluck('slug')->sort()->values()->all());
        $this->assertSame(
            $firstRecord->tags->firstWhere('slug', 'faith-building')->id,
            $secondRecord->tags->firstWhere('slug', 'faith-building')->id,
        );
    }

    public function test_identical_tag_names_do_not_cross_tenant_boundaries(): void
    {
        $firstTenant = $this->createTenant();
        $secondTenant = $this->createTenant();
        $firstAuthor = $this->createUser($firstTenant);
        $secondAuthor = $this->createUser($secondTenant);

        $firstRecord = $this->service()->createDiaryRecord($firstTenant, $firstAuthor, [
            'body' => 'First tenant memory',
            'experience_date' => '2026-06-06',
            'tags' => ['Shared Faith'],
        ]);
        $secondRecord = $this->service()->createDiaryRecord($secondTenant, $secondAuthor, [
            'body' => 'Second tenant memory',
            'experience_date' => '2026-06-06',
            'tags' => ['Shared Faith'],
        ]);

        $firstTag = $firstRecord->tags->first();
        $secondTag = $secondRecord->tags->first();

        $this->assertNotSame($firstTag->id, $secondTag->id);
        $this->assertTrue($firstTag->tenant->is($firstTenant));
        $this->assertTrue($secondTag->tenant->is($secondTenant));
    }

    public function test_highlights_are_persisted_as_ordered_rows(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);

        $record = $this->service()->createDiaryRecord($tenant, $author, [
            'body' => 'A memory with highlights.',
            'experience_date' => '2026-06-06',
            'highlights' => [
                'First highlight',
                'Second highlight',
                '  ',
                'Third highlight',
            ],
        ]);

        $this->assertSame([0, 1, 2], $record->highlights->pluck('sort_order')->all());
        $this->assertSame([
            'First highlight',
            'Second highlight',
            'Third highlight',
        ], $record->highlights->pluck('text')->all());
    }

    public function test_user_outside_tenant_is_denied_before_any_write(): void
    {
        $tenant = $this->createTenant();
        $outsideUser = $this->createUser();

        $recordCount = MemoryRecord::query()->count();
        $tagCount = MemoryTag::query()->count();

        try {
            $this->service()->createDiaryRecord($tenant, $outsideUser, [
                'body' => 'Denied memory',
                'experience_date' => '2026-06-06',
                'tags' => ['Denied Tag'],
                'highlights' => ['Denied highlight'],
            ]);

            $this->fail('The outside user was not denied.');
        } catch (AuthorizationException) {
            $this->assertSame($recordCount, MemoryRecord::query()->count());
            $this->assertSame($tagCount, MemoryTag::query()->count());
        }
    }

    public function test_non_shared_collaboration_mode_denies_creation_before_any_write(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);

        $tenant->memoryWorkspaceSettings()->create([
            'collaboration_mode' => 'owner_review',
        ]);

        $recordCount = MemoryRecord::query()->count();

        try {
            $this->service()->createDiaryRecord($tenant->fresh(), $author, [
                'body' => 'Denied by workspace mode',
                'experience_date' => '2026-06-06',
            ]);

            $this->fail('The non-shared workspace mode did not deny creation.');
        } catch (AuthorizationException) {
            $this->assertSame($recordCount, MemoryRecord::query()->count());
        }
    }

    public function test_body_and_experience_date_are_required_before_persistence(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);
        $recordCount = MemoryRecord::query()->count();

        try {
            $this->service()->createDiaryRecord($tenant, $author, [
                'body' => '',
                'experience_date' => '',
            ]);

            $this->fail('Invalid diary data was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('body', $exception->errors());
            $this->assertArrayHasKey('experience_date', $exception->errors());
            $this->assertSame($recordCount, MemoryRecord::query()->count());
        }
    }

    public function test_memory_record_policy_reuses_workspace_settings_decisions(): void
    {
        $tenant = $this->createTenant();
        $member = $this->createUser($tenant);
        $outsideUser = $this->createUser();
        $record = $this->service()->createDiaryRecord($tenant, $member, [
            'body' => 'Policy memory.',
            'experience_date' => '2026-06-06',
        ]);

        $this->assertTrue($member->can('create', [MemoryRecord::class, $tenant]));
        $this->assertTrue($member->can('view', $record));
        $this->assertTrue($member->can('update', $record));
        $this->assertFalse($outsideUser->can('create', [MemoryRecord::class, $tenant]));
        $this->assertFalse($outsideUser->can('view', $record));
        $this->assertFalse($outsideUser->can('update', $record));

        $tenant->memoryWorkspaceSettings()->create([
            'collaboration_mode' => 'owner_review',
        ]);

        $this->assertFalse($member->can('create', [MemoryRecord::class, $tenant->fresh()]));
        $this->assertTrue($member->can('view', $record->fresh()));
        $this->assertFalse($member->can('update', $record->fresh()));
    }

    public function test_authorized_tenant_member_can_update_diary_record_without_changing_identity(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);
        $editor = $this->createUser($tenant);
        $record = $this->service()->createDiaryRecord($tenant, $author, [
            'body' => 'Original memory body.',
            'experience_date' => '2026-06-06',
            'location_name' => 'Sao Paulo',
            'tags' => ['Original'],
            'highlights' => ['Original highlight'],
        ]);
        $recordId = $record->id;
        $recordUuid = $record->uuid;

        $updatedRecord = $this->service()->updateDiaryRecord($record, $tenant, $editor, [
            'body' => 'Updated memory body.',
            'experience_date' => '2026-06-10',
            'location_name' => 'Curitiba',
            'tags' => ['Updated', 'Family'],
            'highlights' => ['First updated highlight', 'Second updated highlight'],
        ]);

        $this->assertSame($recordId, $updatedRecord->id);
        $this->assertSame($recordUuid, $updatedRecord->uuid);
        $this->assertSame($author->id, $updatedRecord->author_user_id);
        $this->assertSame($editor->id, $updatedRecord->last_edited_by_user_id);
        $this->assertSame('Updated memory body.', $updatedRecord->body);
        $this->assertSame('2026-06-10', $updatedRecord->experience_date->toDateString());
        $this->assertSame('Curitiba', $updatedRecord->location_name);
        $this->assertSame(['family', 'updated'], $updatedRecord->tags->pluck('slug')->sort()->values()->all());
        $this->assertSame([
            'First updated highlight',
            'Second updated highlight',
        ], $updatedRecord->highlights->pluck('text')->all());
        $this->assertSame([0, 1], $updatedRecord->highlights->pluck('sort_order')->all());
    }

    public function test_update_denies_cross_tenant_record_before_any_write(): void
    {
        $recordTenant = $this->createTenant();
        $routeTenant = $this->createTenant();
        $author = $this->createUser($recordTenant);
        $editor = $this->createUser($routeTenant);
        $record = $this->service()->createDiaryRecord($recordTenant, $author, [
            'body' => 'Original memory body.',
            'experience_date' => '2026-06-06',
        ]);

        try {
            $this->service()->updateDiaryRecord($record, $routeTenant, $editor, [
                'body' => 'Denied update.',
                'experience_date' => '2026-06-10',
            ]);

            $this->fail('The cross-tenant update was not denied.');
        } catch (AuthorizationException) {
            $this->assertSame('Original memory body.', $record->fresh()->body);
        }
    }

    public function test_non_shared_collaboration_mode_denies_update_before_any_write(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);
        $record = $this->service()->createDiaryRecord($tenant, $author, [
            'body' => 'Original memory body.',
            'experience_date' => '2026-06-06',
        ]);

        $tenant->memoryWorkspaceSettings()->create([
            'collaboration_mode' => 'owner_review',
        ]);

        try {
            $this->service()->updateDiaryRecord($record->fresh(), $tenant->fresh(), $author, [
                'body' => 'Denied update.',
                'experience_date' => '2026-06-10',
            ]);

            $this->fail('The non-shared workspace mode did not deny update.');
        } catch (AuthorizationException) {
            $this->assertSame('Original memory body.', $record->fresh()->body);
        }
    }

    private function service(): MemoryRecordService
    {
        return app(MemoryRecordService::class);
    }
}
