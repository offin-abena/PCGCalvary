<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ChurchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        date_default_timezone_set(config('church.timezone', 'Africa/Accra'));

        $definitions = require base_path('routes/church_definitions.php');

        Route::middleware(['web', 'church.security', 'church.auth', 'church.csrf'])
            ->group(function () use ($definitions) {
                foreach ($definitions as $definition) {
                    [$method, $path, $controller, $action, $permission] = $definition;

                    $route = Route::{strtolower($method)}($path, [$controller, $action]);

                    if ($permission) {
                        $route->middleware('church.access:' . $permission);
                    } else {
                        $route->middleware('church.access');
                    }
                }
            });
    }
}
