<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackReferralCode
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('referralCode')) {
            $referralCode = $request->get('referralCode');
            session(['referral_code' => $referralCode]);
        }

        return $next($request);
    }
}
