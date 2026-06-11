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

class MemoryRecord extends Model
{
    public const TYPE_DIARY = 'diary';

    public const TYPE_PERIOD = 'period';

    /**
     * @var array<int, string>
     */
    public const ACTIVE_TYPES = [
        self::TYPE_DIARY,
    ];

    /**
     * @var array<int, string>
     */
    public const RESERVED_TYPES = [
        self::TYPE_PERIOD,
    ];

    /** @use HasFactory<MemoryRecordFactory> */
    use HasFactory;

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
        $sourceMetadata = $this->source_metadata;

        if (! is_array($sourceMetadata)) {
            return 0;
        }

        return (int) ($sourceMetadata['photo_count'] ?? 0);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
