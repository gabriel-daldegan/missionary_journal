<?php

namespace App\Services;

use App\Models\MemoryProfile;
use App\Models\MemoryRecord;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MemoryValidationReportService
{
    public function __construct(
        private readonly MemoryWorkspaceSettingsService $workspaceSettingsService
    ) {}

    /**
     * @return array{
     *     summary: array{
     *         generated_at: string,
     *         total_tenants: int,
     *         tenants_with_completed_profiles: int,
     *         tenants_with_first_record: int,
     *         active_validation_tenants: int,
     *         tenants_spanning_two_weeks: int,
     *         tenants_with_collaborator_engagement: int,
     *         tenants_with_media: int,
     *         tenants_with_email_source: int
     *     },
     *     tenants: array<int, array{
     *         tenant_id: int,
     *         tenant_uuid: string,
     *         tenant_name: string,
     *         member_count: int,
     *         completed_profile_count: int,
     *         record_count: int,
     *         first_record_created_at: string|null,
     *         latest_record_created_at: string|null,
     *         activity_span_days: int,
     *         activity_spans_two_weeks: bool,
     *         active_validation_tenant: bool,
     *         collaborator_participant_count: int,
     *         collaborator_edit_count: int,
     *         has_collaborator_engagement: bool,
     *         shared_editing_enabled: bool,
     *         media_record_count: int,
     *         media_attachment_count: int,
     *         email_source_record_count: int,
     *         has_email_source_marker: bool
     *     }>
     * }
     */
    public function report(): array
    {
        /** @var Collection<int, array<string, mixed>> $tenantReports */
        $tenantReports = collect();

        Tenant::query()
            ->select(['id', 'uuid', 'name', 'created_at'])
            ->with([
                'users:id',
                'memoryWorkspaceSettings:id,tenant_id,collaboration_mode',
            ])
            ->orderBy('name')
            ->chunk(50, function (Collection $tenants) use (&$tenantReports): void {
                $tenantReports = $tenantReports->merge(
                    $tenants->map(fn (Tenant $tenant): array => $this->reportForTenant($tenant))
                );
            });

        $tenantReports = $tenantReports->values();

        return [
            'summary' => [
                'generated_at' => now()->toDateTimeString(),
                'total_tenants' => $tenantReports->count(),
                'tenants_with_completed_profiles' => $tenantReports
                    ->where('completed_profile_count', '>', 0)
                    ->count(),
                'tenants_with_first_record' => $tenantReports
                    ->where('record_count', '>', 0)
                    ->count(),
                'active_validation_tenants' => $tenantReports
                    ->where('active_validation_tenant', true)
                    ->count(),
                'tenants_spanning_two_weeks' => $tenantReports
                    ->where('activity_spans_two_weeks', true)
                    ->count(),
                'tenants_with_collaborator_engagement' => $tenantReports
                    ->where('has_collaborator_engagement', true)
                    ->count(),
                'tenants_with_media' => $tenantReports
                    ->where('media_attachment_count', '>', 0)
                    ->count(),
                'tenants_with_email_source' => $tenantReports
                    ->where('has_email_source_marker', true)
                    ->count(),
            ],
            'tenants' => $tenantReports->all(),
        ];
    }

    /**
     * @return array{
     *     tenant_id: int,
     *     tenant_uuid: string,
     *     tenant_name: string,
     *     member_count: int,
     *     completed_profile_count: int,
     *     record_count: int,
     *     first_record_created_at: string|null,
     *     latest_record_created_at: string|null,
     *     activity_span_days: int,
     *     activity_spans_two_weeks: bool,
     *     active_validation_tenant: bool,
     *     collaborator_participant_count: int,
     *     collaborator_edit_count: int,
     *     has_collaborator_engagement: bool,
     *     shared_editing_enabled: bool,
     *     media_record_count: int,
     *     media_attachment_count: int,
     *     email_source_record_count: int,
     *     has_email_source_marker: bool
     * }
     */
    private function reportForTenant(Tenant $tenant): array
    {
        $memberIds = $tenant->users
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values();

        /** @var Collection<int, MemoryRecord> $records */
        $records = MemoryRecord::query()
            ->select([
                'id',
                'tenant_id',
                'author_user_id',
                'last_edited_by_user_id',
                'source',
                'created_at',
            ])
            ->whereBelongsTo($tenant)
            ->get();

        $firstRecord = $records->sortBy('created_at')->first();
        $latestRecord = $records->sortByDesc('created_at')->first();
        $activitySpanDays = $this->activitySpanDays($firstRecord, $latestRecord);
        $participantUserIds = $this->participantUserIds($records);
        $collaboratorEditCount = $records
            ->filter(fn (MemoryRecord $record): bool => $this->wasEditedByAnotherUser($record))
            ->count();
        $mediaUsage = $this->mediaUsageForRecords($records->pluck('id'));
        $emailSourceRecordCount = $records
            ->filter(fn (MemoryRecord $record): bool => $this->isEmailSourced($record))
            ->count();
        $completedProfileCount = MemoryProfile::query()
            ->whereIn('user_id', $memberIds)
            ->whereNotNull('completed_at')
            ->count();

        return [
            'tenant_id' => (int) $tenant->id,
            'tenant_uuid' => (string) $tenant->uuid,
            'tenant_name' => (string) $tenant->name,
            'member_count' => $memberIds->count(),
            'completed_profile_count' => $completedProfileCount,
            'record_count' => $records->count(),
            'first_record_created_at' => $firstRecord?->created_at?->toDateTimeString(),
            'latest_record_created_at' => $latestRecord?->created_at?->toDateTimeString(),
            'activity_span_days' => $activitySpanDays,
            'activity_spans_two_weeks' => $activitySpanDays >= 14,
            'active_validation_tenant' => $completedProfileCount > 0 && $records->isNotEmpty(),
            'collaborator_participant_count' => $participantUserIds->count(),
            'collaborator_edit_count' => $collaboratorEditCount,
            'has_collaborator_engagement' => $memberIds->count() > 1 && $participantUserIds->count() > 1,
            'shared_editing_enabled' => $this->workspaceSettingsService
                ->isActiveCollaborationMode($this->workspaceSettingsService->collaborationMode($tenant)),
            'media_record_count' => $mediaUsage['record_count'],
            'media_attachment_count' => $mediaUsage['attachment_count'],
            'email_source_record_count' => $emailSourceRecordCount,
            'has_email_source_marker' => $emailSourceRecordCount > 0,
        ];
    }

    private function activitySpanDays(?MemoryRecord $firstRecord, ?MemoryRecord $latestRecord): int
    {
        if ($firstRecord?->created_at === null || $latestRecord?->created_at === null) {
            return 0;
        }

        return (int) abs($latestRecord->created_at->diffInDays($firstRecord->created_at));
    }

    /**
     * @param  Collection<int, MemoryRecord>  $records
     * @return Collection<int, int<0, max>>
     */
    private function participantUserIds(Collection $records): Collection
    {
        return $records
            ->flatMap(fn (MemoryRecord $record): array => [
                $record->author_user_id,
                $record->last_edited_by_user_id,
            ])
            ->filter(fn (mixed $userId): bool => $userId !== null)
            ->map(fn (mixed $userId): int => (int) $userId)
            ->unique()
            ->values();
    }

    private function wasEditedByAnotherUser(MemoryRecord $record): bool
    {
        return $record->author_user_id !== null
            && $record->last_edited_by_user_id !== null
            && (int) $record->author_user_id !== (int) $record->last_edited_by_user_id;
    }

    /**
     * @param  Collection<int, mixed>  $recordIds
     * @return array{record_count: int, attachment_count: int}
     */
    private function mediaUsageForRecords(Collection $recordIds): array
    {
        $recordIds = $recordIds
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($recordIds->isEmpty()) {
            return [
                'record_count' => 0,
                'attachment_count' => 0,
            ];
        }

        $mediaRecordIds = Media::query()
            ->whereIn('model_type', $this->memoryRecordMorphTypes())
            ->whereIn('model_id', $recordIds)
            ->where('collection_name', (new MemoryRecord)->mediaCollectionName())
            ->pluck('model_id')
            ->map(fn (mixed $id): int => (int) $id);

        return [
            'record_count' => $mediaRecordIds->unique()->count(),
            'attachment_count' => $mediaRecordIds->count(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function memoryRecordMorphTypes(): array
    {
        return collect([
            MemoryRecord::class,
            (new MemoryRecord)->getMorphClass(),
        ])
            ->unique()
            ->values()
            ->all();
    }

    private function isEmailSourced(MemoryRecord $record): bool
    {
        return mb_strtolower((string) $record->source) === 'email';
    }
}
