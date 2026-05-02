<?php

namespace Tests\Feature\Http\Middleware;

use App\Constants\DiscountConstants;
use App\Constants\SessionConstants;
use App\Models\Discount;
use App\Services\SessionService;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class TrackCouponCodeTest extends FeatureTest
{
    public function test_valid_coupon_code_is_stored_in_session(): void
    {
        $this->withExceptionHandling();

        $discount = $this->createActiveDiscount();
        $code = $this->createDiscountCode($discount);

        $this->get('/login?'.DiscountConstants::COUPON_QUERY_PARAMETER.'='.$code);

        $sessionService = app(SessionService::class);
        $this->assertEquals($code, $sessionService->getCouponCode());
    }

    public function test_invalid_coupon_code_is_not_stored_in_session(): void
    {
        $this->withExceptionHandling();

        $this->get('/login?'.DiscountConstants::COUPON_QUERY_PARAMETER.'=INVALIDCODE123');

        $response = $this->get('/login?'.DiscountConstants::COUPON_QUERY_PARAMETER.'=INVALIDCODE123');
        $response->assertSessionMissing(SessionConstants::COUPON_CODE);
    }

    public function test_inactive_discount_coupon_code_is_not_stored_in_session(): void
    {
        $this->withExceptionHandling();

        $discount = $this->createActiveDiscount(['is_active' => false]);
        $code = $this->createDiscountCode($discount);

        $response = $this->get('/login?'.DiscountConstants::COUPON_QUERY_PARAMETER.'='.$code);

        $response->assertSessionMissing(SessionConstants::COUPON_CODE);
    }

    public function test_expired_discount_coupon_code_is_not_stored_in_session(): void
    {
        $this->withExceptionHandling();

        $discount = $this->createActiveDiscount(['valid_until' => now()->subDay()]);
        $code = $this->createDiscountCode($discount);

        $response = $this->get('/login?'.DiscountConstants::COUPON_QUERY_PARAMETER.'='.$code);

        $response->assertSessionMissing(SessionConstants::COUPON_CODE);
    }

    public function test_coupon_code_is_not_stored_when_not_present(): void
    {
        $this->withExceptionHandling();

        $response = $this->get('/login');

        $response->assertSessionMissing(SessionConstants::COUPON_CODE);
    }

    public function test_coupon_code_is_stored_from_any_route(): void
    {
        $this->withExceptionHandling();

        $discount = $this->createActiveDiscount();
        $code = $this->createDiscountCode($discount);

        $this->get('/register?'.DiscountConstants::COUPON_QUERY_PARAMETER.'='.$code);

        $sessionService = app(SessionService::class);
        $this->assertEquals($code, $sessionService->getCouponCode());
    }

    public function test_coupon_code_is_overwritten_with_new_code(): void
    {
        $this->withExceptionHandling();

        $discount = $this->createActiveDiscount();
        $oldCode = $this->createDiscountCode($discount);
        $newCode = $this->createDiscountCode($discount);

        // Store old code in session
        app(SessionService::class)->saveCouponCode($oldCode);

        $this->get('/login?'.DiscountConstants::COUPON_QUERY_PARAMETER.'='.$newCode);

        $sessionService = app(SessionService::class);
        $this->assertEquals($newCode, $sessionService->getCouponCode());
    }

    public function test_coupon_code_persists_across_requests(): void
    {
        $this->withExceptionHandling();

        $discount = $this->createActiveDiscount();
        $code = $this->createDiscountCode($discount);

        $this->get('/login?'.DiscountConstants::COUPON_QUERY_PARAMETER.'='.$code);

        $this->get('/login');
        $sessionService = app(SessionService::class);
        $this->assertEquals($code, $sessionService->getCouponCode());

        $this->get('/register');
        $this->assertEquals($code, $sessionService->getCouponCode());
    }

    public function test_coupon_code_expires_after_8_hours(): void
    {
        $this->withExceptionHandling();

        $discount = $this->createActiveDiscount();
        $code = $this->createDiscountCode($discount);

        // Manually store with a timestamp 9 hours ago (beyond 8-hour TTL)
        session([SessionConstants::COUPON_CODE => [
            'code' => $code,
            'stored_at' => now()->subHours(9),
        ]]);

        $sessionService = app(SessionService::class);
        $this->assertNull($sessionService->getCouponCode());
    }

    public function test_coupon_code_is_valid_within_ttl(): void
    {
        $this->withExceptionHandling();

        $discount = $this->createActiveDiscount();
        $code = $this->createDiscountCode($discount);

        // Manually store with a timestamp 7 hours ago (within 8-hour TTL)
        session([SessionConstants::COUPON_CODE => [
            'code' => $code,
            'stored_at' => now()->subHours(7),
        ]]);

        $sessionService = app(SessionService::class);
        $this->assertEquals($code, $sessionService->getCouponCode());
    }

    public function test_invalid_coupon_clears_existing_session_coupon(): void
    {
        $this->withExceptionHandling();

        $discount = $this->createActiveDiscount();
        $code = $this->createDiscountCode($discount);

        // Store a valid coupon first
        app(SessionService::class)->saveCouponCode($code);

        // Visit with an invalid coupon — should clear the stored one
        $this->get('/login?'.DiscountConstants::COUPON_QUERY_PARAMETER.'=INVALIDCODE');

        $response = $this->get('/login');
        $response->assertSessionMissing(SessionConstants::COUPON_CODE);
    }

    private function createActiveDiscount(array $overrides = []): Discount
    {
        return Discount::create(array_merge([
            'name' => 'test',
            'description' => 'test',
            'type' => 'percentage',
            'amount' => 10,
            'is_active' => true,
            'valid_until' => null,
            'action_type' => null,
            'max_redemptions' => -1,
            'max_redemptions_per_user' => -1,
            'is_recurring' => false,
            'redemptions' => 0,
        ], $overrides));
    }

    private function createDiscountCode(Discount $discount): string
    {
        $code = Str::random(10);
        $discount->codes()->create(['code' => $code]);

        return $code;
    }
}
