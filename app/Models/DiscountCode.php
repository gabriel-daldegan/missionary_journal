<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'discount_id',
        'code',
        'referral_reward_id',
        'is_referral_reward',
    ];

    protected $casts = [
        'is_referral_reward' => 'boolean',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountCodeRedemption::class);
    }

    public function referralReward(): BelongsTo
    {
        return $this->belongsTo(ReferralReward::class);
    }
}
