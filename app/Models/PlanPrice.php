<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'price',
        'setup_fee',
        'currency_id',
        'price_per_unit',
        'type',
        'tiers',
        'included_seats',
        'extra_seat_price',
    ];

    protected $casts = [
        'tiers' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (PlanPrice $planPrice) {
            // delete plan_price_payment_provider_data when plan price is updated to recreate provider prices when plan price is updated
            if ($planPrice->getOriginal('price') !== $planPrice->price ||
                $planPrice->getOriginal('setup_fee') !== $planPrice->setup_fee ||
                $planPrice->getOriginal('price_per_unit') !== $planPrice->price_per_unit ||
                $planPrice->getOriginal('type') !== $planPrice->type ||
                $planPrice->getOriginal('tiers') !== $planPrice->tiers ||
                $planPrice->getOriginal('included_seats') !== $planPrice->included_seats ||
                $planPrice->getOriginal('extra_seat_price') !== $planPrice->extra_seat_price
            ) {
                $planPrice->planPricePaymentProviderData()->delete();
            }
        });

        static::deleting(function (PlanPrice $planPrice) {
            // delete plan_price_payment_provider_data when plan price is deleted to recreate provider prices when plan price is deleted
            $planPrice->planPricePaymentProviderData()->delete();
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function planPricePaymentProviderData(): HasMany
    {
        return $this->hasMany(PlanPricePaymentProviderData::class);
    }
}
