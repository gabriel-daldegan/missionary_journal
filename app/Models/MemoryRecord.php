<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\MemoryRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MemoryRecord extends Model implements HasMedia
{
    public const TYPE_DIARY = 'diary';

    public const TYPE_PERIOD = 'period';

    /**
     * @var array<int, string>
     */
    public const ACTIVE_TYPES = [
        self::TYPE_DIARY,
        self::TYPE_PERIOD,
    ];

    /**
     * @var array<int, string>
     */
    public const RESERVED_TYPES = [
    ];

    /** @use HasFactory<MemoryRecordFactory> */
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'type',
        'title',
        'body',
        'notes',
        'experience_date',
        'period_start_date',
        'period_end_date',
        'location_name',
        'people',
        'source',
        'source_metadata',
    ];

    protected $attributes = [
        'type' => self::TYPE_DIARY,
    ];

    protected $casts = [
        'experience_date' => 'datetime:Y-m-d',
        'period_start_date' => 'datetime:Y-m-d',
        'period_end_date' => 'datetime:Y-m-d',
        'people' => 'array',
        'source_metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by_user_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MemoryTag::class, 'memory_record_tag');
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(MemoryHighlight::class)->orderBy('sort_order');
    }

    public function timelineDate(): ?Carbon
    {
        if ($this->type === self::TYPE_PERIOD) {
            return $this->period_start_date;
        }

        return $this->experience_date;
    }

    public function timelineExcerpt(int $limit = 180): string
    {
        return Str::limit((string) ($this->body ?: $this->title ?: ''), $limit);
    }

    public function timelinePhotoCount(): int
    {
        if ($this->relationLoaded('media')) {
            return $this->getMedia($this->mediaCollectionName())->count();
        }

        return $this->media()
            ->where('collection_name', $this->mediaCollectionName())
            ->count();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection($this->mediaCollectionName())
            ->useDisk($this->mediaDiskName())
            ->acceptsMimeTypes($this->allowedPhotoMimeTypes());
    }

    /**
     * @return array<int, string>
     */
    public function allowedPhotoMimeTypes(): array
    {
        /** @var array<int, string> $mimeTypes */
        $mimeTypes = config('memory.media.allowed_mime_types', [
            'image/jpeg',
            'image/png',
            'image/webp',
        ]);

        return $mimeTypes;
    }

    public function mediaCollectionName(): string
    {
        return (string) config('memory.media.collection', 'photos');
    }

    public function mediaDiskName(): string
    {
        return (string) config('memory.media.disk', 'local');
    }

    public function mediaRoute(Media $media): string
    {
        return route('memories.media.show', [
            'tenant' => $this->tenant,
            'media' => $media->uuid,
        ]);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
