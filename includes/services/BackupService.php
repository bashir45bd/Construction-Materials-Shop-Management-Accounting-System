<?php
/**
 * Database Backup & CSV Import Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\DB;
use App\Config\Database;
use PDO;
use Exception;

class BackupService
{
    /**
     * Generate Full SQL Dump of the Database
     */
    public static function createSqlBackup(int $shopId = 1): array
    {
        $pdo = DB::pdo();
        $tables = [
            'shops', 'settings', 'document_sequences', 'roles', 'permissions', 
            'role_permissions', 'users', 'user_roles', 'units', 'unit_conversions', 
            'categories', 'brands', 'products', 'customers', 'customer_ledger', 
            'customer_advances', 'customer_advance_adjustments', 'suppliers', 
            'supplier_ledger', 'supplier_advances', 'cash_accounts', 'bank_accounts', 
            'financial_transactions', 'expense_categories', 'expenses', 
            'income_categories', 'incomes', 'sales', 'sale_items', 'sale_payments', 
            'sales_returns', 'sales_return_items', 'purchases', 'purchase_items', 
            'purchase_returns', 'purchase_return_items', 'stock_movements', 
            'stock_adjustments', 'stock_adjustment_items', 'quotations', 
            'quotation_items', 'audit_logs'
        ];

        $sqlDump = "-- ========================================================\n";
        $sqlDump .= "-- NIRMAN ERP DATABASE BACKUP\n";
        $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "-- Shop ID: " . $shopId . "\n";
        $sqlDump .= "-- ========================================================\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Check if table exists
            $check = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($check->rowCount() === 0) {
                continue;
            }

            // Table Structure
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sqlDump .= $createRow[1] . ";\n\n";

            // Table Data
            $rowsStmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $cols = array_keys($rows[0]);
                $colNames = implode('`, `', $cols);

                foreach ($rows as $row) {
                    $values = array_map(function ($val) use ($pdo) {
                        if ($val === null) {
                            return 'NULL';
                        }
                        return $pdo->quote((string)$val);
                    }, array_values($row));

                    $sqlDump .= "INSERT INTO `{$table}` (`{$colNames}`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sqlDump .= "\n";
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'backup_nirman_erp_' . date('Y_m_d_His') . '.sql';
        $filepath = BACKUPS_PATH . '/' . $filename;

        if (!is_dir(BACKUPS_PATH)) {
            mkdir(BACKUPS_PATH, 0755, true);
        }

        file_put_contents($filepath, $sqlDump);

        return [
            'filename' => $filename,
            'filepath' => $filepath,
            'size'     => filesize($filepath),
            'content'  => $sqlDump
        ];
    }
}
