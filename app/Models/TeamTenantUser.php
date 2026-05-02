<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamTenantUser extends Pivot
{
    public $incrementing = true;
}
