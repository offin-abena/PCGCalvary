<?php

$dir = 'C:/xampp/htdocs/church_laravel/resources/views';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);
    $updated = str_replace('asset(', 'church_asset(', $content);
    if ($updated !== $content) {
        file_put_contents($path, $updated);
        echo "Updated: $path\n";
    }
}

echo "Done.\n";
