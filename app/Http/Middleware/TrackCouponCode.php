<?php

namespace App\Http\Middleware;

use App\Constants\DiscountConstants;
use App\Services\DiscountService;
use App\Services\SessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackCouponCode
{
    public function __construct(
        private DiscountService $discountService,
        private SessionService $sessionService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has(DiscountConstants::COUPON_QUERY_PARAMETER)) {
            $code = $request->input(DiscountConstants::COUPON_QUERY_PARAMETER);

            if ($code && $this->discountService->getActiveDiscountByCode($code)) {
                $this->sessionService->saveCouponCode($code);
            } else {
                $this->sessionService->clearCouponCode();
            }
        }

        return $next($request);
    }
}
