<?php

namespace App\Http\Middleware;

use App\Core\Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChurchAuthenticate
{
    private const PUBLIC_PATHS = [
        'login',
        'logout',
        'donations/give',
        'donations/store',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $path = trim($request->path(), '/');

        foreach (self::PUBLIC_PATHS as $public) {
            if ($path === $public || str_starts_with($path, $public . '/')) {
                return $next($request);
            }
        }

        Auth::requireAuth();

        return $next($request);
    }
}
