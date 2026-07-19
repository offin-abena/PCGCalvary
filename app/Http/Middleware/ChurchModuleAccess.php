<?php

namespace App\Http\Middleware;

use App\Core\Auth;
use App\Core\View;
use App\Services\ModuleAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChurchModuleAccess
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $path = trim($request->path(), '/');
        $linkKey = ModuleAccess::resolveLinkKeyFromUrl($path);

        if ($linkKey && !ModuleAccess::canAccess($linkKey)) {
            View::render('errors/403', ['title' => 'Access Denied']);
        }

        if ($permission && !Auth::can($permission)) {
            View::render('errors/403', ['title' => 'Access Denied']);
        }

        return $next($request);
    }
}
