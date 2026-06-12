<?php

namespace Tests\Feature\Livewire\Memory;

use App\Models\MemoryProfile;
use App\Models\MemoryRecord;
use App\Models\MemoryTag;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\FeatureTest;

class MemoryTimelineTest extends FeatureTest
{
    public function test_timeline_renders_month_groups_with_record_cards_and_detail_links(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $tag = MemoryTag::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Family',
            'slug' => 'family',
        ]);

        Storage::fake('local');

        $earlierRecord = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Family ministry in 2026.',
            'experience_date' => '2026-06-11',
            'location_name' => 'Recife',
        ]);
        $earlierRecord
            ->addMedia(UploadedFile::fake()->image('first.jpg')->size(10))
            ->toMediaCollection($earlierRecord->mediaCollectionName(), $earlierRecord->mediaDiskName());
        $earlierRecord
            ->addMedia(UploadedFile::fake()->image('second.jpg')->size(10))
            ->toMediaCollection($earlierRecord->mediaCollectionName(), $earlierRecord->mediaDiskName());
        $earlierRecord->tags()->attach($tag->id);

        $periodRecord = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_PERIOD,
            'title' => 'Trip planning.',
            'body' => null,
            'notes' => 'Private planning notes stay on detail.',
            'experience_date' => null,
            'period_start_date' => '2026-06-20',
            'period_end_date' => '2026-06-21',
        ]);

        $olderRecord = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Quiet prep before visit.',
            'experience_date' => '2026-05-15',
            'location_name' => null,
        ]);

        $response = $this->actingAs($user)->get(route('memories.timeline', [
            'tenant' => $tenant,
        ]));

        $response->assertOk();
        $response->assertSee('June 2026');
        $response->assertSee('May 2026');
        $response->assertSeeInOrder([
            'June 2026',
            'Trip planning.',
            'Family ministry in 2026.',
            'May 2026',
            'Quiet prep before visit.',
        ]);
        $response->assertDontSee('Private planning notes stay on detail.');
        $response->assertSee($this->recordRoute($tenant, $periodRecord), false);
        $response->assertSee('Recife');
        $response->assertSeeInOrder([
            $periodRecord->period_start_date?->toFormattedDateString(),
            __('memory.timeline.period_range_separator'),
            $periodRecord->period_end_date?->toFormattedDateString(),
        ]);
        $response->assertSee($tag->name);
        $response->assertSee(trans_choice('memory.timeline.photo_count', 2, ['count' => 2]));
        $response->assertSee(__('memory.timeline.open_record'));
        $response->assertSee($this->createRoute($tenant), false);
    }

    public function test_empty_timeline_shows_create_action_for_the_first_diary_entry(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('memories.timeline', [
            'tenant' => $tenant,
        ]));

        $response->assertOk();
        $response->assertSee(__('memory.timeline.ready_heading'));
        $response->assertSee(__('memory.timeline.ready_body'));
        $response->assertSee(__('memory.record_editor.new_diary'));
        $response->assertSee($this->createRoute($tenant), false);
    }

    public function test_timeline_does_not_render_records_from_other_tenants(): void
    {
        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $foreignUser = $this->createUser($otherTenant);
        MemoryProfile::factory()->for($user)->create();
        MemoryProfile::factory()->for($foreignUser)->create();

        $foreignRecord = MemoryRecord::factory()->create([
            'tenant_id' => $otherTenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Record that must not appear.',
            'experience_date' => '2026-06-10',
        ]);

        MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => 'Allowed tenant record.',
            'experience_date' => '2026-06-10',
        ]);

        $response = $this->actingAs($user)->get(route('memories.timeline', [
            'tenant' => $tenant,
        ]));

        $response->assertOk();
        $response->assertSee('Allowed tenant record.');
        $response->assertDontSee($foreignRecord->body);
    }

    public function test_timeline_cards_render_an_excerpt_without_the_full_record_body(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $visibleOpening = 'Visible timeline excerpt opening.';
        $hiddenTail = 'Private full body tail should stay on detail.';

        MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => MemoryRecord::TYPE_DIARY,
            'body' => $visibleOpening.' '.str_repeat('middle context ', 20).$hiddenTail,
            'experience_date' => '2026-06-10',
        ]);

        $response = $this->actingAs($user)->get(route('memories.timeline', [
            'tenant' => $tenant,
        ]));

        $response->assertOk();
        $response->assertSee($visibleOpening);
        $response->assertDontSee($hiddenTail);
    }

    private function createRoute(Tenant $tenant): string
    {
        return route('memories.records.create', [
            'tenant' => $tenant,
            'type' => MemoryRecord::TYPE_DIARY,
        ]);
    }

    private function recordRoute(Tenant $tenant, MemoryRecord $record): string
    {
        return route('memories.records.show', [
            'tenant' => $tenant,
            'record' => $record,
        ]);
    }
}
