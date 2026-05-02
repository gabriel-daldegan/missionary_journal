<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'starts_at',
        'ends_at',
        'is_active',
        'is_dismissible',
        'show_for_customers',
        'show_on_frontend',
        'show_on_user_dashboard',
    ];
}
