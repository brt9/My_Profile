<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

final class EnsureEmailIsVerifiedUnlessDebug extends EnsureEmailIsVerified
{
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (config('app.debug')) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
