<?php
/**
 * Audit Log Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\DB;
use App\Auth\Auth;

class AuditService
{
    /**
     * Log User Action to immutable audit table
     */
    public static function log(
        string $action,
        string $module,
        ?int $recordId = null,
        ?array $oldData = null,
        ?array $newData = null,
        ?string $notes = null,
        int $shopId = 1
    ): void {
        try {
            $pdo = DB::pdo();
            $stmt = $pdo->prepare("INSERT INTO audit_logs 
                (shop_id, user_id, action, module, record_id, ip_address, old_data, new_data, notes)
                VALUES (:s, :uid, :action, :module, :rid, :ip, :old, :new, :notes)");

            $stmt->execute([
                's'      => $shopId,
                'uid'    => Auth::id(),
                'action' => $action,
                'module' => $module,
                'rid'    => $recordId,
                'ip'     => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'old'    => $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                'new'    => $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                'notes'  => $notes
            ]);
        } catch (\Throwable $e) {
            // Audit logging failure should not crash user operation
            error_log("[Audit Log Error] " . $e->getMessage());
        }
    }
}
