<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePortfolioAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminEmail = mb_strtolower(trim((string) config('portfolio.admin_email')));
        $userEmail = mb_strtolower(trim((string) $request->user()?->email));

        abort_unless($adminEmail !== '' && hash_equals($adminEmail, $userEmail), 403);

        return $next($request);
    }
}
