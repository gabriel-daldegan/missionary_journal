<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_user_id',
        'referred_user_id',
        'referral_code',
        'status',
        'verified_at',
        'paid_at',
        'rewarded_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'paid_at' => 'datetime',
        'rewarded_at' => 'datetime',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function reward(): HasOne
    {
        return $this->hasOne(ReferralReward::class);
    }
}
