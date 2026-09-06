<?php
/**
 * Supplier Ledger & Procurement Accounts Payable Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\DB;
use PDO;
use RuntimeException;

class SupplierLedgerService
{
    /**
     * Record a transaction in Supplier Ledger and update current_due atomically
     */
    public static function recordEntry(
        int $supplierId,
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

        // Lock supplier row
        $stmt = $pdo->prepare("SELECT id, current_due FROM suppliers WHERE id = :id FOR UPDATE");
        $stmt->execute(['id' => $supplierId]);
        $supplier = $stmt->fetch();

        if (!$supplier) {
            throw new RuntimeException("Supplier ID {$supplierId} not found.");
        }

        // Net Payable Change = Credit (New Purchases) - Debit (Payments/Returns)
        $currentDue = (float)$supplier['current_due'];
        $newDue = $currentDue + $credit - $debit;

        // Insert into supplier_ledger
        $insertStmt = $pdo->prepare("INSERT INTO supplier_ledger 
            (shop_id, supplier_id, transaction_date, transaction_type, reference_type, reference_id, reference_no, description, debit, credit, running_balance, user_id)
            VALUES (:s, :sid, :tdate, :ttype, :rtype, :rid, :rno, :desc, :deb, :cred, :rbal, :uid)");

        $insertStmt->execute([
            's'     => $shopId,
            'sid'   => $supplierId,
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

        // Update supplier profile cached current_due
        $updateStmt = $pdo->prepare("UPDATE suppliers SET current_due = :due, updated_at = NOW() WHERE id = :id");
        $updateStmt->execute([
            'due' => $newDue,
            'id'  => $supplierId
        ]);

        return $newDue;
    }

    /**
     * Create Complete Material Procurement Purchase Voucher
     */
    public static function createPurchase(
        int $supplierId,
        array $items,
        float $subtotal,
        float $discountAmount,
        float $grandTotal,
        float $paidAmount,
        string $accountType,
        int $accountId,
        ?string $notes,
        int $userId,
        ?string $purchaseDate = null,
        int $shopId = 1
    ): array {
        return DB::transaction(function ($pdo) use (
            $supplierId, $items, $subtotal, $discountAmount, $grandTotal, 
            $paidAmount, $accountType, $accountId, $notes, $userId, $purchaseDate, $shopId
        ) {
            $purchaseNo = DocumentNumberService::getNextNumber('PURCHASE', $shopId);
            $pDate = $purchaseDate ?? date('Y-m-d');
            $dueAmount = max(0.0, $grandTotal - $paidAmount);
            $payStatus = ($dueAmount <= 0.001) ? 'paid' : (($paidAmount > 0) ? 'partial' : 'due');

            // 1. Insert into purchases table
            $stmt = $pdo->prepare("INSERT INTO purchases 
                (shop_id, purchase_number, supplier_id, purchase_date, subtotal, discount_amount, grand_total, paid_amount, due_amount, payment_status, purchase_status, notes, created_by)
                VALUES (:s, :pno, :sid, :pdate, :sub, :disc, :gtot, :paid, :due, :pstat, 'received', :notes, :uid)");
            
            $stmt->execute([
                's'     => $shopId,
                'pno'   => $purchaseNo,
                'sid'   => $supplierId,
                'pdate' => $pDate,
                'sub'   => $subtotal,
                'disc'  => $discountAmount,
                'gtot'  => $grandTotal,
                'paid'  => $paidAmount,
                'due'   => $dueAmount,
                'pstat' => $payStatus,
                'notes' => $notes,
                'uid'   => $userId
            ]);

            $purchaseId = (int)$pdo->lastInsertId();

            // 2. Insert line items & log Stock In
            $itemStmt = $pdo->prepare("INSERT INTO purchase_items 
                (purchase_id, shop_id, product_id, unit_id, quantity, base_quantity, unit_cost, line_total)
                VALUES (:pid, :s, :prod_id, :uid, :qty, :bqty, :cost, :ltot)");

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $unitId = (int)$item['unit_id'];
                $quantity = (float)$item['quantity'];
                $unitCost = (float)$item['unit_cost'];
                $lineTotal = (float)$item['line_total'];

                // Get base unit for product
                $prodStmt = $pdo->prepare("SELECT base_unit_id FROM products WHERE id = :id");
                $prodStmt->execute(['id' => $productId]);
                $baseUnitId = (int)$prodStmt->fetchColumn();

                $baseQty = StockEngineService::convertToBaseUnit($quantity, $unitId, $baseUnitId, $shopId);

                $itemStmt->execute([
                    'pid'     => $purchaseId,
                    's'       => $shopId,
                    'prod_id' => $productId,
                    'uid'     => $unitId,
                    'qty'     => $quantity,
                    'bqty'    => $baseQty,
                    'cost'    => $unitCost,
                    'ltot'    => $lineTotal
                ]);

                // Record stock movement (INFLOW)
                StockEngineService::recordMovement(
                    $productId,
                    'PURCHASE',
                    $quantity,
                    $unitId,
                    $unitCost,
                    'PURCHASE',
                    $purchaseId,
                    $purchaseNo,
                    $userId,
                    "Procured from supplier [{$purchaseNo}]",
                    $shopId
                );
            }

            // 3. Update Supplier Ledger
            // Credit: Full Grand Total (Total Payable increase)
            self::recordEntry(
                $supplierId,
                'PURCHASE',
                'PURCHASE',
                $purchaseId,
                $purchaseNo,
                "Material Purchase [{$purchaseNo}]",
                0.00,
                $grandTotal,
                $userId,
                $pDate,
                $shopId
            );

            // 4. If paid now, record payment in Supplier Ledger and Outflow in Financial Accounts
            if ($paidAmount > 0) {
                FinanceService::recordOutflow(
                    $accountType,
                    $accountId,
                    'PURCHASE_PAYMENT',
                    'PURCHASE',
                    $purchaseId,
                    $purchaseNo,
                    $paidAmount,
                    "Payment for Purchase [{$purchaseNo}]",
                    $userId,
                    $pDate,
                    $shopId
                );

                // Debit: Paid Amount (Payable decrease)
                self::recordEntry(
                    $supplierId,
                    'PAYMENT',
                    'PURCHASE_PAYMENT',
                    $purchaseId,
                    $purchaseNo,
                    "Paid during purchase checkout [{$purchaseNo}]",
                    $paidAmount,
                    0.00,
                    $userId,
                    $pDate,
                    $shopId
                );
            }

            return [
                'purchase_id'     => $purchaseId,
                'purchase_number' => $purchaseNo,
                'grand_total'     => $grandTotal,
                'paid_amount'     => $paidAmount,
                'due_amount'      => $dueAmount
            ];
        });
    }

    /**
     * Make Direct Supplier Payment (Disbursement)
     */
    public static function makePayment(
        int $supplierId,
        float $amount,
        string $paymentMethod,
        string $accountType,
        int $accountId,
        ?string $notes,
        int $userId,
        ?string $date = null,
        int $shopId = 1
    ): array {
        return DB::transaction(function ($pdo) use ($supplierId, $amount, $paymentMethod, $accountType, $accountId, $notes, $userId, $date, $shopId) {
            $paymentNo = DocumentNumberService::getNextNumber('PAYMENT', $shopId);
            $payDate = $date ?? date('Y-m-d');

            // 1. Record Outflow in Cash/Bank
            FinanceService::recordOutflow(
                $accountType,
                $accountId,
                'SUPPLIER_PAYMENT',
                'SUPPLIER_PAYMENT',
                null,
                $paymentNo,
                $amount,
                "Supplier payment disbursed - {$paymentNo}" . ($notes ? " ({$notes})" : ""),
                $userId,
                $payDate,
                $shopId
            );

            // 2. Record Debit in Supplier Ledger
            $desc = "Payment made via {$paymentMethod} [{$paymentNo}]" . ($notes ? " - {$notes}" : "");
            $newDue = self::recordEntry(
                $supplierId,
                'PAYMENT',
                'PAYMENT',
                null,
                $paymentNo,
                $desc,
                $amount,
                0.00,
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
}
