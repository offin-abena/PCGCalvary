<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Model;

class SmsService
{
    private array $config;
    private array $appConfig;

    public function __construct()
    {
        $this->config = require config_path('sms.php');
        $this->appConfig = config('church');
    }

    public function isEnabled(): bool
    {
        return $this->config['enabled'] && $this->config['provider'] !== '';
    }

    public function send(string $phone, string $message, ?int $memberId = null, string $context = 'general'): array
    {
        $phone = $this->normalizePhone($phone);

        if (!$phone) {
            return ['success' => false, 'message' => 'Invalid phone number', 'phone' => $phone];
        }

        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'SMS is disabled', 'phone' => $phone];
        }

        $message = $this->sanitizeMessageForProvider($message);

        $result = match ($this->config['provider']) {
            'wigal' => $this->sendViaWigal($phone, $message),
            'africastalking' => $this->sendViaAfricasTalking($phone, $message),
            'twilio' => $this->sendViaTwilio($phone, $message),
            'log' => $this->sendViaLog($phone, $message),
            default => ['success' => false, 'message' => 'Unknown SMS provider'],
        };

        $this->logSms($phone, $message, $memberId, $context, $result);

        return array_merge($result, ['phone' => $phone]);
    }

    public function sendBulk(array $recipients, string $messageTemplate, string $context = 'bulk'): array
    {
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $details = [];

        foreach ($recipients as $recipient) {
            $phone = $recipient['phone'] ?? '';
            if (empty($phone)) {
                $skipped++;
                continue;
            }

            $message = $this->applyTemplate($messageTemplate, $recipient);
            $result = $this->send($phone, $message, $recipient['id'] ?? $recipient['member_id'] ?? null, $context);

            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
            }

            $details[] = [
                'name' => $recipient['name'] ?? ($recipient['first_name'] ?? '') . ' ' . ($recipient['last_name'] ?? ''),
                'phone' => $phone,
                'success' => $result['success'],
                'error' => $result['message'] ?? null,
            ];
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'total' => count($recipients),
            'details' => $details,
        ];
    }

    public function applyTemplate(string $template, array $vars): string
    {
        $vars['church'] = $vars['church'] ?? $this->appConfig['church_name'];

        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }

        return $template;
    }

    public function getProviderName(): string
    {
        return match ($this->config['provider']) {
            'wigal' => 'Wigal FROG',
            default => ucfirst($this->config['provider']),
        };
    }

    private function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (empty($phone)) {
            return null;
        }

        // Ghana: 0XXXXXXXXX -> +233XXXXXXXXX, 233XXXXXXXXX -> +233XXXXXXXXX
        if (preg_match('/^0([235]\d{8})$/', $phone, $m)) {
            return '+233' . $m[1];
        }

        if (preg_match('/^233([235]\d{8})$/', $phone, $m)) {
            return '+233' . $m[1];
        }

        if (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        return strlen($phone) >= 10 ? $phone : null;
    }

    /**
     * Wigal FROG rejects non-ASCII characters (e.g. GH₵) in message bodies.
     */
    private function sanitizeMessageForProvider(string $message): string
    {
        if ($this->config['provider'] !== 'wigal') {
            return $message;
        }

        $message = str_replace(['GH₵', '₵'], ['GHS', 'GHS '], $message);
        $message = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $message) ?? $message;

        return trim($message);
    }

    private function sendViaWigal(string $phone, string $message): array
    {
        $wigal = $this->config['wigal'];

        if (empty($wigal['username']) || empty($wigal['api_key'])) {
            return ['success' => false, 'message' => 'Wigal FROG credentials not configured'];
        }

        $destination = ltrim($phone, '+');
        $msgId = 'CHURCH-' . uniqid();

        $payload = json_encode([
            'senderid' => $wigal['sender_id'],
            'destinations' => [[
                'destination' => $destination,
                'message' => $message,
                'msgid' => $msgId,
                'smstype' => 'text',
            ]],
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            return ['success' => false, 'message' => 'Could not encode SMS message: ' . json_last_error_msg()];
        }

        $response = $this->httpPost($wigal['endpoint'], $payload, [
            'USERNAME: ' . $wigal['username'],
            'API-KEY: ' . $wigal['api_key'],
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json',
        ]);

        $data = json_decode($response['body'] ?? '', true) ?? [];
        $status = strtoupper($data['status'] ?? '');

        if ($response['success'] && $status === 'ACCEPTD') {
            return ['success' => true, 'message' => 'SMS sent', 'provider_ref' => $msgId];
        }

        if (!empty($data['message'])) {
            return ['success' => false, 'message' => $data['message']];
        }

        return ['success' => false, 'message' => $response['error'] ?? 'HTTP request failed'];
    }

    private function sendViaAfricasTalking(string $phone, string $message): array
    {
        $at = $this->config['africastalking'];

        if (empty($at['api_key'])) {
            return ['success' => false, 'message' => 'Africa\'s Talking API key not configured'];
        }

        $postData = http_build_query([
            'username' => $at['username'],
            'to' => $phone,
            'message' => $message,
            'from' => $at['sender_id'],
        ]);

        $response = $this->httpPost($at['endpoint'], $postData, [
            'apikey: ' . $at['api_key'],
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ]);

        if ($response['success']) {
            $data = json_decode($response['body'], true);
            $recipient = $data['SMSMessageData']['Recipients'][0] ?? [];
            $status = $recipient['status'] ?? '';

            if (in_array($status, ['Success', 'Sent', 'Submitted'])) {
                return ['success' => true, 'message' => 'SMS sent', 'provider_ref' => $recipient['messageId'] ?? null];
            }

            return ['success' => false, 'message' => $recipient['status'] ?? 'Send failed'];
        }

        return ['success' => false, 'message' => $response['error'] ?? 'HTTP request failed'];
    }

    private function sendViaTwilio(string $phone, string $message): array
    {
        $tw = $this->config['twilio'];

        if (empty($tw['account_sid']) || empty($tw['auth_token'])) {
            return ['success' => false, 'message' => 'Twilio credentials not configured'];
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$tw['account_sid']}/Messages.json";
        $postData = http_build_query([
            'To' => $phone,
            'From' => $tw['from_number'],
            'Body' => $message,
        ]);

        $response = $this->httpPost($url, $postData, [
            'Content-Type: application/x-www-form-urlencoded',
        ], $tw['account_sid'] . ':' . $tw['auth_token']);

        if ($response['success']) {
            $data = json_decode($response['body'], true);
            if (isset($data['sid'])) {
                return ['success' => true, 'message' => 'SMS sent', 'provider_ref' => $data['sid']];
            }
            return ['success' => false, 'message' => $data['message'] ?? 'Twilio error'];
        }

        return ['success' => false, 'message' => $response['error'] ?? 'HTTP request failed'];
    }

    private function sendViaLog(string $phone, string $message): array
    {
        return ['success' => true, 'message' => 'SMS logged (dev mode)', 'provider_ref' => 'LOG-' . uniqid()];
    }

    private function httpPost(string $url, string $data, array $headers = [], ?string $basicAuth = null): array
    {
        if (!function_exists('curl_init')) {
            return ['success' => false, 'error' => 'cURL extension not available'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($basicAuth) {
            curl_setopt($ch, CURLOPT_USERPWD, $basicAuth);
        }

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'body' => $body,
            'http_code' => $httpCode,
        ];
    }

    private function logSms(string $phone, string $message, ?int $memberId, string $context, array $result): void
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO sms_logs (member_id, phone, message, context, provider, status, provider_ref, error_message) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $memberId,
                $phone,
                $message,
                $context,
                $this->config['provider'],
                $result['success'] ? 'sent' : 'failed',
                $result['provider_ref'] ?? null,
                $result['success'] ? null : ($result['message'] ?? 'Unknown error'),
            ]);
        } catch (\PDOException $e) {
            // Table may not exist yet; fail silently
        }
    }
}
