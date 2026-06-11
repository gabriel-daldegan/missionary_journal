<?php

namespace Tests\Feature\Livewire\Memory;

use App\Livewire\Memory\MemoryRecordDetail;
use App\Models\MemoryProfile;
use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Services\MemoryRecordService;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Feature\FeatureTest;

class MemoryRecordDetailTest extends FeatureTest
{
    public function test_authorized_member_can_open_diary_record_detail(): void
    {
        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Santos Family Workspace',
        ]);
        $user = $this->createUser($tenant, attributes: [
            'name' => 'Ana Santos',
        ]);
        MemoryProfile::factory()->for($user)->create();
        $record = $this->service()->createDiaryRecord($tenant, $user, [
            'body' => 'We wrote the full memory after dinner.',
            'experience_date' => '2026-06-07',
            'location_name' => 'Curitiba',
            'tags' => ['Family', 'Faith'],
            'highlights' => ['Grandma remembered the song.'],
        ]);

        $response = $this->actingAs($user)->get($this->showRoute($tenant, $record));

        $response->assertOk();
        $response->assertSee('data-memory-layout="true"', false);
        $response->assertSee('Santos Family Workspace');
        $response->assertSee('Diary memory');
        $response->assertSee('We wrote the full memory after dinner.');
        $response->assertSee('Curitiba');
        $response->assertSee('Family');
        $response->assertSee('Grandma remembered the song.');
        $response->assertSee('Written by Ana Santos');
        $response->assertSee(route('memories.records.edit', [
            'tenant' => $tenant,
            'record' => $record,
        ]), false);

        foreach ([
            'Missionary only',
            'Family visible',
            'Public sharing',
            'Collaboration mode',
        ] as $forbiddenText) {
            $response->assertDontSee($forbiddenText);
        }
    }

    public function test_authorized_member_can_open_period_record_detail(): void
    {
        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Santos Family Workspace',
        ]);
        $user = $this->createUser($tenant, attributes: [
            'name' => 'Ana Santos',
        ]);
        MemoryProfile::factory()->for($user)->create();
        $record = $this->service()->createPeriodRecord($tenant, $user, [
            'title' => 'July family visit',
            'period_start_date' => '2026-07-10',
            'period_end_date' => '2026-07-14',
            'location_name' => 'Curitiba',
            'notes' => 'A short set of notes for the multi-day memory.',
            'people' => ['Ana', 'Pedro'],
            'tags' => ['Family', 'Visit'],
            'highlights' => ['Shared Sunday lunch.'],
        ]);

        $response = $this->actingAs($user)->get($this->showRoute($tenant, $record));

        $response->assertOk();
        $response->assertSee('data-memory-layout="true"', false);
        $response->assertSee('Santos Family Workspace');
        $response->assertSee('Trip or period');
        $response->assertSee('July family visit');
        $response->assertSee('Jul 10, 2026');
        $response->assertSee('Jul 14, 2026');
        $response->assertSee('Curitiba');
        $response->assertSee('Ana');
        $response->assertSee('Pedro');
        $response->assertSee('A short set of notes for the multi-day memory.');
        $response->assertSee('Family');
        $response->assertSee('Shared Sunday lunch.');
        $response->assertDontSee(route('memories.records.edit', [
            'tenant' => $tenant,
            'record' => $record,
        ]), false);
    }

    public function test_non_member_direct_detail_url_receives_generic_not_found(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);
        MemoryProfile::factory()->for($author)->create();
        $record = $this->service()->createDiaryRecord($tenant, $author, [
            'body' => 'Private cross tenant memory body.',
            'experience_date' => '2026-06-07',
        ]);
        $outsideUser = $this->createUser();
        MemoryProfile::factory()->for($outsideUser)->create();

        $response = $this->actingAs($outsideUser)->get($this->showRoute($tenant, $record));

        $response->assertNotFound();
        $response->assertDontSee('Private cross tenant memory body.');
    }

    public function test_cross_tenant_record_parameter_is_denied_without_content_leak(): void
    {
        $this->withExceptionHandling();

        $recordTenant = $this->createTenant();
        $routeTenant = $this->createTenant();
        $author = $this->createUser($recordTenant);
        $routeUser = $this->createUser($routeTenant);
        MemoryProfile::factory()->for($routeUser)->create();
        $record = $this->service()->createDiaryRecord($recordTenant, $author, [
            'body' => 'Wrong tenant memory body.',
            'experience_date' => '2026-06-07',
        ]);

        $response = $this->actingAs($routeUser)->get($this->showRoute($routeTenant, $record));

        $response->assertNotFound();
        $response->assertDontSee('Wrong tenant memory body.');
    }

    public function test_non_member_cannot_mount_detail_component_directly(): void
    {
        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);
        $record = $this->service()->createDiaryRecord($tenant, $author, [
            'body' => 'Direct component leak attempt.',
            'experience_date' => '2026-06-07',
        ]);
        $outsideUser = $this->createUser();
        MemoryProfile::factory()->for($outsideUser)->create();
        $this->actingAs($outsideUser);

        $this->expectException(NotFoundHttpException::class);

        Livewire::test(MemoryRecordDetail::class, [
            'tenant' => $tenant,
            'record' => $record,
        ]);
    }

    private function showRoute(Tenant $tenant, MemoryRecord $record): string
    {
        return route('memories.records.show', [
            'tenant' => $tenant,
            'record' => $record,
        ]);
    }

    private function service(): MemoryRecordService
    {
        return app(MemoryRecordService::class);
    }
}
