<?php
/**
 * Customer Advance Deposit & Allocation Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\DB;
use PDO;
use RuntimeException;

class CustomerAdvanceService
{
    /**
     * Accept New Advance Payment from Customer
     */
    public static function receiveAdvance(
        int $customerId,
        float $amount,
        string $paymentMethod,
        string $accountType,
        int $accountId,
        ?string $notes,
        int $userId,
        ?string $date = null,
        int $shopId = 1
    ): array {
        if ($amount <= 0 || !in_array($accountType, ['cash', 'bank'], true)) {
            throw new RuntimeException('A valid advance amount and account are required.');
        }
        return DB::transaction(function ($pdo) use ($customerId, $amount, $paymentMethod, $accountType, $accountId, $notes, $userId, $date, $shopId) {
            $customerStmt = $pdo->prepare('SELECT id FROM customers WHERE id = :id AND shop_id = :shop FOR UPDATE');
            $customerStmt->execute(['id' => $customerId, 'shop' => $shopId]);
            if (!$customerStmt->fetch()) {
                throw new RuntimeException('Customer was not found for this shop.');
            }
            $advanceNo = DocumentNumberService::getNextNumber('ADVANCE', $shopId);
            $rcptDate = $date ?? date('Y-m-d');

            // 1. Record Cash/Bank Inflow
            FinanceService::recordInflow(
                $accountType,
                $accountId,
                'CUSTOMER_ADVANCE',
                'CUSTOMER_ADVANCE',
                null,
                $advanceNo,
                $amount,
                "Advance received from customer - {$advanceNo}" . ($notes ? " ({$notes})" : ""),
                $userId,
                $rcptDate,
                $shopId
            );

            // 2. Insert into customer_advances
            $stmt = $pdo->prepare("INSERT INTO customer_advances 
                (shop_id, customer_id, advance_no, receipt_date, amount, adjusted_amount, refunded_amount, remaining_balance, payment_method, account_type, account_id, status, notes, created_by)
                VALUES (:s, :cid, :ano, :rdate, :amt, 0.00, 0.00, :rem, :pm, :atype, :aid, 'active', :notes, :uid)");
            
            $stmt->execute([
                's'     => $shopId,
                'cid'   => $customerId,
                'ano'   => $advanceNo,
                'rdate' => $rcptDate,
                'amt'   => $amount,
                'rem'   => $amount,
                'pm'    => $paymentMethod,
                'atype' => $accountType,
                'aid'   => $accountId,
                'notes' => $notes,
                'uid'   => $userId
            ]);

            $advanceId = (int)$pdo->lastInsertId();

            // 3. Update customer.current_advance
            $stmtCust = $pdo->prepare("UPDATE customers SET current_advance = current_advance + :amt, updated_at = NOW() WHERE id = :id");
            $stmtCust->execute(['amt' => $amount, 'id' => $customerId]);

            return [
                'advance_id' => $advanceId,
                'advance_no' => $advanceNo,
                'amount'     => $amount
            ];
        });
    }

    /**
     * Apply Available Advance balance to a Sales Memo
     *
     * @return float Total amount actually deducted from advances
     */
    public static function applyAdvanceToSale(
        int $customerId,
        int $saleId,
        float $desiredAmount,
        int $userId,
        int $shopId = 1
    ): float {
        if ($desiredAmount <= 0) {
            return 0.0;
        }

        $pdo = DB::pdo();
        
        // Fetch active customer advances ordered by oldest first (FIFO)
        $stmt = $pdo->prepare("SELECT id, advance_no, remaining_balance FROM customer_advances 
                               WHERE customer_id = :cid AND shop_id = :s AND status = 'active' AND remaining_balance > 0 
                               ORDER BY receipt_date ASC, id ASC FOR UPDATE");
        $stmt->execute(['cid' => $customerId, 's' => $shopId]);
        $advances = $stmt->fetchAll();

        $remainingToDeduct = $desiredAmount;
        $totalDeducted = 0.0;
        $today = date('Y-m-d');

        foreach ($advances as $adv) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $advId = (int)$adv['id'];
            $advBalance = (float)$adv['remaining_balance'];
            $deduction = min($remainingToDeduct, $advBalance);

            $newBalance = $advBalance - $deduction;
            $newStatus = ($newBalance <= 0.0001) ? 'fully_adjusted' : 'active';

            // Update advance row
            $updStmt = $pdo->prepare("UPDATE customer_advances 
                                      SET adjusted_amount = adjusted_amount + :ded, 
                                          remaining_balance = :nbal, 
                                          status = :stat, 
                                          updated_at = NOW() 
                                      WHERE id = :id");
            $updStmt->execute([
                'ded'  => $deduction,
                'nbal' => $newBalance,
                'stat' => $newStatus,
                'id'   => $advId
            ]);

            // Insert adjustment link
            $linkStmt = $pdo->prepare("INSERT INTO customer_advance_adjustments 
                (shop_id, advance_id, customer_id, sale_id, adjustment_date, amount, created_by) 
                VALUES (:s, :aid, :cid, :sid, :adate, :amt, :uid)");
            $linkStmt->execute([
                's'     => $shopId,
                'aid'   => $advId,
                'cid'   => $customerId,
                'sid'   => $saleId,
                'adate' => $today,
                'amt'   => $deduction,
                'uid'   => $userId
            ]);

            $totalDeducted += $deduction;
            $remainingToDeduct -= $deduction;
        }

        // Deduct from customer profile current_advance
        if ($totalDeducted > 0) {
            $updCust = $pdo->prepare("UPDATE customers SET current_advance = GREATEST(0, current_advance - :ded), updated_at = NOW() WHERE id = :id");
            $updCust->execute(['ded' => $totalDeducted, 'id' => $customerId]);
        }

        return $totalDeducted;
    }
}
