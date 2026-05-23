<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\User;
use App\Services\MemoryProfileService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemoryProfileComplete
{
    public function __construct(
        public MemoryProfileService $memoryProfileService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if (! $tenant instanceof Tenant) {
            abort(404);
        }

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(404);
        }

        if (! $this->memoryProfileService->isComplete($user)) {
            return redirect()->route('memories.profile.setup', [
                'tenant' => $tenant,
            ]);
        }

        app()->setLocale($this->memoryProfileService->preferredLocale($user));

        return $next($request);
    }
}
