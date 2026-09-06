<?php
/**
 * Database Configuration & PDO Factory
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;

    private static array $config = [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'dbname'   => 'construction_erp',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+06:00'"
        ]
    ];

    /**
     * Get Singleton PDO Connection
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Establish PDO connection with fallback and environment override
     */
    private static function connect(): void
    {
        $host     = getenv('DB_HOST') ?: self::$config['host'];
        $port     = getenv('DB_PORT') ?: self::$config['port'];
        $dbname   = getenv('DB_NAME') ?: self::$config['dbname'];
        $user     = getenv('DB_USER') ?: self::$config['username'];
        $pass     = getenv('DB_PASS') !== false ? getenv('DB_PASS') : self::$config['password'];
        $charset  = self::$config['charset'];

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            self::$instance = new PDO($dsn, $user, $pass, self::$config['options']);
        } catch (PDOException $e) {
            // Log error internally and fail gracefully without leaking DB credentials
            error_log("[DB Connection Error] " . $e->getMessage());
            throw new RuntimeException("Database connection failed. Please ensure MySQL service is running and configured correctly.");
        }
    }

    /**
     * Check if database is installed and tables exist
     */
    public static function isInstalled(): bool
    {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query("SHOW TABLES LIKE 'shops'");
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
