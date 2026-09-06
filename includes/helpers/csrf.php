<?php
/**
 * CSRF Protection Helper
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Helpers;

class Csrf
{
    /**
     * Generate or return existing CSRF token
     */
    public static function getToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Render hidden HTML input field
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate submitted CSRF token
     */
    public static function validate(?string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if (empty($sessionToken) || empty($token)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    /**
     * Enforce CSRF check or terminate
     */
    public static function verifyOrDie(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::validate($token)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                jsonResponse(['success' => false, 'message' => 'CSRF security token invalid or expired. Please refresh the page.'], 403);
            }
            http_response_code(403);
            die("Security Error: CSRF token verification failed. Please refresh and try again.");
        }
    }
}
