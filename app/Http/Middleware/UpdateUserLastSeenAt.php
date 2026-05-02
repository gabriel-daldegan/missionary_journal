<?php

namespace App\Http\Middleware;

use App\Events\User\UserSeen;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Facades\Impersonation;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastSeenAt
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->ajax() ||
            $request->expectsJson() ||
            $request->header('X-Livewire') !== null ||
            Impersonation::isImpersonating()
        ) {
            return $next($request);
        }

        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            if ($user->last_seen_at === null || now()->diffInMinutes($user->last_seen_at, true) >= 10) { // not to overload the database
                UserSeen::dispatch($user);
            }
        }

        return $next($request);
    }
}
