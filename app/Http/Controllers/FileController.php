<?php

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Http\Controllers\Controller;

class FileController extends Controller
{
    public function serve(): void
    {
        Auth::requireAuth();

        $relative = $this->input('f', '');
        $relative = str_replace('\\', '/', $relative);
        $relative = ltrim($relative, '/');

        if ($relative === '' || str_contains($relative, '..')) {
            http_response_code(404);
            exit('Not found');
        }

        $config = config('church');
        $uploadRoot = realpath($config['upload_path']);
        $fullPath = realpath($config['upload_path'] . $relative);

        if (!$uploadRoot || !$fullPath || !str_starts_with($fullPath, $uploadRoot) || !is_file($fullPath)) {
            http_response_code(404);
            exit('Not found');
        }

        $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        header('X-Content-Type-Options: nosniff');
        readfile($fullPath);
        exit;
    }
}
