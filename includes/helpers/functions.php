<?php
/**
 * Global Helper Functions
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

use App\Config\Database;

/**
 * HTML Escaping for XSS Protection
 */
function e(?string $string): string
{
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Retrieve Active Shop Details (Cached per execution)
 */
function getShop(int $shopId = 1): array
{
    static $shop = null;
    if ($shop !== null) {
        return $shop;
    }

    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM shops WHERE id = :id AND is_active = 1 LIMIT 1");
        $stmt->execute(['id' => $shopId]);
        $shop = $stmt->fetch() ?: [
            'id' => 1,
            'name' => 'Construction Materials Supply',
            'shop_code' => 'SHOP001',
            'proprietor' => 'Proprietor',
            'address' => 'Shop Address',
            'phone' => '01700000000',
            'invoice_prefix' => 'MT',
            'currency_symbol' => '৳',
            'currency_code' => 'BDT',
            'currency_position' => 'before',
            'date_format' => 'd-m-Y',
            'invoice_footer_shop' => 'Shop Copy',
            'invoice_footer_customer' => 'Customer Copy'
        ];
    } catch (\Throwable $e) {
        $shop = [
            'id' => 1,
            'name' => 'Construction Materials Supply',
            'shop_code' => 'SHOP001',
            'phone' => '01700000000',
            'currency_symbol' => '৳',
            'currency_position' => 'before',
            'date_format' => 'd-m-Y'
        ];
    }

    return $shop;
}

/**
 * Get Specific Setting Value
 */
function getSetting(string $key, mixed $default = null, int $shopId = 1): mixed
{
    static $settingsCache = [];
    $cacheKey = "{$shopId}_{$key}";

    if (array_key_exists($cacheKey, $settingsCache)) {
        return $settingsCache[$cacheKey];
    }

    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE shop_id = :shop_id AND setting_key = :k LIMIT 1");
        $stmt->execute(['shop_id' => $shopId, 'k' => $key]);
        $val = $stmt->fetchColumn();
        $settingsCache[$cacheKey] = ($val !== false) ? $val : $default;
    } catch (\Throwable $e) {
        $settingsCache[$cacheKey] = $default;
    }

    return $settingsCache[$cacheKey];
}

/**
 * Format Currency according to Shop Preferences
 */
function formatMoney(float|int|string|null $amount, ?string $currencySymbol = null): string
{
    $num = (float)($amount ?? 0.0);
    $formattedNum = number_format($num, 2, '.', ',');
    
    $shop = getShop();
    $symbol = $currencySymbol ?? ($shop['currency_symbol'] ?? '৳');
    $pos = $shop['currency_position'] ?? 'before';

    return $pos === 'after' ? "{$formattedNum} {$symbol}" : "{$symbol} {$formattedNum}";
}

/**
 * Format Quantity with precision
 */
function formatQuantity(float|int|string|null $qty, int $decimals = 2): string
{
    $num = (float)($qty ?? 0.0);
    // If it's an exact integer, don't show unnecessary trailing zeroes
    if (floor($num) == $num) {
        return number_format($num, 0, '.', ',');
    }
    return rtrim(rtrim(number_format($num, $decimals, '.', ','), '0'), '.');
}

/**
 * Format Date based on shop setting
 */
function formatDate(?string $dateStr, string $format = ''): string
{
    if (empty($dateStr)) {
        return '-';
    }
    $shop = getShop();
    $targetFormat = !empty($format) ? $format : ($shop['date_format'] ?? 'd-m-Y');
    
    try {
        $dt = new DateTime($dateStr);
        return $dt->format($targetFormat);
    } catch (\Throwable $e) {
        return $dateStr;
    }
}

/**
 * JSON Response Formatter for AJAX Requests
 */
function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Safe Redirect
 */
function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Session Flash Messages
 */
function flash(string $type, string $message): void
{
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlashes(): array
{
    $flashes = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $flashes;
}

/**
 * Sanitize String Input
 */
function cleanInput(?string $input): string
{
    return trim((string)($input ?? ''));
}
