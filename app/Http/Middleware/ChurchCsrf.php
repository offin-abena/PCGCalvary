<?php

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\Csrf;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChurchCsrf
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('POST')) {
            Csrf::verify();
        }

        return $next($request);
    }
}
