<?php

namespace Tests\Feature\Services;

use App\Constants\ReferralConstants;
use App\Events\Referral\ReferralSucceeded;
use App\Mail\Referral\ReferralRewardEarned;
use App\Models\Discount;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\ReferralReward;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class ReferralServiceTest extends FeatureTest
{
    private ReferralService $referralService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->referralService = app()->make(ReferralService::class);
    }

    public function test_is_enabled_returns_false_when_disabled(): void
    {
        config(['app.referral.enabled' => false]);

        $this->assertFalse($this->referralService->isEnabled());
    }

    public function test_is_enabled_returns_true_when_enabled(): void
    {
        config(['app.referral.enabled' => true]);

        $this->assertTrue($this->referralService->isEnabled());
    }

    public function test_get_or_create_referral_code_creates_new_code(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->referralCode);

        $referralCode = $this->referralService->getOrCreateReferralCode($user);

        $this->assertNotNull($referralCode);
        $this->assertEquals($user->id, $referralCode->user_id);
        $this->assertNotEmpty($referralCode->code);
        $this->assertStringStartsWith(ReferralConstants::CODE_PREFIX, $referralCode->code);
        $this->assertEquals(strlen(ReferralConstants::CODE_PREFIX) + ReferralConstants::CODE_LENGTH, strlen($referralCode->code));
    }

    public function test_get_or_create_referral_code_returns_existing_code(): void
    {
        $user = User::factory()->create();

        $existingCode = ReferralCode::create([
            'user_id' => $user->id,
            'code' => 'TESTCODE1234',
        ]);

        $referralCode = $this->referralService->getOrCreateReferralCode($user);

        $this->assertEquals($existingCode->id, $referralCode->id);
        $this->assertEquals('TESTCODE1234', $referralCode->code);
    }

    public function test_find_referrer_by_code_returns_user(): void
    {
        $user = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($user);

        $foundUser = $this->referralService->findReferrerByCode($referralCode->code);

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
    }

    public function test_find_referrer_by_code_returns_null_for_invalid_code(): void
    {
        $foundUser = $this->referralService->findReferrerByCode('INVALIDCODE');

        $this->assertNull($foundUser);
    }

    public function test_track_referral_creates_referral_record(): void
    {
        config(['app.referral.enabled' => true]);

        $referrer = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($referrer);

        $referredUser = User::factory()->create();

        $referral = $this->referralService->trackReferral($referredUser, $referralCode->code);

        $this->assertNotNull($referral);
        $this->assertEquals($referrer->id, $referral->referrer_user_id);
        $this->assertEquals($referredUser->id, $referral->referred_user_id);
        $this->assertEquals($referralCode->code, $referral->referral_code);
        $this->assertEquals(ReferralConstants::STATUS_PENDING, $referral->status);
    }

    public function test_track_referral_increments_uses_count(): void
    {
        config(['app.referral.enabled' => true]);

        $referrer = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($referrer);

        $this->assertEquals(0, $referralCode->uses_count);

        $referredUser = User::factory()->create();
        $this->referralService->trackReferral($referredUser, $referralCode->code);

        $referralCode->refresh();
        $this->assertEquals(1, $referralCode->uses_count);
    }

    public function test_track_referral_returns_null_when_disabled(): void
    {
        config(['app.referral.enabled' => false]);

        $referrer = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($referrer);

        $referredUser = User::factory()->create();

        $referral = $this->referralService->trackReferral($referredUser, $referralCode->code);

        $this->assertNull($referral);
    }

    public function test_track_referral_prevents_self_referral(): void
    {
        config(['app.referral.enabled' => true]);

        $user = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($user);

        $referral = $this->referralService->trackReferral($user, $referralCode->code);

        $this->assertNull($referral);
    }

    public function test_track_referral_returns_null_for_invalid_code(): void
    {
        config(['app.referral.enabled' => true]);

        $referredUser = User::factory()->create();

        $referral = $this->referralService->trackReferral($referredUser, 'INVALIDCODE');

        $this->assertNull($referral);
    }

    public function test_track_referral_returns_existing_referral_for_duplicate(): void
    {
        config(['app.referral.enabled' => true]);

        $referrer = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($referrer);

        $referredUser = User::factory()->create();

        $referral1 = $this->referralService->trackReferral($referredUser, $referralCode->code);
        $referral2 = $this->referralService->trackReferral($referredUser, $referralCode->code);

        $this->assertEquals($referral1->id, $referral2->id);

        $referralCount = Referral::where('referrer_user_id', $referrer->id)
            ->where('referred_user_id', $referredUser->id)
            ->count();

        $this->assertEquals(1, $referralCount);
    }

    public function test_process_verified_registration_updates_status_and_rewards(): void
    {
        Mail::fake();
        config([
            'app.referral.enabled' => true,
            'app.referral.trigger' => ReferralConstants::TRIGGER_VERIFIED_REGISTRATION,
            'app.referral.reward_type' => ReferralConstants::REWARD_TYPE_CUSTOM_EVENT,
        ]);

        Event::fake([ReferralSucceeded::class]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($referrer);

        Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'referral_code' => $referralCode->code,
            'status' => ReferralConstants::STATUS_PENDING,
        ]);

        $this->referralService->processVerifiedRegistration($referredUser);

        $referral = Referral::where('referred_user_id', $referredUser->id)->first();

        $this->assertEquals(ReferralConstants::STATUS_REWARDED, $referral->status);
        $this->assertNotNull($referral->verified_at);
        $this->assertNotNull($referral->rewarded_at);

        Event::assertDispatched(ReferralSucceeded::class, function ($event) use ($referrer, $referredUser) {
            return $event->referrer->id === $referrer->id && $event->referredUser->id === $referredUser->id;
        });
    }

    public function test_process_verified_registration_does_nothing_when_trigger_is_first_payment(): void
    {
        config([
            'app.referral.enabled' => true,
            'app.referral.trigger' => ReferralConstants::TRIGGER_FIRST_PAYMENT,
        ]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($referrer);

        $referral = Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'referral_code' => $referralCode->code,
            'status' => ReferralConstants::STATUS_PENDING,
        ]);

        $this->referralService->processVerifiedRegistration($referredUser);

        $referral->refresh();
        $this->assertEquals(ReferralConstants::STATUS_PENDING, $referral->status);
    }

    public function test_process_first_subscription_payment_updates_status_and_rewards(): void
    {
        Mail::fake();
        config([
            'app.referral.enabled' => true,
            'app.referral.trigger' => ReferralConstants::TRIGGER_FIRST_PAYMENT,
            'app.referral.reward_type' => ReferralConstants::REWARD_TYPE_CUSTOM_EVENT,
        ]);

        Event::fake([ReferralSucceeded::class]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($referrer);

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
            'amount' => 1000,
            'status' => 'success',
            'currency_id' => $subscription->currency_id,
        ]);

        $this->referralService->processReferralOnFirstSubscriptionPayment($subscription);

        $referral = Referral::where('referred_user_id', $referredUser->id)->first();

        $this->assertEquals(ReferralConstants::STATUS_REWARDED, $referral->status);
        $this->assertNotNull($referral->paid_at);
        $this->assertNotNull($referral->rewarded_at);

        Event::assertDispatched(ReferralSucceeded::class);
    }

    public function test_process_first_subscription_payment_does_nothing_for_free_subscription(): void
    {
        config([
            'app.referral.enabled' => true,
            'app.referral.trigger' => ReferralConstants::TRIGGER_FIRST_PAYMENT,
        ]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($referrer);

        $referral = Referral::create([
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

        $this->referralService->processReferralOnFirstSubscriptionPayment($subscription);

        $referral->refresh();
        $this->assertEquals(ReferralConstants::STATUS_PENDING, $referral->status);
    }

    public function test_process_coupon_reward_creates_discount_code_and_sends_email(): void
    {
        Mail::fake();
        config([
            'app.referral.enabled' => true,
            'app.referral.trigger' => ReferralConstants::TRIGGER_VERIFIED_REGISTRATION,
            'app.referral.reward_type' => ReferralConstants::REWARD_TYPE_COUPON,
        ]);

        $discount = Discount::create([
            'name' => 'Referral Discount',
            'description' => 'Reward for referrals',
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => true,
            'valid_until' => null,
            'action_type' => null,
            'max_redemptions' => -1,
            'max_redemptions_per_user' => 1,
            'is_recurring' => false,
            'redemptions' => 0,
        ]);

        config(['app.referral.discount_id' => $discount->id]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($referrer);

        Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'referral_code' => $referralCode->code,
            'status' => ReferralConstants::STATUS_PENDING,
        ]);

        $this->referralService->processVerifiedRegistration($referredUser);

        $reward = ReferralReward::where('referrer_user_id', $referrer->id)->first();

        $this->assertNotNull($reward);
        $this->assertEquals(ReferralConstants::REWARD_TYPE_COUPON, $reward->reward_type);
        $this->assertNotNull($reward->discount_code_id);

        $discountCode = $reward->discountCode;
        $this->assertNotNull($discountCode);
        $this->assertTrue($discountCode->is_referral_reward);
        $this->assertStringStartsWith(ReferralConstants::CODE_PREFIX, $discountCode->code);

        Mail::assertQueued(ReferralRewardEarned::class, function ($mail) use ($referrer) {
            return $mail->hasTo($referrer->email);
        });
    }

    public function test_process_custom_event_reward_dispatches_event(): void
    {
        Mail::fake();
        config([
            'app.referral.enabled' => true,
            'app.referral.trigger' => ReferralConstants::TRIGGER_VERIFIED_REGISTRATION,
            'app.referral.reward_type' => ReferralConstants::REWARD_TYPE_CUSTOM_EVENT,
        ]);

        Event::fake([ReferralSucceeded::class]);

        $referrer = User::factory()->create();
        $referredUser = User::factory()->create();
        $referralCode = $this->referralService->getOrCreateReferralCode($referrer);

        Referral::create([
            'referrer_user_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
            'referral_code' => $referralCode->code,
            'status' => ReferralConstants::STATUS_PENDING,
        ]);

        $this->referralService->processVerifiedRegistration($referredUser);

        $reward = ReferralReward::where('referrer_user_id', $referrer->id)->first();

        $this->assertNotNull($reward);
        $this->assertEquals(ReferralConstants::REWARD_TYPE_CUSTOM_EVENT, $reward->reward_type);
        $this->assertNull($reward->discount_code_id);

        Event::assertDispatched(ReferralSucceeded::class, function ($event) use ($referrer, $referredUser) {
            return $event->referrer->id === $referrer->id && $event->referredUser->id === $referredUser->id;
        });
    }

    public function test_get_referral_link_returns_correct_url(): void
    {
        $user = User::factory()->create();

        $link = $this->referralService->getReferralLink($user);

        $referralCode = $user->fresh()->referralCode;

        $this->assertNotNull($referralCode);
        $this->assertEquals(url('/?'.ReferralConstants::HTTP_PARAM_REFERRAL_CODE.'='.$referralCode->code), $link);
    }
}
