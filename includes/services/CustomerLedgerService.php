<?php
/**
 * Customer Ledger & Accounts Receivable Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\DB;
use PDO;
use RuntimeException;

class CustomerLedgerService
{
    /**
     * Record a transaction in Customer Ledger and update current_due atomically
     */
    public static function recordEntry(
        int $customerId,
        string $transactionType,
        string $referenceType,
        ?int $referenceId,
        string $referenceNo,
        string $description,
        float $debit,
        float $credit,
        int $userId,
        ?string $date = null,
        int $shopId = 1
    ): float {
        $pdo = DB::pdo();
        $txnDate = $date ?? date('Y-m-d');

        // Lock customer row
        $stmt = $pdo->prepare("SELECT id, current_due FROM customers WHERE id = :id AND shop_id = :shop FOR UPDATE");
        $stmt->execute(['id' => $customerId, 'shop' => $shopId]);
        $customer = $stmt->fetch();

        if (!$customer) {
            throw new RuntimeException("Customer ID {$customerId} not found.");
        }

        // Net Due Change = Debit (New Due/Sale) - Credit (Payment/Adjustment/Return)
        $currentDue = (float)$customer['current_due'];
        $newDue = $currentDue + $debit - $credit;
        if ($newDue < -0.001) {
            throw new RuntimeException('Payment exceeds the customer due. Record the excess as customer advance instead.');
        }

        // Insert into customer_ledger
        $insertStmt = $pdo->prepare("INSERT INTO customer_ledger 
            (shop_id, customer_id, transaction_date, transaction_type, reference_type, reference_id, reference_no, description, debit, credit, running_balance, user_id)
            VALUES (:s, :cid, :tdate, :ttype, :rtype, :rid, :rno, :desc, :deb, :cred, :rbal, :uid)");

        $insertStmt->execute([
            's'     => $shopId,
            'cid'   => $customerId,
            'tdate' => $txnDate,
            'ttype' => $transactionType,
            'rtype' => $referenceType,
            'rid'   => $referenceId,
            'rno'   => $referenceNo,
            'desc'  => $description,
            'deb'   => $debit,
            'cred'  => $credit,
            'rbal'  => $newDue,
            'uid'   => $userId
        ]);

        // Update customer profile cached current_due
        $updateStmt = $pdo->prepare("UPDATE customers SET current_due = :due, updated_at = NOW() WHERE id = :id");
        $updateStmt->execute([
            'due' => $newDue,
            'id'  => $customerId
        ]);

        return $newDue;
    }

    /**
     * Process Direct Customer Due Payment Collection
     */
    public static function receivePayment(
        int $customerId,
        float $amount,
        string $paymentMethod,
        string $accountType,
        int $accountId,
        ?string $notes,
        int $userId,
        ?string $date = null,
        ?int $saleId = null,
        int $shopId = 1
    ): array {
        return DB::transaction(function ($pdo) use ($customerId, $amount, $paymentMethod, $accountType, $accountId, $notes, $userId, $date, $saleId, $shopId) {
            $paymentNo = DocumentNumberService::getNextNumber('PAYMENT', $shopId);
            $payDate = $date ?? date('Y-m-d');

            // 1. Record in financial_transactions
            FinanceService::recordInflow(
                $accountType,
                $accountId,
                'CUSTOMER_COLLECTION',
                'CUSTOMER_PAYMENT',
                null,
                $paymentNo,
                $amount,
                "Customer due payment received - {$paymentNo}" . ($notes ? " ({$notes})" : ""),
                $userId,
                $payDate,
                $shopId
            );

            // 2. Record in Customer Ledger
            $desc = "Payment received via {$paymentMethod} [{$paymentNo}]";
            if ($notes) {
                $desc .= " - {$notes}";
            }

            $newDue = self::recordEntry(
                $customerId,
                'PAYMENT',
                'PAYMENT',
                null,
                $paymentNo,
                $desc,
                0.00,
                $amount,
                $userId,
                $payDate,
                $shopId
            );

            return [
                'payment_no' => $paymentNo,
                'amount'     => $amount,
                'new_due'    => $newDue
            ];
        });
    }

    /**
     * Get Frequently / Recently Active Customers for Fast POS Screen
     */
    public static function getRecentCustomers(int $limit = 8, int $shopId = 1): array
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT c.id, c.customer_code, c.name, c.mobile, c.address, c.customer_type, c.current_due, c.current_advance,
                                      MAX(s.created_at) as last_sale_time, COUNT(s.id) as total_memos
                               FROM customers c
                               LEFT JOIN sales s ON c.id = s.customer_id AND s.shop_id = :sales_shop
                               WHERE c.shop_id = :customer_shop AND c.status = 'active'
                               GROUP BY c.id
                               ORDER BY last_sale_time DESC, c.id DESC
                               LIMIT :lim");
        $stmt->bindValue(':sales_shop', $shopId, PDO::PARAM_INT);
        $stmt->bindValue(':customer_shop', $shopId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
