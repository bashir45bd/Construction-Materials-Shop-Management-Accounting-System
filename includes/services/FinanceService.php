<?php
/**
 * Finance, Banking, Expense & Income Statement Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\DB;
use PDO;
use RuntimeException;

class FinanceService
{
    /**
     * Record Cash/Bank Inflow Transaction
     */
    public static function recordInflow(
        string $accountType,
        int $accountId,
        string $category,
        ?string $referenceType,
        ?int $referenceId,
        ?string $referenceNo,
        float $amount,
        ?string $notes,
        int $userId,
        ?string $date = null,
        int $shopId = 1
    ): float {
        $pdo = DB::pdo();
        $txDate = $date ?? date('Y-m-d');
        $txNo = DocumentNumberService::getNextNumber('TRANSACTION', $shopId);

        // Lock target account
        if (!in_array($accountType, ['cash', 'bank'], true) || $amount <= 0) {
            throw new RuntimeException('Invalid financial account or amount.');
        }
        $table = ($accountType === 'cash') ? 'cash_accounts' : 'bank_accounts';
        $stmt = $pdo->prepare("SELECT id, current_balance FROM {$table} WHERE id = :id AND shop_id = :shop AND status = 'active' FOR UPDATE");
        $stmt->execute(['id' => $accountId, 'shop' => $shopId]);
        $acc = $stmt->fetch();

        if (!$acc) {
            throw new RuntimeException("Financial {$accountType} account ID {$accountId} not found.");
        }

        $newBalance = (float)$acc['current_balance'] + $amount;

        // Update account balance
        $upd = $pdo->prepare("UPDATE {$table} SET current_balance = :bal, updated_at = NOW() WHERE id = :id");
        $upd->execute(['bal' => $newBalance, 'id' => $accountId]);

        // Insert into financial_transactions
        $ins = $pdo->prepare("INSERT INTO financial_transactions 
            (shop_id, transaction_no, transaction_date, account_type, account_id, transaction_nature, category, reference_type, reference_id, reference_no, amount, balance_after, notes, created_by)
            VALUES (:s, :tno, :tdate, :atype, :aid, 'INFLOW', :cat, :rtype, :rid, :rno, :amt, :bal, :notes, :uid)");
        
        $ins->execute([
            's'     => $shopId,
            'tno'   => $txNo,
            'tdate' => $txDate,
            'atype' => $accountType,
            'aid'   => $accountId,
            'cat'   => $category,
            'rtype' => $referenceType,
            'rid'   => $referenceId,
            'rno'   => $referenceNo,
            'amt'   => $amount,
            'bal'   => $newBalance,
            'notes' => $notes,
            'uid'   => $userId
        ]);

        return $newBalance;
    }

    /**
     * Record Cash/Bank Outflow Transaction
     */
    public static function recordOutflow(
        string $accountType,
        int $accountId,
        string $category,
        ?string $referenceType,
        ?int $referenceId,
        ?string $referenceNo,
        float $amount,
        ?string $notes,
        int $userId,
        ?string $date = null,
        int $shopId = 1
    ): float {
        $pdo = DB::pdo();
        $txDate = $date ?? date('Y-m-d');
        $txNo = DocumentNumberService::getNextNumber('TRANSACTION', $shopId);

        // Lock target account
        if (!in_array($accountType, ['cash', 'bank'], true) || $amount <= 0) {
            throw new RuntimeException('Invalid financial account or amount.');
        }
        $table = ($accountType === 'cash') ? 'cash_accounts' : 'bank_accounts';
        $stmt = $pdo->prepare("SELECT id, current_balance FROM {$table} WHERE id = :id AND shop_id = :shop AND status = 'active' FOR UPDATE");
        $stmt->execute(['id' => $accountId, 'shop' => $shopId]);
        $acc = $stmt->fetch();

        if (!$acc) {
            throw new RuntimeException("Financial {$accountType} account ID {$accountId} not found.");
        }

        $newBalance = (float)$acc['current_balance'] - $amount;

        // Update account balance
        $upd = $pdo->prepare("UPDATE {$table} SET current_balance = :bal, updated_at = NOW() WHERE id = :id");
        $upd->execute(['bal' => $newBalance, 'id' => $accountId]);

        // Insert into financial_transactions
        $ins = $pdo->prepare("INSERT INTO financial_transactions 
            (shop_id, transaction_no, transaction_date, account_type, account_id, transaction_nature, category, reference_type, reference_id, reference_no, amount, balance_after, notes, created_by)
            VALUES (:s, :tno, :tdate, :atype, :aid, 'OUTFLOW', :cat, :rtype, :rid, :rno, :amt, :bal, :notes, :uid)");
        
        $ins->execute([
            's'     => $shopId,
            'tno'   => $txNo,
            'tdate' => $txDate,
            'atype' => $accountType,
            'aid'   => $accountId,
            'cat'   => $category,
            'rtype' => $referenceType,
            'rid'   => $referenceId,
            'rno'   => $referenceNo,
            'amt'   => $amount,
            'bal'   => $newBalance,
            'notes' => $notes,
            'uid'   => $userId
        ]);

        return $newBalance;
    }

    /**
     * Record Operational Overhead Expense
     */
    public static function createExpense(
        int $categoryId,
        float $amount,
        string $accountType,
        int $accountId,
        ?string $payeeName,
        ?string $notes,
        int $userId,
        ?string $date = null,
        int $shopId = 1
    ): array {
        return DB::transaction(function ($pdo) use ($categoryId, $amount, $accountType, $accountId, $payeeName, $notes, $userId, $date, $shopId) {
            $expNo = DocumentNumberService::getNextNumber('EXPENSE', $shopId);
            $expDate = $date ?? date('Y-m-d');

            // 1. Record Outflow
            self::recordOutflow(
                $accountType,
                $accountId,
                'EXPENSE',
                'EXPENSE',
                null,
                $expNo,
                $amount,
                "Expense [{$expNo}]" . ($notes ? " - {$notes}" : ""),
                $userId,
                $expDate,
                $shopId
            );

            // 2. Insert into expenses table
            $stmt = $pdo->prepare("INSERT INTO expenses 
                (shop_id, expense_no, expense_date, category_id, amount, account_type, account_id, payee_name, notes, created_by)
                VALUES (:s, :eno, :edate, :cid, :amt, :atype, :aid, :pname, :notes, :uid)");
            
            $stmt->execute([
                's'     => $shopId,
                'eno'   => $expNo,
                'edate' => $expDate,
                'cid'   => $categoryId,
                'amt'   => $amount,
                'atype' => $accountType,
                'aid'   => $accountId,
                'pname' => $payeeName,
                'notes' => $notes,
                'uid'   => $userId
            ]);

            return [
                'expense_id' => (int)$pdo->lastInsertId(),
                'expense_no' => $expNo,
                'amount'     => $amount
            ];
        });
    }

    /**
     * Record Non-Sales Operational Income
     */
    public static function createIncome(
        int $categoryId,
        float $amount,
        string $accountType,
        int $accountId,
        ?string $payerName,
        ?string $notes,
        int $userId,
        ?string $date = null,
        int $shopId = 1
    ): array {
        return DB::transaction(function ($pdo) use ($categoryId, $amount, $accountType, $accountId, $payerName, $notes, $userId, $date, $shopId) {
            $incNo = DocumentNumberService::getNextNumber('INCOME', $shopId);
            $incDate = $date ?? date('Y-m-d');

            // 1. Record Inflow
            self::recordInflow(
                $accountType,
                $accountId,
                'INCOME',
                'INCOME',
                null,
                $incNo,
                $amount,
                "Income [{$incNo}]" . ($notes ? " - {$notes}" : ""),
                $userId,
                $incDate,
                $shopId
            );

            // 2. Insert into incomes table
            $stmt = $pdo->prepare("INSERT INTO incomes 
                (shop_id, income_no, income_date, category_id, amount, account_type, account_id, payer_name, notes, created_by)
                VALUES (:s, :ino, :idate, :cid, :amt, :atype, :aid, :pname, :notes, :uid)");
            
            $stmt->execute([
                's'     => $shopId,
                'ino'   => $incNo,
                'idate' => $incDate,
                'cid'   => $categoryId,
                'amt'   => $amount,
                'atype' => $accountType,
                'aid'   => $accountId,
                'pname' => $payerName,
                'notes' => $notes,
                'uid'   => $userId
            ]);

            return [
                'income_id' => (int)$pdo->lastInsertId(),
                'income_no' => $incNo,
                'amount'    => $amount
            ];
        });
    }

    /**
     * Compute Real-Time Profit & Loss (Income Statement)
     */
    public static function getIncomeStatement(string $startDate, string $endDate, int $shopId = 1): array
    {
        $pdo = DB::pdo();

        // 1. Total Sales & Historical COGS Snapshot
        $salesStmt = $pdo->prepare("SELECT 
                COALESCE(SUM(grand_total), 0) as total_sales,
                COALESCE(SUM(total_cogs), 0) as total_cogs,
                COALESCE(SUM(discount_amount), 0) as total_discounts
            FROM sales 
            WHERE shop_id = :s AND sale_status != 'cancelled' AND sale_date BETWEEN :start AND :end");
        $salesStmt->execute(['s' => $shopId, 'start' => $startDate, 'end' => $endDate]);
        $salesData = $salesStmt->fetch();

        $totalSales = (float)$salesData['total_sales'];
        $totalCogs = (float)$salesData['total_cogs'];
        $grossProfit = $totalSales - $totalCogs;

        // 2. Other Operating Incomes
        $incStmt = $pdo->prepare("SELECT 
                ic.name as category_name, COALESCE(SUM(i.amount), 0) as amount
            FROM incomes i
            JOIN income_categories ic ON i.category_id = ic.id
            WHERE i.shop_id = :s AND i.income_date BETWEEN :start AND :end
            GROUP BY ic.id");
        $incStmt->execute(['s' => $shopId, 'start' => $startDate, 'end' => $endDate]);
        $incomeBreakdown = $incStmt->fetchAll();
        $totalOtherIncome = (float)array_sum(array_column($incomeBreakdown, 'amount'));

        // 3. Operational Overhead Expenses
        $expStmt = $pdo->prepare("SELECT 
                ec.name as category_name, COALESCE(SUM(e.amount), 0) as amount
            FROM expenses e
            JOIN expense_categories ec ON e.category_id = ec.id
            WHERE e.shop_id = :s AND e.expense_date BETWEEN :start AND :end
            GROUP BY ec.id");
        $expStmt->execute(['s' => $shopId, 'start' => $startDate, 'end' => $endDate]);
        $expenseBreakdown = $expStmt->fetchAll();
        $totalExpenses = (float)array_sum(array_column($expenseBreakdown, 'amount'));

        // 4. Net Profit = Gross Profit + Other Income - Expenses
        $netProfit = $grossProfit + $totalOtherIncome - $totalExpenses;

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_sales' => $totalSales,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'gross_margin_pct' => ($totalSales > 0) ? ($grossProfit / $totalSales) * 100 : 0.0,
            'other_income' => [
                'total' => $totalOtherIncome,
                'breakdown' => $incomeBreakdown
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'breakdown' => $expenseBreakdown
            ],
            'net_profit' => $netProfit,
            'net_margin_pct' => ($totalSales > 0) ? ($netProfit / $totalSales) * 100 : 0.0
        ];
    }
}
