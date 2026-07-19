<?php

namespace App\Core;

class AuditLog
{
    public static function log(string $action, ?string $module = null, ?int $recordId = null, ?array $details = null): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, module, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            Auth::id(),
            $action,
            $module,
            $recordId,
            $details ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
