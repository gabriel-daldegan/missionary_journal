<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationProvider extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];
}
