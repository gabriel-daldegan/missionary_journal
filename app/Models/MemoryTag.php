<?php

namespace App\Models;

use Database\Factories\MemoryTagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MemoryTag extends Model
{
    /** @use HasFactory<MemoryTagFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function records(): BelongsToMany
    {
        return $this->belongsToMany(MemoryRecord::class, 'memory_record_tag');
    }
}
