<?php

namespace Tests\Feature\Memory;

use App\Models\MemoryProfile;
use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MemoryRecordService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\FeatureTest;

class MemoryRouteAccessTest extends FeatureTest
{
    public function test_guest_is_redirected_from_memory_route(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();

        $response = $this->get($this->memoryRoute($tenant));

        $response->assertRedirect(route('login'));
    }

    public function test_tenant_member_can_access_memory_shell(): void
    {
        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Santos Family Workspace',
        ]);
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get($this->memoryRoute($tenant));

        $response->assertOk();
        $response->assertSee('data-memory-layout="true"', false);
        $response->assertSee('Memory Timeline');
        $response->assertSee('Santos Family Workspace');
        $response->assertSee('Memory profile');
        $response->assertSee('Account');
        $response->assertSee('Dashboard');
    }

    public function test_authenticated_non_member_receives_generic_not_found(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Private Tenant Details',
        ]);
        $user = $this->createUser();

        $response = $this->actingAs($user)->get($this->memoryRoute($tenant));

        $response->assertNotFound();
        $response->assertDontSee('Private Tenant Details');
        $response->assertDontSee('Memory Timeline');
    }

    public function test_memory_shell_uses_dedicated_product_layout(): void
    {
        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $response = $this->actingAs($user)->get($this->memoryRoute($tenant));

        $response->assertOk();
        $response->assertSee('data-memory-layout="true"', false);
        $response->assertDontSee('All rights reserved.');
        $response->assertDontSee('Get started');
        $response->assertDontSee('fi-sidebar');
        $response->assertDontSee('<table', false);
        $response->assertDontSee('<footer', false);
    }

    public function test_existing_public_and_dashboard_entry_routes_keep_current_behavior(): void
    {
        $homeResponse = $this->get(route('home'));

        $homeResponse->assertOk();
        $homeResponse->assertSee('<footer', false);
        $homeResponse->assertDontSee('data-memory-layout="true"', false);

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);

        $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));

        $dashboardResponse->assertRedirect(route('filament.dashboard.pages.dashboard', [
            'tenant' => $tenant,
        ]));
    }

    public function test_tenant_member_can_access_private_memory_photo_route(): void
    {
        Storage::fake('local');

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        $record = $this->createRecordWithPhoto($tenant, $user);
        $media = $record->getFirstMedia($record->mediaCollectionName());

        $response = $this->actingAs($user)->get(route('memories.media.show', [
            'tenant' => $tenant,
            'media' => $media->uuid,
        ]));

        $response->assertOk();
        $this->assertStringStartsWith('image/', (string) $response->headers->get('content-type'));
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_private_memory_photo_route_supports_morph_map_alias(): void
    {
        Storage::fake('local');
        Relation::enforceMorphMap([
            'memory-record' => MemoryRecord::class,
        ]);

        try {
            $tenant = $this->createTenant();
            $user = $this->createUser($tenant);
            MemoryProfile::factory()->for($user)->create();
            $record = $this->createRecordWithPhoto($tenant, $user);
            $media = $record->getFirstMedia($record->mediaCollectionName());

            $this->assertSame('memory-record', $media->model_type);

            $response = $this->actingAs($user)->get(route('memories.media.show', [
                'tenant' => $tenant,
                'media' => $media->uuid,
            ]));

            $response->assertOk();
        } finally {
            Relation::morphMap([], false);
            Relation::requireMorphMap(false);
        }
    }

    public function test_cross_tenant_private_memory_photo_route_is_denied_without_content_leak(): void
    {
        $this->withExceptionHandling();
        Storage::fake('local');

        $recordTenant = $this->createTenant();
        $routeTenant = $this->createTenant();
        $author = $this->createUser($recordTenant);
        $routeUser = $this->createUser($routeTenant);
        MemoryProfile::factory()->for($routeUser)->create();
        $record = $this->createRecordWithPhoto($recordTenant, $author, 'Private media body leak sentinel.');
        $media = $record->getFirstMedia($record->mediaCollectionName());

        $response = $this->actingAs($routeUser)->get(route('memories.media.show', [
            'tenant' => $routeTenant,
            'media' => $media->uuid,
        ]));

        $response->assertNotFound();
        $response->assertDontSee('Private media body leak sentinel.');
        $response->assertDontSee('memory-records');
        $response->assertDontSee($media->file_name);
    }

    public function test_non_member_private_memory_photo_route_is_denied_without_content_leak(): void
    {
        $this->withExceptionHandling();
        Storage::fake('local');

        $tenant = $this->createTenant();
        $author = $this->createUser($tenant);
        $outsideUser = $this->createUser();
        MemoryProfile::factory()->for($outsideUser)->create();
        $record = $this->createRecordWithPhoto($tenant, $author, 'Non member media body leak sentinel.');
        $media = $record->getFirstMedia($record->mediaCollectionName());

        $response = $this->actingAs($outsideUser)->get(route('memories.media.show', [
            'tenant' => $tenant,
            'media' => $media->uuid,
        ]));

        $response->assertNotFound();
        $response->assertDontSee('Non member media body leak sentinel.');
        $response->assertDontSee('memory-records');
        $response->assertDontSee($media->file_name);
    }

    private function memoryRoute(Tenant $tenant): string
    {
        return route('memories.timeline', [
            'tenant' => $tenant,
        ]);
    }

    private function createRecordWithPhoto(Tenant $tenant, User $author, string $body = 'Private photo memory.'): MemoryRecord
    {
        return app(MemoryRecordService::class)->createDiaryRecord($tenant, $author, [
            'body' => $body,
            'experience_date' => '2026-06-06',
            'photos' => [
                UploadedFile::fake()->image('private-photo.jpg')->size(128),
            ],
        ]);
    }
}
