<?php

return [
    'provider' => env('SMS_PROVIDER', 'wigal'),
    'enabled' => filter_var(env('SMS_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN),
    'wigal' => [
        'username' => env('WIGAL_USERNAME', ''),
        'api_key' => env('WIGAL_API_KEY', ''),
        'sender_id' => env('WIGAL_SENDER_ID', 'CHURCH'),
        'endpoint' => env('WIGAL_ENDPOINT', 'https://frogapi.wigal.com.gh/api/v3/sms/send'),
    ],
    'africastalking' => [
        'username' => 'sandbox',
        'api_key' => '',
        'sender_id' => 'CHURCH',
        'endpoint' => 'https://api.africastalking.com/version1/messaging',
    ],
    'twilio' => [
        'account_sid' => '',
        'auth_token' => '',
        'from_number' => '',
    ],
    'templates' => [
        'tithe_receipt' => 'Dear {name}, thank you for your tithe of {amount} received on {date}. Receipt: {receipt}. God bless you! - {church}',
        'tithe_reminder' => 'Dear {name}, friendly reminder from {church} to honour your tithe this month ({month}). "Bring the whole tithe into the storehouse." - Mal 3:10',
        'tithe_overdue' => 'Dear {name}, we have not received your tithe for {month}. Please contact the church office if you need assistance. - {church}',
    ],
    'auto_send_tithe_receipt' => true,
    'reminder_only_without_tithe' => true,
];
