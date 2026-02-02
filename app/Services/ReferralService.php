<?php

namespace App\Services;

use App\Constants\ReferralConstants;
use App\Constants\TransactionStatus;
use App\Events\Referral\ReferralSucceeded;
use App\Mail\Referral\ReferralRewardEarned;
use App\Models\Discount;
use App\Models\DiscountCode;
use App\Models\Order;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\ReferralReward;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReferralService
{
    public function isEnabled(): bool
    {
        return (bool) config('app.referral.enabled', false);
    }

    public function isDiscountUsedAsReward(Discount $discount): bool
    {
        $rewardType = config('app.referral.reward_type');

        return $rewardType === ReferralConstants::REWARD_TYPE_COUPON &&
               config('app.referral.discount_id') == $discount->id;
    }

    public function getOrCreateReferralCode(User $user): ReferralCode
    {
        $referralCode = $user->referralCode;

        if (! $referralCode) {
            $referralCode = $this->createReferralCode($user);
        }

        return $referralCode;
    }

    private function createReferralCode(User $user): ReferralCode
    {
        do {
            $code = $this->generateUniqueCode();
        } while (ReferralCode::where('code', $code)->exists());

        return ReferralCode::create([
            'user_id' => $user->id,
            'code' => $code,
        ]);
    }

    private function generateUniqueCode(): string
    {
        return ReferralConstants::CODE_PREFIX.strtoupper(Str::random(ReferralConstants::CODE_LENGTH));
    }

    public function findReferrerByCode(string $code): ?User
    {
        $referralCode = ReferralCode::where('code', $code)->first();

        return $referralCode?->user;
    }

    public function trackReferral(User $referredUser, string $referralCode): ?Referral
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $referrer = $this->findReferrerByCode($referralCode);

        if (! $referrer || $referrer->id === $referredUser->id) {
            return null;
        }

        $existingReferral = Referral::where('referrer_user_id', $referrer->id)
            ->where('referred_user_id', $referredUser->id)
            ->first();

        if ($existingReferral) {
            return $existingReferral;
        }

        $referral = Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'referral_code' => $referralCode,
            'status' => ReferralConstants::STATUS_PENDING,
        ]);

        ReferralCode::where('code', $referralCode)->increment('uses_count');

        return $referral;
    }

    public function processVerifiedRegistration(User $verifiedUser): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $trigger = config('app.referral.trigger');

        if ($trigger !== ReferralConstants::TRIGGER_VERIFIED_REGISTRATION) {
            return;
        }

        $referral = Referral::where('referred_user_id', $verifiedUser->id)
            ->where('status', ReferralConstants::STATUS_PENDING)
            ->first();

        if (! $referral) {
            return;
        }

        $referral->update([
            'status' => ReferralConstants::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);

        $this->processReward($referral);
    }

    public function processReferralOnFirstOrderPayment(Order $order): void
    {
        $hasPaidTransaction = $order->transactions()
            ->where('amount', '>', 0)
            ->where('status', TransactionStatus::SUCCESS->value)
            ->exists();

        if (! $hasPaidTransaction) {
            return;
        }

        $this->processFirstPayment($order->user);
    }

    public function processReferralOnFirstSubscriptionPayment(Subscription $subscription): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $hasPaidTransaction = $subscription->transactions()
            ->where('amount', '>', 0)
            ->where('status', TransactionStatus::SUCCESS->value)
            ->exists();

        if (! $hasPaidTransaction) {
            return;
        }

        $this->processFirstPayment($subscription->user);
    }

    private function processFirstPayment(User $paidUser): void
    {
        $trigger = config('app.referral.trigger');

        if ($trigger !== ReferralConstants::TRIGGER_FIRST_PAYMENT) {
            return;
        }

        $referral = Referral::where('referred_user_id', $paidUser->id)
            ->whereIn('status', [ReferralConstants::STATUS_PENDING, ReferralConstants::STATUS_VERIFIED])
            ->first();

        if (! $referral) {
            return;
        }

        $referral->update([
            'status' => ReferralConstants::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $this->processReward($referral);
    }

    private function processReward(Referral $referral): void
    {
        $rewardType = config('app.referral.reward_type');

        if ($rewardType === ReferralConstants::REWARD_TYPE_COUPON) {
            $this->processCouponReward($referral);
        } elseif ($rewardType === ReferralConstants::REWARD_TYPE_CUSTOM_EVENT) {
            $this->processCustomEventReward($referral);
        }

        $referral->update([
            'status' => ReferralConstants::STATUS_REWARDED,
            'rewarded_at' => now(),
        ]);
    }

    private function processCouponReward(Referral $referral): void
    {
        $discountId = config('app.referral.discount_id');

        if (! $discountId) {
            return;
        }

        $discount = Discount::find($discountId);

        if (! $discount || ! $discount->is_active) {
            return;
        }

        do {
            $code = 'REF-'.strtoupper(Str::random(8));
        } while (DiscountCode::where('code', $code)->exists());

        $discountCode = DiscountCode::create([
            'discount_id' => $discount->id,
            'code' => $code,
            'is_referral_reward' => true,
        ]);

        $reward = ReferralReward::create([
            'referral_id' => $referral->id,
            'referrer_user_id' => $referral->referrer_user_id,
            'reward_type' => ReferralConstants::REWARD_TYPE_COUPON,
            'discount_code_id' => $discountCode->id,
        ]);

        $discountCode->update(['referral_reward_id' => $reward->id]);

        Mail::to($referral->referrer->email)
            ->send(new ReferralRewardEarned($referral, $discountCode));
    }

    private function processCustomEventReward(Referral $referral): void
    {
        ReferralReward::create([
            'referral_id' => $referral->id,
            'referrer_user_id' => $referral->referrer_user_id,
            'reward_type' => ReferralConstants::REWARD_TYPE_CUSTOM_EVENT,
        ]);

        ReferralSucceeded::dispatch($referral->referrer, $referral->referredUser, $referral);
    }

    public function getReferralStats(User $user): array
    {
        return [
            'total_referrals' => $user->referrals()->count(),
            'rewarded_referrals' => $user->referrals()->where('status', ReferralConstants::STATUS_REWARDED)->count(),
            'total_rewards' => $user->referralRewards()->count(),
        ];
    }

    public function getReferralLink(User $user): string
    {
        $referralCode = $this->getOrCreateReferralCode($user);

        return url()->query('/', [ReferralConstants::HTTP_PARAM_REFERRAL_CODE => $referralCode->code]);
    }
}
