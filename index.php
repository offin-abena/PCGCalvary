<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Strip the app subdirectory from REQUEST_URI when serving from project root.
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

if ($basePath !== '' && $basePath !== '/' && empty($_SERVER['PATH_INFO'])) {
    $requestUri = $_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '/';
    $query = $_SERVER['REDIRECT_QUERY_STRING'] ?? $_SERVER['QUERY_STRING'] ?? '';

    if (($queryPos = strpos($requestUri, '?')) !== false) {
        if ($query === '') {
            $query = substr($requestUri, $queryPos + 1);
        }
        $requestUri = substr($requestUri, 0, $queryPos);
    }

    if (str_starts_with($requestUri, $basePath . '/index.php')) {
        $requestUri = substr($requestUri, strlen($basePath . '/index.php')) ?: '/';
    } elseif (str_starts_with($requestUri, $basePath)) {
        $requestUri = substr($requestUri, strlen($basePath)) ?: '/';
    }

    $_SERVER['REQUEST_URI'] = $requestUri.($query !== '' ? '?'.$query : '');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
