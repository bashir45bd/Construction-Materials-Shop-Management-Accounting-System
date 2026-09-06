<?php
/**
 * Reports & Business Intelligence Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\DB;
use PDO;

class ReportService
{
    /**
     * Get Real-time Dashboard KPIs based on selected period
     */
    public static function getDashboardKPIs(string $period = 'today', int $shopId = 1): array
    {
        $pdo = DB::pdo();
        
        $today = date('Y-m-d');
        $startDate = $today;
        $endDate = $today;

        switch ($period) {
            case 'yesterday':
                $startDate = date('Y-m-d', strtotime('-1 day'));
                $endDate = $startDate;
                break;
            case 'this_week':
                $startDate = date('Y-m-d', strtotime('monday this week'));
                $endDate = $today;
                break;
            case 'this_month':
                $startDate = date('Y-m-01');
                $endDate = $today;
                break;
            case 'this_year':
                $startDate = date('Y-01-01');
                $endDate = $today;
                break;
        }

        // 1. Sales & Profit in Period
        $salesStmt = $pdo->prepare("SELECT 
                COALESCE(SUM(grand_total), 0) as total_sales,
                COALESCE(SUM(paid_amount), 0) as total_collected_on_sales,
                COALESCE(SUM(gross_profit), 0) as gross_profit,
                COALESCE(SUM(due_amount), 0) as new_due_created,
                COUNT(id) as memo_count
            FROM sales 
            WHERE shop_id = :s AND sale_status != 'cancelled' AND sale_date BETWEEN :start AND :end");
        $salesStmt->execute(['s' => $shopId, 'start' => $startDate, 'end' => $endDate]);
        $salesData = $salesStmt->fetch();

        // 2. Direct Customer Due Collections in Period
        $collStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as direct_collections 
            FROM financial_transactions 
            WHERE shop_id = :s AND category = 'CUSTOMER_COLLECTION' AND transaction_date BETWEEN :start AND :end");
        $collStmt->execute(['s' => $shopId, 'start' => $startDate, 'end' => $endDate]);
        $directCollections = (float)$collStmt->fetchColumn();

        // 3. Operational Expenses in Period
        $expStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_expenses 
            FROM expenses 
            WHERE shop_id = :s AND expense_date BETWEEN :start AND :end");
        $expStmt->execute(['s' => $shopId, 'start' => $startDate, 'end' => $endDate]);
        $totalExpenses = (float)$expStmt->fetchColumn();

        // 4. Incomes in Period
        $incStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_income 
            FROM incomes 
            WHERE shop_id = :s AND income_date BETWEEN :start AND :end");
        $incStmt->execute(['s' => $shopId, 'start' => $startDate, 'end' => $endDate]);
        $totalIncome = (float)$incStmt->fetchColumn();

        // 5. Total Cumulative Customer Due & Advance
        $custStmt = $pdo->prepare("SELECT 
                COALESCE(SUM(current_due), 0) as total_customer_due,
                COALESCE(SUM(current_advance), 0) as total_customer_advance 
            FROM customers WHERE shop_id = :s AND status = 'active'");
        $custStmt->execute(['s' => $shopId]);
        $custBalances = $custStmt->fetch();

        // 6. Total Cumulative Supplier Due
        $suppStmt = $pdo->prepare("SELECT COALESCE(SUM(current_due), 0) as total_supplier_due 
            FROM suppliers WHERE shop_id = :s AND status = 'active'");
        $suppStmt->execute(['s' => $shopId]);
        $totalSupplierDue = (float)$suppStmt->fetchColumn();

        // 7. Inventory Stock Value
        $stockStmt = $pdo->prepare("SELECT 
                COALESCE(SUM(current_stock * average_cost), 0) as stock_valuation,
                COUNT(id) as total_products,
                SUM(CASE WHEN current_stock <= min_stock THEN 1 ELSE 0 END) as low_stock_count
            FROM products WHERE shop_id = :s AND is_active = 1");
        $stockStmt->execute(['s' => $shopId]);
        $stockData = $stockStmt->fetch();

        // 8. Total Liquid Cash & Bank Balances
        $cashStmt = $pdo->prepare("SELECT COALESCE(SUM(current_balance), 0) FROM cash_accounts WHERE shop_id = :s AND status = 'active'");
        $cashStmt->execute(['s' => $shopId]);
        $totalCash = (float)$cashStmt->fetchColumn();

        $bankStmt = $pdo->prepare("SELECT COALESCE(SUM(current_balance), 0) FROM bank_accounts WHERE shop_id = :s AND status = 'active'");
        $bankStmt->execute(['s' => $shopId]);
        $totalBank = (float)$bankStmt->fetchColumn();

        $totalSales = (float)$salesData['total_sales'];
        $grossProfit = (float)$salesData['gross_profit'];
        $netProfit = $grossProfit + $totalIncome - $totalExpenses;
        $totalCollection = (float)$salesData['total_collected_on_sales'] + $directCollections;

        return [
            'period'                 => $period,
            'start_date'             => $startDate,
            'end_date'               => $endDate,
            'total_sales'            => $totalSales,
            'memo_count'             => (int)$salesData['memo_count'],
            'total_collection'       => $totalCollection,
            'customer_due'           => (float)$custBalances['total_customer_due'],
            'customer_advance'       => (float)$custBalances['total_customer_advance'],
            'supplier_due'           => $totalSupplierDue,
            'stock_valuation'        => (float)$stockData['stock_valuation'],
            'low_stock_count'        => (int)$stockData['low_stock_count'],
            'total_expenses'         => $totalExpenses,
            'gross_profit'           => $grossProfit,
            'net_profit'             => $netProfit,
            'cash_balance'           => $totalCash,
            'bank_balance'           => $totalBank,
            'total_liquid_funds'     => $totalCash + $totalBank
        ];
    }
}
