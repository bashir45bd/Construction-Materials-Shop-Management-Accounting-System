<?php
/**
 * Concurrency-Safe Document Number Generator Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\DB;
use PDO;
use Exception;

class DocumentNumberService
{
    /**
     * Generate Next Document Number atomically
     *
     * Example outputs:
     * - MT-2026-000001 (Sales Memo)
     * - PUR-2026-000001 (Purchase Voucher)
     * - PAY-2026-000001 (Payment Voucher)
     * - ADV-2026-000001 (Advance Deposit)
     * - SR-2026-000001 (Sales Return)
     * - QT-2026-000001 (Quotation)
     * - EXP-2026-000001 (Expense)
     */
    public static function getNextNumber(string $code, int $shopId = 1): string
    {
        $year = date('Y');
        $pdo = DB::pdo();

        // Ensure sequence row exists
        $checkStmt = $pdo->prepare("SELECT id, prefix, padding FROM document_sequences 
                                   WHERE shop_id = :s AND code = :c AND fiscal_year = :y");
        $checkStmt->execute(['s' => $shopId, 'c' => $code, 'y' => $year]);
        $seq = $checkStmt->fetch();

        if (!$seq) {
            // Default prefixes
            $defaultPrefixes = [
                'MEMO' => 'MT-' . $year . '-',
                'PURCHASE' => 'PUR-' . $year . '-',
                'PAYMENT' => 'PAY-' . $year . '-',
                'ADVANCE' => 'ADV-' . $year . '-',
                'SALE_RETURN' => 'SR-' . $year . '-',
                'PURCHASE_RETURN' => 'PR-' . $year . '-',
                'QUOTATION' => 'QT-' . $year . '-',
                'ADJUSTMENT' => 'ADJ-' . $year . '-',
                'EXPENSE' => 'EXP-' . $year . '-',
                'INCOME' => 'INC-' . $year . '-',
                'TRANSACTION' => 'TXN-' . $year . '-'
            ];
            $prefix = $defaultPrefixes[$code] ?? strtoupper($code) . '-' . $year . '-';

            $insertStmt = $pdo->prepare("INSERT INTO document_sequences (shop_id, code, fiscal_year, prefix, current_number, padding) 
                                         VALUES (:s, :c, :y, :p, 0, 6) 
                                         ON DUPLICATE KEY UPDATE prefix = VALUES(prefix)");
            $insertStmt->execute([
                's' => $shopId,
                'c' => $code,
                'y' => $year,
                'p' => $prefix
            ]);

            $checkStmt->execute(['s' => $shopId, 'c' => $code, 'y' => $year]);
            $seq = $checkStmt->fetch();
        }

        // Imports and opening-balance seed data can already contain numbered
        // documents.  Move a stale sequence forward before allocating its next
        // value, otherwise the next write would fail on a unique constraint.
        self::synchronizeWithExistingDocuments($code, $shopId, (string)$seq['prefix'], $pdo);

        // Atomic Mutex update using LAST_INSERT_ID
        $updateStmt = $pdo->prepare("UPDATE document_sequences 
                                     SET current_number = LAST_INSERT_ID(current_number + 1) 
                                     WHERE shop_id = :s AND code = :c AND fiscal_year = :y");
        $updateStmt->execute(['s' => $shopId, 'c' => $code, 'y' => $year]);

        $currentNumber = (int)$pdo->lastInsertId();
        $prefix = $seq['prefix'] ?? "{$code}-{$year}-";
        $padding = (int)($seq['padding'] ?? 6);

        return $prefix . str_pad((string)$currentNumber, $padding, '0', STR_PAD_LEFT);
    }

    /** Move a document sequence forward only; it never renumbers history. */
    private static function synchronizeWithExistingDocuments(string $code, int $shopId, string $prefix, PDO $pdo): void
    {
        $sources = [
            'MEMO' => ['sales', 'memo_number'],
            'PURCHASE' => ['purchases', 'purchase_number'],
            'PAYMENT' => ['sale_payments', 'payment_no'],
            'ADVANCE' => ['customer_advances', 'advance_no'],
            'SALE_RETURN' => ['sales_returns', 'return_no'],
            'PURCHASE_RETURN' => ['purchase_returns', 'return_no'],
            'QUOTATION' => ['quotations', 'quotation_no'],
            'ADJUSTMENT' => ['stock_adjustments', 'adjustment_no'],
            'EXPENSE' => ['expenses', 'expense_no'],
            'INCOME' => ['incomes', 'income_no'],
            'TRANSACTION' => ['financial_transactions', 'transaction_no'],
        ];

        if (!isset($sources[$code])) {
            return;
        }

        [$table, $column] = $sources[$code];
        $maxStmt = $pdo->prepare(
            "SELECT COALESCE(MAX(CAST(SUBSTRING({$column}, CHAR_LENGTH(:prefix) + 1) AS UNSIGNED)), 0)\n" .
            "FROM {$table} WHERE shop_id = :shop AND {$column} LIKE CONCAT(:prefix_match, '%')"
        );
        $maxStmt->execute([
            'prefix' => $prefix,
            'prefix_match' => $prefix,
            'shop' => $shopId,
        ]);
        $existingMaximum = (int)$maxStmt->fetchColumn();

        if ($existingMaximum > 0) {
            $syncStmt = $pdo->prepare(
                'UPDATE document_sequences SET current_number = GREATEST(current_number, :maximum) ' .
                'WHERE shop_id = :shop AND code = :code AND fiscal_year = :year'
            );
            $syncStmt->execute([
                'maximum' => $existingMaximum,
                'shop' => $shopId,
                'code' => $code,
                'year' => date('Y'),
            ]);
        }
    }
}
