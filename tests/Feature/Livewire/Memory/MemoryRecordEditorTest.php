<?php

namespace Tests\Feature\Livewire\Memory;

use App\Livewire\Memory\MemoryRecordEditor;
use App\Models\MemoryProfile;
use App\Models\MemoryRecord;
use App\Models\Tenant;
use Livewire\Livewire;
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

    public function test_unsupported_record_type_returns_not_found_and_writes_nothing(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        $recordCount = MemoryRecord::query()->count();

        $response = $this->actingAs($user)->get(route('memories.records.create', [
            'tenant' => $tenant,
            'type' => 'period',
        ]));

        $response->assertNotFound();
        $this->assertSame($recordCount, MemoryRecord::query()->count());
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

    public function test_dynamic_highlight_controls_keep_order_before_save(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        $this->actingAs($user);

        Livewire::test(MemoryRecordEditor::class, [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_DIARY,
        ])
            ->set('body', 'A memory with reordered highlights.')
            ->set('experienceDate', '2026-06-08')
            ->set('highlights', [
                ['text' => 'Second'],
                ['text' => 'First'],
            ])
            ->call('moveHighlightUp', 1)
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
            'Period',
            'Trip',
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
    }

    private function createRoute(Tenant $tenant): string
    {
        return route('memories.records.create', [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_DIARY,
        ]);
    }
}
