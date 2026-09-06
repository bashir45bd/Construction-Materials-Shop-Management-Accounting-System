<?php
/**
 * Authentication & RBAC Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Auth;

use App\Config\Database;
use PDO;

class Auth
{
    private static ?array $currentUser = null;
    private static ?array $permissions = null;

    /**
     * Authenticate user credentials
     */
    public static function attempt(string $username, string $password): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT u.*, s.name as shop_name, s.is_active as shop_active 
                               FROM users u 
                               JOIN shops s ON u.shop_id = s.id 
                               WHERE u.username = :username OR u.email = :email LIMIT 1");
        // Native PDO prepared statements do not permit reusing one named
        // placeholder in this query, so bind each occurrence explicitly.
        $stmt->execute(['username' => $username, 'email' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        if ($user['status'] !== 'active' || (int)$user['shop_active'] !== 1) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        self::login($user);
        return true;
    }

    /**
     * Initialize user session upon successful login
     */
    public static function login(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id']   = (int)$user['id'];
        $_SESSION['shop_id']   = (int)$user['shop_id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['theme']     = $user['theme_preference'] ?? 'system';

        // Update last login timestamp & IP
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE users SET last_login_at = NOW(), last_login_ip = :ip WHERE id = :id");
            $stmt->execute([
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'id' => $user['id']
            ]);
        } catch (\Throwable $e) {
            // Non-blocking
        }

        self::$currentUser = $user;
        self::loadPermissions((int)$user['id']);
    }

    /**
     * Terminate user session
     */
    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        self::$currentUser = null;
        self::$permissions = null;
    }

    /**
     * Check if user is logged in
     */
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /**
     * Get Logged In User ID
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get Current Shop ID
     */
    public static function shopId(): int
    {
        return (int)($_SESSION['shop_id'] ?? 1);
    }

    /**
     * Get Current User Data
     */
    public static function user(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $userId = self::id();
        if (!$userId) {
            return null;
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $userId]);
            self::$currentUser = $stmt->fetch() ?: null;
        } catch (\Throwable $e) {
            self::$currentUser = null;
        }

        return self::$currentUser;
    }

    /**
     * Load Permissions for User
     */
    private static function loadPermissions(int $userId): void
    {
        if (self::$permissions !== null) {
            return;
        }

        try {
            $pdo = Database::getConnection();
            // Check if user is super admin
            $stmtRole = $pdo->prepare("SELECT r.slug FROM roles r 
                                      JOIN user_roles ur ON r.id = ur.role_id 
                                      WHERE ur.user_id = :uid");
            $stmtRole->execute(['uid' => $userId]);
            $roles = $stmtRole->fetchAll(PDO::FETCH_COLUMN);

            if (in_array('super_admin', $roles, true)) {
                // Super Admin has all permissions
                self::$permissions = ['*'];
                return;
            }

            $stmtPerms = $pdo->prepare("SELECT DISTINCT p.slug 
                                       FROM permissions p
                                       JOIN role_permissions rp ON p.id = rp.permission_id
                                       JOIN user_roles ur ON rp.role_id = ur.role_id
                                       WHERE ur.user_id = :uid");
            $stmtPerms->execute(['uid' => $userId]);
            self::$permissions = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            self::$permissions = [];
        }
    }

    /**
     * Check if user has permission
     */
    public static function hasPermission(string $permissionSlug): bool
    {
        if (!self::check()) {
            return false;
        }

        if (self::$permissions === null) {
            self::loadPermissions(self::id());
        }

        if (in_array('*', self::$permissions ?? [], true)) {
            return true;
        }

        return in_array($permissionSlug, self::$permissions ?? [], true);
    }

    /**
     * Enforce Login on Page Load
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                jsonResponse(['success' => false, 'message' => 'Session expired. Please log in again.'], 401);
            }
            redirect(BASE_URL . '/modules/auth/login.php');
        }
    }

    /**
     * Enforce Specific Permission on Page Load
     */
    public static function requirePermission(string $permissionSlug): void
    {
        self::requireLogin();
        if (!self::hasPermission($permissionSlug)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                jsonResponse(['success' => false, 'message' => 'Access Denied: You do not have permission for this action.'], 403);
            }
            http_response_code(403);
            die("<!DOCTYPE html><html><head><title>403 Access Denied</title><style>body{font-family:system-ui;padding:40px;text-align:center;}h1{color:#dc2626;}</style></head><body><h1>403 - Access Denied</h1><p>You do not have permission to access this module (<code>{$permissionSlug}</code>).</p><p><a href='" . BASE_URL . "/modules/dashboard/'>Return to Dashboard</a></p></body></html>");
        }
    }
}
