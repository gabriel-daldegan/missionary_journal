<?php

namespace Tests\Feature\Referral;

use App\Constants\ReferralConstants;
use App\Constants\SessionConstants;
use App\Events\Referral\ReferralSucceeded;
use App\Mail\Referral\ReferralRewardEarned;
use App\Models\Discount;
use App\Models\Referral;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class ReferralFlowTest extends FeatureTest
{
    public function test_referral_code_is_stored_in_session_from_url(): void
    {
        $this->withExceptionHandling();
        config(['app.referral.enabled' => true]);

        $referrer = User::factory()->create();
        $referralService = app()->make(ReferralService::class);
        $referralCode = $referralService->getOrCreateReferralCode($referrer);

        $response = $this->get('/login?referralCode='.$referralCode->code);

        $response->assertSessionHas(SessionConstants::REFERRAL_CODE, $referralCode->code);
    }

    public function test_referral_code_persists_in_session_across_requests(): void
    {
        $this->withExceptionHandling();
        config(['app.referral.enabled' => true]);

        $referrer = User::factory()->create();
        $referralService = app()->make(ReferralService::class);
        $referralCode = $referralService->getOrCreateReferralCode($referrer);

        $this->get('/login?referralCode='.$referralCode->code);

        $response = $this->get('/register');
        $response->assertSessionHas(SessionConstants::REFERRAL_CODE, $referralCode->code);
    }

    public function test_referral_reward_is_given_on_email_verification_when_configured(): void
    {
        Mail::fake();
        Event::fake([ReferralSucceeded::class]);

        config([
            'app.referral.enabled' => true,
            'app.referral.trigger' => ReferralConstants::TRIGGER_VERIFIED_REGISTRATION,
            'app.referral.reward_type' => ReferralConstants::REWARD_TYPE_CUSTOM_EVENT,
        ]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->unverified()->create();

        $referralService = app()->make(ReferralService::class);
        $referralCode = $referralService->getOrCreateReferralCode($referrer);

        Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'referral_code' => $referralCode->code,
            'status' => ReferralConstants::STATUS_PENDING,
        ]);

        event(new Verified($referredUser));

        $this->assertDatabaseHas('referrals', [
            'referred_user_id' => $referredUser->id,
            'status' => ReferralConstants::STATUS_REWARDED,
        ]);

        Event::assertDispatched(ReferralSucceeded::class);
    }

    public function test_referral_reward_is_given_on_first_payment_when_configured(): void
    {
        Mail::fake();
        Event::fake([ReferralSucceeded::class]);

        config([
            'app.referral.enabled' => true,
            'app.referral.trigger' => ReferralConstants::TRIGGER_FIRST_PAYMENT,
            'app.referral.reward_type' => ReferralConstants::REWARD_TYPE_CUSTOM_EVENT,
        ]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->create();

        $referralService = app()->make(ReferralService::class);
        $referralCode = $referralService->getOrCreateReferralCode($referrer);

        Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'referral_code' => $referralCode->code,
            'status' => ReferralConstants::STATUS_PENDING,
        ]);

        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $referredUser->id,
            'tenant_id' => $tenant->id,
        ]);

        Transaction::create([
            'uuid' => Str::uuid(),
            'user_id' => $referredUser->id,
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => 2999,
            'status' => 'success',
            'currency_id' => $subscription->currency_id,
        ]);

        $referralService->processReferralOnFirstSubscriptionPayment($subscription);

        $this->assertDatabaseHas('referrals', [
            'referred_user_id' => $referredUser->id,
            'status' => ReferralConstants::STATUS_REWARDED,
        ]);

        Event::assertDispatched(ReferralSucceeded::class);
    }

    public function test_coupon_reward_sends_email_to_referrer(): void
    {
        Mail::fake();

        $discount = Discount::create([
            'name' => 'Referral Reward',
            'description' => 'Reward for referring friends',
            'type' => 'percentage',
            'amount' => 20,
            'is_active' => true,
            'valid_until' => null,
            'action_type' => null,
            'max_redemptions' => -1,
            'max_redemptions_per_user' => 1,
            'is_recurring' => false,
            'redemptions' => 0,
        ]);

        config([
            'app.referral.enabled' => true,
            'app.referral.trigger' => ReferralConstants::TRIGGER_VERIFIED_REGISTRATION,
            'app.referral.reward_type' => ReferralConstants::REWARD_TYPE_COUPON,
            'app.referral.discount_id' => $discount->id,
        ]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->create();

        $referralService = app()->make(ReferralService::class);
        $referralCode = $referralService->getOrCreateReferralCode($referrer);

        Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'referral_code' => $referralCode->code,
            'status' => ReferralConstants::STATUS_PENDING,
        ]);

        $referralService->processVerifiedRegistration($referredUser);

        Mail::assertQueued(ReferralRewardEarned::class, function ($mail) use ($referrer) {
            return $mail->hasTo($referrer->email);
        });

        $this->assertDatabaseHas('discount_codes', [
            'discount_id' => $discount->id,
            'is_referral_reward' => true,
        ]);
    }

    public function test_referral_is_not_created_for_self_referral(): void
    {
        config(['app.referral.enabled' => true]);

        $user = User::factory()->create();
        $referralService = app()->make(ReferralService::class);
        $referralCode = $referralService->getOrCreateReferralCode($user);

        $this->withSession(['referral_code' => $referralCode->code]);

        $referral = $referralService->trackReferral($user, session('referral_code'));

        $this->assertNull($referral);
        $this->assertDatabaseMissing('referrals', [
            'referrer_user_id' => $user->id,
            'referred_user_id' => $user->id,
        ]);
    }

    public function test_referral_is_not_tracked_when_system_is_disabled(): void
    {
        config(['app.referral.enabled' => false]);

        $referrer = User::factory()->create();
        $referralService = app()->make(ReferralService::class);
        $referralCode = $referralService->getOrCreateReferralCode($referrer);

        $newUser = User::factory()->create();
        $referral = $referralService->trackReferral($newUser, $referralCode->code);

        $this->assertNull($referral);
        $this->assertDatabaseMissing('referrals', [
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $newUser->id,
        ]);
    }

    public function test_duplicate_referral_is_not_created(): void
    {
        config(['app.referral.enabled' => true]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->create();
        $referralService = app()->make(ReferralService::class);
        $referralCode = $referralService->getOrCreateReferralCode($referrer);

        $referral1 = $referralService->trackReferral($referredUser, $referralCode->code);
        $referral2 = $referralService->trackReferral($referredUser, $referralCode->code);

        $this->assertEquals($referral1->id, $referral2->id);

        $referralCount = Referral::where('referrer_user_id', $referrer->id)
            ->where('referred_user_id', $referredUser->id)
            ->count();

        $this->assertEquals(1, $referralCount);
    }

    public function test_free_subscription_does_not_trigger_first_payment_reward(): void
    {
        Mail::fake();
        Event::fake([ReferralSucceeded::class]);

        config([
            'app.referral.enabled' => true,
            'app.referral.trigger' => ReferralConstants::TRIGGER_FIRST_PAYMENT,
            'app.referral.reward_type' => ReferralConstants::REWARD_TYPE_CUSTOM_EVENT,
        ]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->create();

        $referralService = app()->make(ReferralService::class);
        $referralCode = $referralService->getOrCreateReferralCode($referrer);

        Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'referral_code' => $referralCode->code,
            'status' => ReferralConstants::STATUS_PENDING,
        ]);

        $tenant = Tenant::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $referredUser->id,
            'tenant_id' => $tenant->id,
        ]);

        Transaction::create([
            'uuid' => Str::uuid(),
            'user_id' => $referredUser->id,
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'amount' => 0,
            'status' => 'success',
            'currency_id' => $subscription->currency_id,
        ]);

        $referralService->processReferralOnFirstSubscriptionPayment($subscription);

        $this->assertDatabaseHas('referrals', [
            'referred_user_id' => $referredUser->id,
            'status' => ReferralConstants::STATUS_PENDING,
        ]);

        Event::assertNotDispatched(ReferralSucceeded::class);
    }

    public function test_referral_link_contains_correct_format(): void
    {
        $user = User::factory()->create();
        $referralService = app()->make(ReferralService::class);

        $link = $referralService->getReferralLink($user);

        $this->assertStringContainsString('referralCode=', $link);
        $this->assertStringContainsString(url('/'), $link);
    }

    public function test_referral_stats_are_accurate(): void
    {
        $referrer = User::factory()->create();
        $referralService = app()->make(ReferralService::class);
        $referralCode = $referralService->getOrCreateReferralCode($referrer);

        for ($i = 0; $i < 2; $i++) {
            $user = User::factory()->create();
            Referral::create([
                'referrer_user_id' => $referrer->id,
                'referred_user_id' => $user->id,
                'referral_code' => $referralCode->code,
                'status' => ReferralConstants::STATUS_PENDING,
            ]);
        }

        $verifiedUser = User::factory()->create();
        Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $verifiedUser->id,
            'referral_code' => $referralCode->code,
            'status' => ReferralConstants::STATUS_VERIFIED,
        ]);

        $rewardedUser = User::factory()->create();
        Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $rewardedUser->id,
            'referral_code' => $referralCode->code,
            'status' => ReferralConstants::STATUS_REWARDED,
        ]);

        $stats = $referralService->getReferralStats($referrer);

        $this->assertEquals(4, $stats['total_referrals']);
        $this->assertEquals(1, $stats['rewarded_referrals']);
    }
}
