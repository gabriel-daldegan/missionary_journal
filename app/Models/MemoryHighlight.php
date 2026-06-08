<?php

namespace App\Models;

use Database\Factories\MemoryHighlightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoryHighlight extends Model
{
    /** @use HasFactory<MemoryHighlightFactory> */
    use HasFactory;

    protected $fillable = [
        'text',
        'sort_order',
    ];

    protected $attributes = [
        'sort_order' => 0,
    ];

    public function memoryRecord(): BelongsTo
    {
        return $this->belongsTo(MemoryRecord::class);
    }
}
