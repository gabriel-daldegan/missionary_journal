<?php

namespace App\Http\Controllers;

use App\Models\MemoryRecord;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemoryMediaController extends Controller
{
    public function show(Tenant $tenant, Media $media): Response|StreamedResponse
    {
        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null) {
            abort(404);
        }

        $record = $this->recordForTenantMedia($tenant, $media);

        Gate::forUser($user)->authorize('viewMedia', [$record, $media]);

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();

        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path, null, [
            'Content-Type' => $media->mime_type ?? 'application/octet-stream',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function recordForTenantMedia(Tenant $tenant, Media $media): MemoryRecord
    {
        $record = $media->model;

        if (! $record instanceof MemoryRecord) {
            abort(404);
        }

        if ($record->tenant_id !== $tenant->id || $media->collection_name !== $record->mediaCollectionName()) {
            abort(404);
        }

        $record->setRelation('tenant', $tenant);

        return $record;
    }
}
