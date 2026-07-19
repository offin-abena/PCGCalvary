<?php

/**
 * Generates routes/church_definitions.php from the original church public/index.php
 */

$index = file_get_contents('C:/xampp/htdocs/church/public/index.php');
preg_match_all(
    '/\$router->(get|post)\(\'([^\']+)\',\s*\'([^\']+)\',\s*\'([^\']+)\'(?:,\s*\'([^\']+)\')?\)/',
    $index,
    $matches,
    PREG_SET_ORDER
);

$lines = ["<?php\n\nuse App\\Http\\Controllers\\*;\n\nreturn ["];
$controllers = [];

foreach ($matches as $m) {
    $method = strtoupper($m[1]);
    $path = $m[2];
    $controller = $m[3];
    $action = $m[4];
    $permission = $m[5] ?? null;
    $controllers[$controller] = true;
    $perm = $permission ? "'{$permission}'" : 'null';
    $lines[] = "    ['{$method}', '{$path}', {$controller}::class, '{$action}', {$perm}],";
}

$lines[] = "];\n";

$useStatements = '';
foreach (array_keys($controllers) as $controller) {
    $useStatements .= "use App\\Http\\Controllers\\{$controller};\n";
}

$output = "<?php\n\n{$useStatements}\nreturn [\n";
foreach ($matches as $m) {
    $method = strtoupper($m[1]);
    $path = $m[2];
    $controller = $m[3];
    $action = $m[4];
    $permission = $m[5] ?? null;
    $perm = $permission ? "'{$permission}'" : 'null';
    $output .= "    ['{$method}', '{$path}', {$controller}::class, '{$action}', {$perm}],\n";
}
$output .= "];\n";

file_put_contents('C:/xampp/htdocs/church_laravel/routes/church_definitions.php', $output);
echo 'Generated ' . count($matches) . " routes.\n";
