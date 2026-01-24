<?php

namespace App\Http\Middleware;

use App\Constants\ReferralConstants;
use App\Constants\SessionConstants;
use App\Services\ReferralService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackReferralCode
{
    public function __construct(
        private ReferralService $referralService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->referralService->isEnabled() && $request->has(ReferralConstants::HTTP_PARAM_REFERRAL_CODE)) {
            $referralCode = $request->get('referralCode');
            session([SessionConstants::REFERRAL_CODE => $referralCode]);
        }

        return $next($request);
    }
}
