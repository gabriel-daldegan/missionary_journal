<?php

namespace Tests\Feature\Http\Middleware;

use App\Constants\ReferralConstants;
use App\Constants\SessionConstants;
use App\Models\ReferralCode;
use App\Models\User;
use Tests\Feature\FeatureTest;

class TrackReferralCodeTest extends FeatureTest
{
    public function test_referral_code_is_stored_in_session(): void
    {
        $this->withExceptionHandling();
        config(['app.referral.enabled' => true]);

        $user = User::factory()->create();
        ReferralCode::create([
            'user_id' => $user->id,
            'code' => 'TESTCODE123',
        ]);

        $response = $this->get('/login?'.ReferralConstants::HTTP_PARAM_REFERRAL_CODE.'=TESTCODE123');

        $response->assertSessionHas(SessionConstants::REFERRAL_CODE, 'TESTCODE123');
    }

    public function test_referral_code_is_stored_from_any_route(): void
    {
        $this->withExceptionHandling();
        config(['app.referral.enabled' => true]);

        $response = $this->get('/register?'.ReferralConstants::HTTP_PARAM_REFERRAL_CODE.'=ANYCODE456');

        $response->assertSessionHas(SessionConstants::REFERRAL_CODE, 'ANYCODE456');
    }

    public function test_referral_code_is_not_stored_when_not_present(): void
    {
        $this->withExceptionHandling();
        config(['app.referral.enabled' => true]);

        $response = $this->get('/login');

        $response->assertSessionMissing(SessionConstants::REFERRAL_CODE);
    }

    public function test_referral_code_is_overwritten_with_new_code(): void
    {
        $this->withExceptionHandling();
        config(['app.referral.enabled' => true]);

        $this->withSession([SessionConstants::REFERRAL_CODE => 'OLDCODE123']);

        $response = $this->get('/login?'.ReferralConstants::HTTP_PARAM_REFERRAL_CODE.'=NEWCODE456');

        $response->assertSessionHas(SessionConstants::REFERRAL_CODE, 'NEWCODE456');
    }

    public function test_referral_code_is_not_stored_when_system_disabled(): void
    {
        $this->withExceptionHandling();
        config(['app.referral.enabled' => false]);

        $response = $this->get('/login?'.ReferralConstants::HTTP_PARAM_REFERRAL_CODE.'=TESTCODE');

        $response->assertSessionMissing(SessionConstants::REFERRAL_CODE);
    }

    public function test_referral_code_persists_across_requests(): void
    {
        $this->withExceptionHandling();
        config(['app.referral.enabled' => true]);

        $this->get('/login?'.ReferralConstants::HTTP_PARAM_REFERRAL_CODE.'=PERSISTENT1');

        $response = $this->get('/login');
        $response->assertSessionHas(SessionConstants::REFERRAL_CODE, 'PERSISTENT1');

        $response = $this->get('/register');
        $response->assertSessionHas(SessionConstants::REFERRAL_CODE, 'PERSISTENT1');
    }
}
