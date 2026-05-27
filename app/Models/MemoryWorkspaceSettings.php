<?php

namespace App\Models;

use Database\Factories\MemoryWorkspaceSettingsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoryWorkspaceSettings extends Model
{
    public const MODE_SHARED_EDITING = 'shared_editing';

    /**
     * @var array<int, string>
     */
    public const ACTIVE_COLLABORATION_MODES = [
        self::MODE_SHARED_EDITING,
    ];

    /** @use HasFactory<MemoryWorkspaceSettingsFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'collaboration_mode',
    ];

    protected $attributes = [
        'collaboration_mode' => self::MODE_SHARED_EDITING,
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
