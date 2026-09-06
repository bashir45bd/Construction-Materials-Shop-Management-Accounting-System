<?php
/**
 * Application Bootstrap & Global Settings
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

// Error Reporting (Display errors in development, log in production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set Timezone
date_default_timezone_set('Asia/Dhaka');

// Define Base Paths
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('MODULES_PATH', ROOT_PATH . '/modules');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOADS_PATH', STORAGE_PATH . '/uploads');
define('LOGS_PATH', STORAGE_PATH . '/logs');
define('BACKUPS_PATH', STORAGE_PATH . '/backups');

// Application Constants
define('APP_NAME', 'NirmanERP Construction Supply & Accounting');
define('APP_VERSION', '2.0.0-PROD');

// Determine Base URL Dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . ($scriptDir === '/' ? '' : $scriptDir), '/');
// Clean up module subdirectories from Base URL if nested
$cleanedBaseUrl = preg_replace('#/(modules|ajax|api)/.*$#', '', $baseUrl);
define('BASE_URL', $cleanedBaseUrl);

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');
    
    // Cookie lifetime: 8 hours
    session_set_cookie_params([
        'lifetime' => 28800,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_name('NIRMAN_ERP_SESS');
    session_start();
}

// Simple Class Autoloader
spl_autoload_register(function ($class) {
    $prefixes = [
        'App\\Config\\'   => CONFIG_PATH . '/',
        'App\\Auth\\'     => INCLUDES_PATH . '/auth/',
        'App\\Database\\' => INCLUDES_PATH . '/database/',
        'App\\Helpers\\'  => INCLUDES_PATH . '/helpers/',
        'App\\Services\\' => INCLUDES_PATH . '/services/'
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Require Helper Functions
require_once INCLUDES_PATH . '/helpers/functions.php';
require_once INCLUDES_PATH . '/helpers/csrf.php';
