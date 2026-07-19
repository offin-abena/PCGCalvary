<?php

$dir = 'C:/xampp/htdocs/church_laravel/app/Http/Controllers';
foreach (glob($dir . '/*.php') as $file) {
    $c = file_get_contents($file);
    $c = str_replace("require __DIR__ . '/../../config/app.php'", "config('church')", $c);
    $c = str_replace("require __DIR__ . '/../../config/sms.php'", "config('sms')", $c);
    $c = str_replace("require __DIR__ . '/../../config/finance_categories.php'", "config('finance_categories')", $c);
    file_put_contents($file, $c);
}

echo "Controllers updated.\n";
