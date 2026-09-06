<?php
/**
 * Database Query Helper & Transaction Manager
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Database;

use App\Config\Database;
use PDO;
use PDOStatement;
use Throwable;
use Exception;

class DB
{
    /**
     * Get underlying PDO instance
     */
    public static function pdo(): PDO
    {
        return Database::getConnection();
    }

    /**
     * Execute transactional callback with automatic rollback on error
     *
     * @template T
     * @param callable(PDO): T $callback
     * @return T
     * @throws Throwable
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $isNested = $pdo->inTransaction();

        if (!$isNested) {
            $pdo->beginTransaction();
        }

        try {
            $result = $callback($pdo);
            if (!$isNested) {
                $pdo->commit();
            }
            return $result;
        } catch (Throwable $e) {
            if (!$isNested && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("[DB Transaction Failed] " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Execute Prepared Query
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch Single Row
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Fetch All Rows
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Fetch Single Column Value
     */
    public static function fetchValue(string $sql, array $params = []): mixed
    {
        return self::query($sql, $params)->fetchColumn();
    }

    /**
     * Insert Record and return Last Insert ID
     */
    public static function insert(string $table, array $data): int|string
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($data);

        return self::pdo()->lastInsertId();
    }

    /**
     * Update Record
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $params = [];

        foreach ($data as $col => $val) {
            $setParts[] = "{$col} = :upd_{$col}";
            $params["upd_{$col}"] = $val;
        }

        $sql = sprintf("UPDATE %s SET %s WHERE %s", $table, implode(', ', $setParts), $where);
        
        $mergedParams = array_merge($params, $whereParams);
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($mergedParams);

        return $stmt->rowCount();
    }

    /**
     * Delete Record (or Soft Delete)
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
