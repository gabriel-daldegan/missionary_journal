<?php

namespace Tests\Feature\Services\Memory;

use App\Models\MemoryProfile;
use App\Models\MemoryRecord;
use App\Services\MemoryValidationReportService;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Feature\FeatureTest;

class MemoryValidationReportServiceTest extends FeatureTest
{
    public function test_report_derives_validation_metrics_without_private_content(): void
    {
        $tenant = $this->createTenant();
        $tenant->update([
            'name' => 'Validation Family Workspace',
        ]);
        $owner = $this->createUser($tenant);
        $collaborator = $this->createUser($tenant);
        MemoryProfile::factory()->for($owner)->create([
            'completed_at' => now()->subDays(22),
            'mission_context' => 'Private mission context sentinel.',
        ]);
        MemoryProfile::factory()->for($collaborator)->incomplete()->create();

        $firstRecord = MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'author_user_id' => $owner->id,
            'last_edited_by_user_id' => $collaborator->id,
            'body' => 'Private report body sentinel.',
            'notes' => 'Private report notes sentinel.',
            'location_name' => 'Private report location sentinel.',
            'source' => 'email',
            'source_metadata' => [
                'private_subject' => 'Private email subject sentinel.',
            ],
            'created_at' => now()->subDays(20),
            'updated_at' => now()->subDays(20),
        ]);
        $this->createMediaForRecord($firstRecord, 'private-report-photo.jpg');

        MemoryRecord::factory()->create([
            'tenant_id' => $tenant->id,
            'author_user_id' => $collaborator->id,
            'last_edited_by_user_id' => $collaborator->id,
            'body' => 'Second private body sentinel.',
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ]);

        $foreignTenant = $this->createTenant();
        $foreignUser = $this->createUser($foreignTenant);
        MemoryRecord::factory()->create([
            'tenant_id' => $foreignTenant->id,
            'author_user_id' => $foreignUser->id,
            'last_edited_by_user_id' => $foreignUser->id,
            'body' => 'Foreign tenant body sentinel.',
        ]);

        $report = app(MemoryValidationReportService::class)->report();
        $tenantReport = collect($report['tenants'])
            ->firstWhere('tenant_id', $tenant->id);

        $this->assertGreaterThanOrEqual(1, $report['summary']['active_validation_tenants']);
        $this->assertGreaterThanOrEqual(1, $report['summary']['tenants_spanning_two_weeks']);
        $this->assertGreaterThanOrEqual(1, $report['summary']['tenants_with_collaborator_engagement']);
        $this->assertGreaterThanOrEqual(1, $report['summary']['tenants_with_media']);
        $this->assertGreaterThanOrEqual(1, $report['summary']['tenants_with_email_source']);

        $this->assertSame('Validation Family Workspace', $tenantReport['tenant_name']);
        $this->assertSame(2, $tenantReport['member_count']);
        $this->assertSame(1, $tenantReport['completed_profile_count']);
        $this->assertSame(2, $tenantReport['record_count']);
        $this->assertGreaterThanOrEqual(14, $tenantReport['activity_span_days']);
        $this->assertTrue($tenantReport['activity_spans_two_weeks']);
        $this->assertTrue($tenantReport['active_validation_tenant']);
        $this->assertSame(2, $tenantReport['collaborator_participant_count']);
        $this->assertSame(1, $tenantReport['collaborator_edit_count']);
        $this->assertTrue($tenantReport['has_collaborator_engagement']);
        $this->assertTrue($tenantReport['shared_editing_enabled']);
        $this->assertSame(1, $tenantReport['media_record_count']);
        $this->assertSame(1, $tenantReport['media_attachment_count']);
        $this->assertSame(1, $tenantReport['email_source_record_count']);
        $this->assertTrue($tenantReport['has_email_source_marker']);

        $encodedReport = json_encode($report);

        $this->assertIsString($encodedReport);
        $this->assertStringNotContainsString('Private report body sentinel.', $encodedReport);
        $this->assertStringNotContainsString('Private report notes sentinel.', $encodedReport);
        $this->assertStringNotContainsString('Private report location sentinel.', $encodedReport);
        $this->assertStringNotContainsString('Private email subject sentinel.', $encodedReport);
        $this->assertStringNotContainsString('Private mission context sentinel.', $encodedReport);
        $this->assertStringNotContainsString('Second private body sentinel.', $encodedReport);
        $this->assertStringNotContainsString('private-report-photo.jpg', $encodedReport);
        $this->assertStringNotContainsString('Foreign tenant body sentinel.', $encodedReport);
    }

    private function createMediaForRecord(MemoryRecord $record, string $fileName): void
    {
        Media::query()->create([
            'model_type' => $record->getMorphClass(),
            'model_id' => $record->id,
            'uuid' => (string) Str::uuid(),
            'collection_name' => $record->mediaCollectionName(),
            'name' => 'Private report photo',
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
