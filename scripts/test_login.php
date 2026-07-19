<?php

$html = @file_get_contents('http://127.0.0.1:8090/login');
if ($html && str_contains($html, 'Login')) {
    echo "LOGIN OK\n";
} elseif ($html) {
    echo "Unexpected response:\n" . substr($html, 0, 500) . "\n";
} else {
    echo "FAILED\n";
}
