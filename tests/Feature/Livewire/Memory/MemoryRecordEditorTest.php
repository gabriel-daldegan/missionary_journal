<?php

namespace Tests\Feature\Livewire\Memory;

use App\Livewire\Memory\MemoryRecordEditor;
use App\Models\MemoryProfile;
use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Services\MemoryRecordService;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Feature\FeatureTest;

class MemoryRecordEditorTest extends FeatureTest
{
    public function test_member_with_completed_profile_can_access_diary_create_route(): void
    {
        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Santos Family Workspace',
        ]);
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get($this->createRoute($tenant));

        $response->assertOk();
        $response->assertSee('data-memory-layout="true"', false);
        $response->assertSee('New diary entry');
        $response->assertSee('Memory text');
        $response->assertSee('Santos Family Workspace');
    }

    public function test_member_with_completed_profile_can_access_period_create_route(): void
    {
        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Santos Family Workspace',
        ]);
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get($this->periodCreateRoute($tenant));

        $response->assertOk();
        $response->assertSee('data-memory-layout="true"', false);
        $response->assertSee('New trip or period');
        $response->assertSee('Title');
        $response->assertSee('Start date');
        $response->assertSee('End date');
        $response->assertSee('People');
        $response->assertSee('Notes');
        $response->assertSee('Memory Highlights');
        $response->assertSee('Santos Family Workspace');
        $response->assertDontSee('Memory text');
    }

    public function test_member_without_completed_profile_is_redirected_to_profile_setup(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $response = $this->actingAs($user)->get($this->createRoute($tenant));

        $response->assertRedirect(route('memories.profile.setup', [
            'tenant' => $tenant,
        ]));
    }

    public function test_non_member_direct_url_receives_generic_not_found(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Private Tenant Details',
        ]);
        $user = $this->createUser();

        $response = $this->actingAs($user)->get($this->createRoute($tenant));

        $response->assertNotFound();
        $response->assertDontSee('Private Tenant Details');
        $response->assertDontSee('New diary entry');
    }

    public function test_non_member_cannot_mount_editor_component_directly(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser();
        MemoryProfile::factory()->for($user)->create();
        $this->actingAs($user);

        $this->expectException(NotFoundHttpException::class);

        Livewire::test(MemoryRecordEditor::class, [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_DIARY,
        ]);
    }

    public function test_unsupported_record_type_is_rejected_by_route_and_writes_nothing(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        $recordCount = MemoryRecord::query()->count();

        $response = $this->actingAs($user)->get(sprintf(
            '/memories/%s/records/create/%s',
            $tenant->uuid,
            'unsupported',
        ));

        $response->assertNotFound();
        $this->assertSame($recordCount, MemoryRecord::query()->count());
    }

    public function test_non_member_cannot_create_period_record(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();
        $user = $this->createUser();
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get($this->periodCreateRoute($tenant));

        $response->assertNotFound();
    }

    public function test_body_and_experience_date_are_required(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(MemoryRecordEditor::class, [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_DIARY,
        ])
            ->set('body', '')
            ->set('experienceDate', '')
            ->call('save')
            ->assertHasErrors([
                'body' => 'required',
                'experienceDate' => 'required',
            ]);
    }

    public function test_successful_diary_creation_persists_record_tags_and_highlights_then_redirects(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(MemoryRecordEditor::class, [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_DIARY,
        ])
            ->set('body', 'We visited a family and wrote the experience while it was fresh.')
            ->set('experienceDate', '2026-06-08')
            ->set('locationName', 'Curitiba')
            ->set('tagInput', 'Faith, Family')
            ->set('highlights', [
                ['text' => 'The family shared a favorite memory.'],
                ['text' => 'We scheduled another visit.'],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('memories.timeline', [
                'tenant' => $tenant,
            ]));

        /** @var MemoryRecord $record */
        $record = MemoryRecord::query()->whereBelongsTo($tenant)->firstOrFail();

        $this->assertSame(MemoryRecord::TYPE_DIARY, $record->type);
        $this->assertSame($user->id, $record->author_user_id);
        $this->assertSame($user->id, $record->last_edited_by_user_id);
        $this->assertSame('2026-06-08', $record->experience_date->toDateString());
        $this->assertSame('Curitiba', $record->location_name);
        $this->assertSame(['faith', 'family'], $record->tags->pluck('slug')->sort()->values()->all());
        $this->assertSame([
            'The family shared a favorite memory.',
            'We scheduled another visit.',
        ], $record->highlights->pluck('text')->all());
        $this->assertSame([0, 1], $record->highlights->pluck('sort_order')->all());
    }

    public function test_successful_period_creation_persists_record_people_tags_and_highlights_then_redirects(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(MemoryRecordEditor::class, [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_PERIOD,
        ])
            ->assertSet('type', MemoryRecord::TYPE_PERIOD)
            ->assertSee('New trip or period')
            ->assertSee('Start date')
            ->set('title', 'July family visit')
            ->set('periodStartDate', '2026-07-10')
            ->set('periodEndDate', '2026-07-14')
            ->set('locationName', 'Curitiba')
            ->set('peopleInput', 'Ana, Pedro')
            ->set('notes', 'A short set of notes for the multi-day memory.')
            ->set('tagInput', 'Family, Visit')
            ->set('highlights', [
                ['text' => 'Arrived together.'],
                ['text' => 'Shared Sunday lunch.'],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('memories.timeline', [
                'tenant' => $tenant,
            ]));

        /** @var MemoryRecord $record */
        $record = MemoryRecord::query()->whereBelongsTo($tenant)->firstOrFail();

        $this->assertSame(MemoryRecord::TYPE_PERIOD, $record->type);
        $this->assertSame($user->id, $record->author_user_id);
        $this->assertSame($user->id, $record->last_edited_by_user_id);
        $this->assertSame('July family visit', $record->title);
        $this->assertNull($record->experience_date);
        $this->assertSame('2026-07-10', $record->period_start_date->toDateString());
        $this->assertSame('2026-07-14', $record->period_end_date->toDateString());
        $this->assertSame('Curitiba', $record->location_name);
        $this->assertSame(['Ana', 'Pedro'], $record->people);
        $this->assertSame(['family', 'visit'], $record->tags->pluck('slug')->sort()->values()->all());
        $this->assertSame([
            'Arrived together.',
            'Shared Sunday lunch.',
        ], $record->highlights->pluck('text')->all());
        $this->assertSame([0, 1], $record->highlights->pluck('sort_order')->all());
    }

    public function test_period_title_and_date_order_are_required(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(MemoryRecordEditor::class, [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_PERIOD,
        ])
            ->set('title', '')
            ->set('periodStartDate', '')
            ->set('periodEndDate', '')
            ->call('save')
            ->assertHasErrors([
                'title' => 'required',
                'periodStartDate' => 'required',
                'periodEndDate' => 'required',
            ])
            ->set('title', 'Backwards trip')
            ->set('periodStartDate', '2026-07-14')
            ->set('periodEndDate', '2026-07-10')
            ->call('save')
            ->assertHasErrors([
                'periodEndDate' => 'after_or_equal',
            ]);
    }

    public function test_edit_mode_prefills_and_updates_record_without_changing_identity(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);
        $editor = $this->createUser($tenant);
        MemoryProfile::factory()->for($editor)->create();
        $record = app(MemoryRecordService::class)->createDiaryRecord($tenant, $author, [
            'body' => 'Original memory body.',
            'experience_date' => '2026-06-08',
            'location_name' => 'Sao Paulo',
            'tags' => ['Original', 'Family'],
            'highlights' => ['Original highlight'],
        ]);
        $recordId = $record->id;
        $recordUuid = $record->uuid;
        $this->actingAs($editor);

        Livewire::test(MemoryRecordEditor::class, [
            'tenant' => $tenant,
            'record' => $record,
        ])
            ->assertSet('isEditing', true)
            ->assertSet('body', 'Original memory body.')
            ->assertSet('experienceDate', '2026-06-08')
            ->assertSet('locationName', 'Sao Paulo')
            ->assertSee('Edit diary entry')
            ->set('body', 'Updated memory body.')
            ->set('experienceDate', '2026-06-12')
            ->set('locationName', 'Curitiba')
            ->set('tagInput', 'Updated, Family')
            ->set('highlights', [
                ['text' => 'Updated highlight'],
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('memories.records.show', [
                'tenant' => $tenant,
                'record' => $record,
            ]));

        /** @var MemoryRecord $updatedRecord */
        $updatedRecord = MemoryRecord::query()->findOrFail($recordId);

        $this->assertSame($recordUuid, $updatedRecord->uuid);
        $this->assertSame($author->id, $updatedRecord->author_user_id);
        $this->assertSame($editor->id, $updatedRecord->last_edited_by_user_id);
        $this->assertSame('Updated memory body.', $updatedRecord->body);
        $this->assertSame('2026-06-12', $updatedRecord->experience_date->toDateString());
        $this->assertSame(['family', 'updated'], $updatedRecord->tags->pluck('slug')->sort()->values()->all());
        $this->assertSame(['Updated highlight'], $updatedRecord->highlights->pluck('text')->all());
    }

    public function test_member_with_completed_profile_can_access_diary_edit_route(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);
        $editor = $this->createUser($tenant);
        MemoryProfile::factory()->for($editor)->create();
        $record = app(MemoryRecordService::class)->createDiaryRecord($tenant, $author, [
            'body' => 'Route mounted edit body.',
            'experience_date' => '2026-06-08',
            'location_name' => 'Sao Paulo',
            'tags' => ['Route'],
            'highlights' => ['Route mounted highlight.'],
        ]);

        $response = $this->actingAs($editor)->get(route('memories.records.edit', [
            'tenant' => $tenant,
            'record' => $record,
        ]));

        $response->assertOk();
        $response->assertSee('Edit diary entry');
        $response->assertSee('Route mounted edit body.');
        $response->assertSee('Sao Paulo');
        $response->assertSee('Route mounted highlight.');
    }

    public function test_cross_tenant_edit_route_receives_generic_not_found(): void
    {
        $this->withExceptionHandling();

        $recordTenant = $this->createTenant();
        $routeTenant = $this->createTenant();
        $author = $this->createUser($recordTenant);
        $routeUser = $this->createUser($routeTenant);
        MemoryProfile::factory()->for($routeUser)->create();
        $record = app(MemoryRecordService::class)->createDiaryRecord($recordTenant, $author, [
            'body' => 'Wrong tenant edit body.',
            'experience_date' => '2026-06-08',
        ]);

        $response = $this->actingAs($routeUser)->get(route('memories.records.edit', [
            'tenant' => $routeTenant,
            'record' => $record,
        ]));

        $response->assertNotFound();
        $response->assertDontSee('Wrong tenant edit body.');
    }

    public function test_dynamic_highlight_controls_keep_order_before_save(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        $this->actingAs($user);

        $component = Livewire::test(MemoryRecordEditor::class, [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_DIARY,
        ]);

        $firstHighlightUid = $component->get('highlights.0.uid');
        $this->assertIsString($firstHighlightUid);
        $this->assertStringContainsString('wire:key="highlight-'.$firstHighlightUid.'"', $component->html());

        $component
            ->set('body', 'A memory with reordered highlights.')
            ->set('experienceDate', '2026-06-08')
            ->set('highlights.0.text', 'Second')
            ->call('addHighlight')
            ->set('highlights.1.text', 'First');

        $secondHighlightUid = $component->get('highlights.1.uid');
        $this->assertIsString($secondHighlightUid);
        $this->assertNotSame($firstHighlightUid, $secondHighlightUid);

        $component->call('moveHighlightUp', 1);

        $this->assertSame($secondHighlightUid, $component->get('highlights.0.uid'));
        $this->assertSame($firstHighlightUid, $component->get('highlights.1.uid'));

        $component
            ->call('addHighlight')
            ->set('highlights.2.text', 'Remove me')
            ->call('removeHighlight', 2)
            ->call('save')
            ->assertHasNoErrors();

        /** @var MemoryRecord $record */
        $record = MemoryRecord::query()->whereBelongsTo($tenant)->firstOrFail();

        $this->assertSame([
            'First',
            'Second',
        ], $record->highlights->pluck('text')->all());
    }

    public function test_forbidden_controls_are_not_rendered(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get($this->createRoute($tenant));

        foreach ([
            'Missionary only',
            'Family visible',
            'Collaboration mode',
            'Owner review',
            'Read-only collaborator',
            'Public sharing',
            'Media upload',
            'AI classification',
            'Email source',
            'Reporting',
        ] as $forbiddenText) {
            $response->assertDontSee($forbiddenText);
        }
    }

    public function test_timeline_exposes_first_level_diary_create_action(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('memories.timeline', [
            'tenant' => $tenant,
        ]));

        $response->assertOk();
        $response->assertSee('New diary entry');
        $response->assertSee($this->createRoute($tenant), false);
        $response->assertSee('New trip / period');
        $response->assertSee($this->periodCreateRoute($tenant), false);
    }

    public function test_timeline_links_existing_records_and_reorders_after_experience_date_update(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        $service = app(MemoryRecordService::class);
        $olderRecord = $service->createDiaryRecord($tenant, $user, [
            'body' => 'Older timeline memory.',
            'experience_date' => '2026-06-01',
        ]);
        $newerRecord = $service->createDiaryRecord($tenant, $user, [
            'body' => 'Newer timeline memory.',
            'experience_date' => '2026-06-10',
        ]);

        $initialResponse = $this->actingAs($user)->get(route('memories.timeline', [
            'tenant' => $tenant,
        ]));

        $initialResponse->assertOk();
        $initialResponse->assertSee(route('memories.records.show', [
            'tenant' => $tenant,
            'record' => $olderRecord,
        ]), false);
        $initialResponse->assertSeeInOrder([
            'Newer timeline memory.',
            'Older timeline memory.',
        ]);

        $service->updateDiaryRecord($olderRecord, $tenant, $user, [
            'body' => 'Older timeline memory.',
            'experience_date' => '2026-06-15',
        ]);

        $updatedResponse = $this->actingAs($user)->get(route('memories.timeline', [
            'tenant' => $tenant,
        ]));

        $updatedResponse->assertOk();
        $updatedResponse->assertSeeInOrder([
            'Older timeline memory.',
            'Newer timeline memory.',
        ]);
    }

    private function createRoute(Tenant $tenant): string
    {
        return route('memories.records.create', [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_DIARY,
        ]);
    }

    private function periodCreateRoute(Tenant $tenant): string
    {
        return route('memories.records.create', [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_PERIOD,
        ]);
    }
}
