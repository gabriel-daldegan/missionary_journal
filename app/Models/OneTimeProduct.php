<?php

namespace App\Models;

use App\Constants\PaymentProviderConstants;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OneTimeProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_quantity',
        'metadata',
        'features',
        'is_active',
        'is_visible',
    ];

    protected $casts = [
        'features' => 'array',
        'metadata' => 'array',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(OneTimeProductPrice::class);
    }

    protected static function booted(): void
    {
        static::updating(function (OneTimeProduct $oneTimeProduct) {
            // booleans are a bit tricky to compare, so we use boolval to compare them
            if ($oneTimeProduct->isDirty([
                'max_quantity',
            ])) {
                // delete all except lemon squeezy & creem stuff (because their data are not auto-created on update as with other providers)
                $manualProviderIds = PaymentProvider::whereIn('slug', [
                    PaymentProviderConstants::LEMON_SQUEEZY_SLUG,
                    PaymentProviderConstants::CREEM_SLUG,
                ])->pluck('id');
                $oneTimeProduct->paymentProviderData()->whereNotIn('payment_provider_id', $manualProviderIds)->delete();
                foreach ($oneTimeProduct->prices as $price) {
                    $price->pricePaymentProviderData()->delete();
                }
            }
        });

        static::deleting(function (OneTimeProduct $oneTimeProduct) {
            if (! $oneTimeProduct->isDeletable()) {
                throw new Exception('Cannot delete a one-time product that has been ordered.');
            }

            $oneTimeProduct->paymentProviderData()->delete();
            foreach ($oneTimeProduct->prices as $price) {
                $price->pricePaymentProviderData()->delete();
            }

            $oneTimeProduct->prices()->delete();
        });
    }

    public function isDeletable()
    {
        return ! $this->orderItems()->exists();
    }

    public function paymentProviderData(): HasMany
    {
        return $this->hasMany(OneTimeProductPaymentProviderData::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
