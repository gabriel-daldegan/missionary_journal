<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

class Team extends Model
{
    use HasFactory, HasRoles;

    protected $fillable = [
        'name',
        'tenant_id',
        'uuid',
    ];

    protected string $guard_name = 'web';

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenantUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantUser::class,
            'team_tenant_user',
            'team_id',
            'tenant_user_id'
        )->withPivot('id')
            ->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        // used to find a model by its uuid instead of its id
        return 'uuid';
    }
}
