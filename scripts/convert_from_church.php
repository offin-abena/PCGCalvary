<?php

/**
 * Copies church app source files into the Laravel project with namespace/path fixes.
 */

$church = 'C:/xampp/htdocs/church';
$laravel = 'C:/xampp/htdocs/church_laravel';

function copyDir(string $src, string $dest, callable $transform = null): void
{
    if (!is_dir($dest)) {
        mkdir($dest, 0755, true);
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $relative = substr($file->getPathname(), strlen($src) + 1);
        $target = $dest . DIRECTORY_SEPARATOR . $relative;
        $targetDir = dirname($target);

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $content = file_get_contents($file->getPathname());
        if ($transform) {
            $content = $transform($content, $relative);
        }

        file_put_contents($target, $content);
    }
}

// Core (except Router - Laravel handles routing)
$coreFiles = ['Auth.php', 'AuditLog.php', 'Controller.php', 'Csrf.php', 'Database.php', 'LoginThrottle.php', 'Model.php', 'Session.php', 'View.php'];
foreach ($coreFiles as $file) {
    $src = "$church/src/Core/$file";
    $dest = "$laravel/app/Core/$file";
    if (!is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0755, true);
    }
    copy($src, $dest);
}

// Models
copyDir("$church/src/Models", "$laravel/app/Models");

// Services
copyDir("$church/src/Services", "$laravel/app/Services", function (string $content, string $relative) {
    return str_replace(
        "require __DIR__ . '/../../config/",
        "require config_path('",
        $content
    );
});

// Controllers
copyDir("$church/src/Controllers", "$laravel/app/Http/Controllers", function (string $content) {
    $content = str_replace('namespace App\\Controllers;', 'namespace App\\Http\\Controllers;', $content);
    $content = str_replace('use App\\Core\\Controller;', 'use App\\Http\\Controllers\\Controller;', $content);

    return $content;
});

// Config files
$configFiles = [
    'age_groups.php',
    'finance_account_classification.php',
    'finance_categories.php',
    'generational_groups.php',
    'modules.php',
    'sms.php',
];
foreach ($configFiles as $file) {
    copy("$church/config/$file", "$laravel/config/$file");
}

// Views
copyDir("$church/views", "$laravel/resources/views", function (string $content) {
    $content = str_replace("\$_GET['url'] ?? ''", "current_path()", $content);
    $content = str_replace("require __DIR__ . '/../../config/app.php'", "config('church')", $content);
    $content = str_replace('$config = config(\'church\');', '$config = config(\'church\');', $content);

    return $content;
});

echo "Conversion copy complete.\n";
