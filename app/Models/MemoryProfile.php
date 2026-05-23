<?php

namespace App\Models;

use Database\Factories\MemoryProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoryProfile extends Model
{
    /** @use HasFactory<MemoryProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_name',
        'preferred_locale',
        'mission_context',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
