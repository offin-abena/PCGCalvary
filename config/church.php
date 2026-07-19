<?php

return [
    'name' => env('CHURCH_APP_NAME', 'Church Management System'),
    'version' => '1.0.0',
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Africa/Accra'),
    'upload_path' => storage_path('app/uploads/'),
    'max_upload_size' => (int) env('MAX_UPLOAD_SIZE', 5 * 1024 * 1024),
    'allowed_image_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'allowed_doc_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
    'items_per_page' => 15,
    'currency' => env('CHURCH_CURRENCY', 'GHS'),
    'currency_symbol' => env('CHURCH_CURRENCY_SYMBOL', 'GH₵'),
    'church_name' => env('CHURCH_NAME', 'PCG Calvary Nungua'),
    'church_address' => env('CHURCH_ADDRESS', 'Nungua, Accra, Ghana'),
    'church_phone' => env('CHURCH_PHONE', '+233 20 000 0000'),
    'church_email' => env('CHURCH_EMAIL', 'info@pcgcalvarynungua.org'),
    'church_logo' => 'images/pcg logo.jpeg',
    'session_timeout' => (int) env('SESSION_TIMEOUT', 300),
];
