<?php

namespace Tests\Feature\Filament\Admin\Page;

use App\Filament\Admin\Pages\MemoryValidationReport;
use App\Models\MemoryProfile;
use App\Models\MemoryRecord;
use App\Services\MemoryValidationReportService;
use Illuminate\Support\Str;
use Mockery;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Feature\FeatureTest;

class MemoryValidationReportTest extends FeatureTest
{
    public function test_admin_can_access_validation_report_without_private_record_content(): void
    {
        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Admin Validation Workspace',
        ]);
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();

        $record = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'author_user_id' => $user->id,
            'last_edited_by_user_id' => $user->id,
            'body' => 'Admin page private body sentinel.',
            'notes' => 'Admin page private notes sentinel.',
            'location_name' => 'Admin page private location sentinel.',
            'created_at' => now()->subDays(3),
        ]);
        $this->createMediaForRecord($record, 'admin-private-photo.jpg');

        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get(MemoryValidationReport::getUrl([], true, 'admin'))
            ->assertSuccessful();

        $response->assertSee('Active validation tenants');
        $response->assertSee('Admin Validation Workspace');
        $response->assertSee('Tenant validation signals');
        $response->assertDontSee('Admin page private body sentinel.');
        $response->assertDontSee('Admin page private notes sentinel.');
        $response->assertDontSee('Admin page private location sentinel.');
        $response->assertDontSee('admin-private-photo.jpg');
        $response->assertDontSee('memory-records');
    }

    public function test_workspace_user_cannot_access_validation_report(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        MemoryProfile::factory()->for($user)->create();
        MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'author_user_id' => $user->id,
            'body' => 'Denied validation report body sentinel.',
        ]);

        $response = $this->actingAs($user)
            ->get(MemoryValidationReport::getUrl([], true, 'admin'));

        $response->assertForbidden();
        $response->assertDontSee('Denied validation report body sentinel.');
    }

    public function test_workspace_user_does_not_resolve_validation_report_metrics(): void
    {
        $this->withExceptionHandling();

        $tenant = $this->createTenant();
        $user = $this->createUser($tenant);
        $reportService = Mockery::mock(MemoryValidationReportService::class);
        $reportService->shouldNotReceive('report');
        $this->app->instance(MemoryValidationReportService::class, $reportService);

        $this->actingAs($user)
            ->get(MemoryValidationReport::getUrl([], true, 'admin'))
            ->assertForbidden();
    }

    private function createMediaForRecord(MemoryRecord $record, string $fileName): void
    {
        Media::query()->create([
            'model_type' => $record->getMorphClass(),
            'model_id' => $record->id,
            'uuid' => (string) Str::uuid(),
            'collection_name' => $record->mediaCollectionName(),
            'name' => 'Private admin report photo',
            'file_name' => $fileName,
            'mime_type' => 'image/jpeg',
            'disk' => $record->mediaDiskName(),
            'conversions_disk' => $record->mediaDiskName(),
            'size' => 10,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
            'order_column' => 1,
        ]);
    }
}
