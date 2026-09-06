<?php
/**
 * Quick Sales & Two-Copy Memo Business Logic Engine
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\DB;
use PDO;
use RuntimeException;

class MemoService
{
    /**
     * Dynamically Calculate Top 20 Selling Products from sales volume
     */
    public static function getTop20Products(int $periodDays = 30, int $shopId = 1): array
    {
        $pdo = DB::pdo();
        $periodDays = max(1, min($periodDays, 3650));
        $cutoffDate = date('Y-m-d', strtotime("-{$periodDays} days"));
        $stmt = $pdo->prepare("SELECT p.id, p.sku, p.name, p.selling_price, p.min_selling_price, 
                                      p.current_stock, p.min_stock, p.base_unit_id,
                                      u.name as unit_name, u.symbol as unit_symbol,
                                      c.name as category_name, b.name as brand_name,
                                      COALESCE(SUM(CASE WHEN s.id IS NOT NULL THEN si.quantity ELSE 0 END), 0) as total_sold_qty,
                                      COUNT(DISTINCT s.id) as memo_count
                               FROM products p
                               JOIN units u ON p.base_unit_id = u.id
                               JOIN categories c ON p.category_id = c.id
                               LEFT JOIN brands b ON p.brand_id = b.id
                               LEFT JOIN sale_items si ON p.id = si.product_id
                               LEFT JOIN sales s ON si.sale_id = s.id 
                                    AND s.shop_id = :sales_shop 
                                    AND s.sale_status != 'cancelled'
                                    AND s.sale_date >= :cutoff_date
                               WHERE p.shop_id = :product_shop AND p.is_active = 1
                               GROUP BY p.id
                               ORDER BY total_sold_qty DESC, p.name ASC
                               LIMIT 20");
        $stmt->bindValue(':sales_shop', $shopId, PDO::PARAM_INT);
        $stmt->bindValue(':product_shop', $shopId, PDO::PARAM_INT);
        $stmt->bindValue(':cutoff_date', $cutoffDate);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get Recently Sold Products (Frequently billed items)
     */
    public static function getRecentlySoldProducts(int $limit = 10, int $shopId = 1): array
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT DISTINCT p.id, p.sku, p.name, p.selling_price, p.min_selling_price,
                                               p.current_stock, p.min_stock, p.base_unit_id,
                                               u.name as unit_name, u.symbol as unit_symbol,
                                               c.name as category_name, b.name as brand_name
                               FROM sale_items si
                               JOIN sales s ON si.sale_id = s.id
                               JOIN products p ON si.product_id = p.id
                               JOIN units u ON p.base_unit_id = u.id
                               JOIN categories c ON p.category_id = c.id
                               LEFT JOIN brands b ON p.brand_id = b.id
                               WHERE s.shop_id = :s AND s.sale_status != 'cancelled' AND p.is_active = 1
                               ORDER BY si.id DESC
                               LIMIT :lim");
        $stmt->bindValue(':s', $shopId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Create and Finalize Complete Sales Memo
     *
     * Strict Double-Entry & Atomic Workflow:
     * 1. Validate customer & products
     * 2. Preserves exact line-item order (01, 02, 03...)
     * 3. Deducts stock & snapshots lot cost
     * 4. Deducts customer advance if requested
     * 5. Handles overpayment -> auto converts to customer advance
     * 6. Updates Customer Ledger
     * 7. Records Cash/Bank Inflow
     * 8. Generates unique memo number
     */
    public static function createMemo(
        int $customerId,
        array $items,
        float $subtotal,
        string $discountType,
        float $discountValue,
        float $discountAmount,
        float $grandTotal,
        float $paidAmount,
        bool $useAdvance,
        string $accountType,
        int $accountId,
        ?string $notes,
        int $userId,
        ?string $saleDate = null,
        int $shopId = 1
    ): array {
        if (empty($items)) {
            throw new RuntimeException("Cannot create memo: No products selected.");
        }

        return DB::transaction(function ($pdo) use (
            $customerId, $items, $subtotal, $discountType, $discountValue, 
            $discountAmount, $grandTotal, $paidAmount, $useAdvance, 
            $accountType, $accountId, $notes, $userId, $saleDate, $shopId
        ) {
            $memoNo = DocumentNumberService::getNextNumber('MEMO', $shopId);
            $sDate = $saleDate ?? date('Y-m-d');

            if (!in_array($discountType, ['fixed', 'percentage'], true) || $discountValue < 0 || $paidAmount < 0) {
                throw new RuntimeException('Invalid discount or payment amount.');
            }
            if (!in_array($accountType, ['cash', 'bank'], true)) {
                throw new RuntimeException('Invalid payment account type.');
            }

            // 1. Fetch customer details. Every mutable resource is scoped to the
            // signed-in shop; IDs from another shop must never be accepted.
            $stmtCust = $pdo->prepare("SELECT id, name, mobile, address, current_due, current_advance FROM customers WHERE id = :id AND shop_id = :shop FOR UPDATE");
            $stmtCust->execute(['id' => $customerId, 'shop' => $shopId]);
            $customer = $stmtCust->fetch();

            if (!$customer) {
                throw new RuntimeException("Customer ID {$customerId} not found.");
            }

            // Recalculate every money value on the server. Browser totals are for
            // display only and must not become the accounting source of truth.
            $calculatedItems = [];
            $calculatedSubtotal = 0.0;
            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $unitId = (int)($item['unit_id'] ?? 0);
                $quantity = round((float)($item['quantity'] ?? 0), 4);
                $unitPrice = round((float)($item['unit_price'] ?? 0), 2);
                $lineDiscount = round((float)($item['discount_amount'] ?? 0), 2);

                if ($productId <= 0 || $unitId <= 0 || $quantity <= 0 || $unitPrice < 0 || $lineDiscount < 0) {
                    throw new RuntimeException('Each memo item needs a valid product, unit, quantity, price, and discount.');
                }

                $productStmt = $pdo->prepare('SELECT id, base_unit_id, selling_price, min_selling_price FROM products WHERE id = :id AND shop_id = :shop AND is_active = 1');
                $productStmt->execute(['id' => $productId, 'shop' => $shopId]);
                $productForPrice = $productStmt->fetch();
                if (!$productForPrice) {
                    throw new RuntimeException("Product ID {$productId} is unavailable for this shop.");
                }
                if ((float)$productForPrice['min_selling_price'] > 0 && $unitPrice + 0.0001 < (float)$productForPrice['min_selling_price']) {
                    throw new RuntimeException("The selected rate is below the minimum selling price for product ID {$productId}.");
                }
                if ($unitPrice - 0.0001 > (float)$productForPrice['selling_price']) {
                    throw new RuntimeException("The selected rate is above the selling price for product ID {$productId}.");
                }

                $baseQty = StockEngineService::convertToBaseUnit($quantity, $unitId, (int)$productForPrice['base_unit_id'], $shopId);
                $grossLine = round($quantity * $unitPrice, 2);
                if ($lineDiscount > $grossLine) {
                    throw new RuntimeException("Line discount cannot exceed the item total for product ID {$productId}.");
                }
                $lineTotal = round($grossLine - $lineDiscount, 2);
                $calculatedSubtotal = round($calculatedSubtotal + $lineTotal, 2);
                $calculatedItems[] = compact('productId', 'unitId', 'quantity', 'unitPrice', 'lineDiscount', 'lineTotal', 'baseQty');
            }

            $subtotal = $calculatedSubtotal;
            $discountAmount = $discountType === 'percentage'
                ? round($subtotal * $discountValue / 100, 2)
                : round($discountValue, 2);
            if ($discountAmount > $subtotal) {
                throw new RuntimeException('Memo discount cannot exceed the subtotal.');
            }
            $grandTotal = round($subtotal - $discountAmount, 2);
            $paidAmount = round($paidAmount, 2);

            // 2. Check & apply customer advance if selected
            $advanceAvailable = (float)$customer['current_advance'];
            $advanceToUse = 0.0;

            if ($useAdvance && $advanceAvailable > 0) {
                // Advance can cover up to Grand Total - Paid Now
                $maxAdvanceNeeded = max(0.0, $grandTotal - $paidAmount);
                $advanceToUse = min($advanceAvailable, $maxAdvanceNeeded);
            }

            // 3. Calculate Due & Overpayment
            $totalDeductions = $paidAmount + $advanceToUse;
            $dueAmount = max(0.0, $grandTotal - $totalDeductions);
            $overpayment = max(0.0, $totalDeductions - $grandTotal);

            // If overpayment occurred, adjust actual paid_amount recognized for this sale
            $effectivePaidForSale = $paidAmount;
            $overpaymentAdvance = 0.0;

            if ($overpayment > 0 && $paidAmount > 0) {
                // The excess cash goes directly to customer advance
                $overpaymentAdvance = $overpayment;
                $effectivePaidForSale = $paidAmount - $overpaymentAdvance;
            }

            // Determine Payment Status
            $paymentStatus = 'due';
            if ($dueAmount <= 0.001) {
                $paymentStatus = ($advanceToUse > 0 && $effectivePaidForSale <= 0) ? 'advance_settled' : 'paid';
            } elseif ($effectivePaidForSale > 0 || $advanceToUse > 0) {
                $paymentStatus = 'partial';
            }

            // 4. Insert Sales Header
            $stmtSale = $pdo->prepare("INSERT INTO sales 
                (shop_id, memo_number, customer_id, sale_date, subtotal, discount_type, discount_value, discount_amount, grand_total, paid_amount, advance_used, due_amount, total_cogs, gross_profit, payment_status, sale_status, notes, created_by)
                VALUES (:s, :mno, :cid, :sdate, :sub, :dtype, :dval, :damt, :gtot, :paid, :adv, :due, 0.00, 0.00, :pstat, 'completed', :notes, :uid)");

            $stmtSale->execute([
                's'     => $shopId,
                'mno'   => $memoNo,
                'cid'   => $customerId,
                'sdate' => $sDate,
                'sub'   => $subtotal,
                'dtype' => $discountType,
                'dval'  => $discountValue,
                'damt'  => $discountAmount,
                'gtot'  => $grandTotal,
                'paid'  => $effectivePaidForSale,
                'adv'   => $advanceToUse,
                'due'   => $dueAmount,
                'pstat' => $paymentStatus,
                'notes' => $notes,
                'uid'   => $userId
            ]);

            $saleId = (int)$pdo->lastInsertId();

            // 5. Insert Line Items, Deduct Stock & Calculate COGS
            $itemStmt = $pdo->prepare("INSERT INTO sale_items 
                (sale_id, shop_id, item_order, product_id, unit_id, quantity, base_quantity, unit_price, unit_cost, discount_amount, line_total, cogs_total)
                VALUES (:sid, :s, :iorder, :pid, :uid, :qty, :bqty, :price, :cost, :damt, :ltot, :cogs)");

            $totalSaleCogs = 0.0;
            $orderIndex = 1;

            foreach ($calculatedItems as $item) {
                $productId = $item['productId'];
                $unitId = $item['unitId'];
                $quantity = $item['quantity'];
                $unitPrice = $item['unitPrice'];
                $lineDiscount = $item['lineDiscount'];
                $lineTotal = $item['lineTotal'];

                // Fetch product cost snapshot & base unit
                $prodStmt = $pdo->prepare("SELECT base_unit_id, average_cost, purchase_price, current_stock FROM products WHERE id = :id AND shop_id = :shop AND is_active = 1 FOR UPDATE");
                $prodStmt->execute(['id' => $productId, 'shop' => $shopId]);
                $product = $prodStmt->fetch();

                if (!$product) {
                    throw new RuntimeException("Product ID {$productId} not found.");
                }

                $baseUnitId = (int)$product['base_unit_id'];
                $baseQty = $item['baseQty'];

                // Use average cost if available, otherwise purchase price
                $unitCost = (float)$product['average_cost'] > 0 ? (float)$product['average_cost'] : (float)$product['purchase_price'];
                $lineCogs = $baseQty * $unitCost;
                $totalSaleCogs += $lineCogs;

                $itemStmt->execute([
                    'sid'    => $saleId,
                    's'      => $shopId,
                    'iorder' => $orderIndex++,
                    'pid'    => $productId,
                    'uid'    => $unitId,
                    'qty'    => $quantity,
                    'bqty'   => $baseQty,
                    'price'  => $unitPrice,
                    'cost'   => $unitCost,
                    'damt'   => $lineDiscount,
                    'ltot'   => $lineTotal,
                    'cogs'   => $lineCogs
                ]);

                // Record stock movement (OUTFLOW)
                StockEngineService::recordMovement(
                    $productId,
                    'SALE',
                    $quantity,
                    $unitId,
                    $unitCost,
                    'SALE',
                    $saleId,
                    $memoNo,
                    $userId,
                    "Sales Memo [{$memoNo}]",
                    $shopId
                );
            }

            // Update COGS & Gross Profit in sale record
            $grossProfit = $grandTotal - $totalSaleCogs;
            $updSale = $pdo->prepare("UPDATE sales SET total_cogs = :cogs, gross_profit = :gp WHERE id = :id");
            $updSale->execute(['cogs' => $totalSaleCogs, 'gp' => $grossProfit, 'id' => $saleId]);

            // 6. Apply Advance Adjustments if any
            if ($advanceToUse > 0) {
                CustomerAdvanceService::applyAdvanceToSale($customerId, $saleId, $advanceToUse, $userId, $shopId);
            }

            // 7. Update Customer Ledger
            // Debit: Full Grand Total (Total Receivable increase)
            CustomerLedgerService::recordEntry(
                $customerId,
                'SALE',
                'SALE',
                $saleId,
                $memoNo,
                "Sales Memo [{$memoNo}]",
                $grandTotal,
                0.00,
                $userId,
                $sDate,
                $shopId
            );

            // Credit: Paid Amount at checkout
            if ($effectivePaidForSale > 0) {
                CustomerLedgerService::recordEntry(
                    $customerId,
                    'PAYMENT',
                    'SALE_PAYMENT',
                    $saleId,
                    $memoNo,
                    "Payment received at memo checkout [{$memoNo}]",
                    0.00,
                    $effectivePaidForSale,
                    $userId,
                    $sDate,
                    $shopId
                );

                // Record Financial Account Inflow
                FinanceService::recordInflow(
                    $accountType,
                    $accountId,
                    'SALE_PAYMENT',
                    'SALE',
                    $saleId,
                    $memoNo,
                    $effectivePaidForSale,
                    "Sales memo payment - {$memoNo}",
                    $userId,
                    $sDate,
                    $shopId
                );

                // Insert sale_payments record
                $payNo = DocumentNumberService::getNextNumber('PAYMENT', $shopId);
                $stmtSP = $pdo->prepare("INSERT INTO sale_payments 
                    (shop_id, payment_no, sale_id, customer_id, payment_date, amount, payment_method, account_type, account_id, notes, created_by)
                    VALUES (:s, :pno, :sid, :cid, :pdate, :amt, 'Checkout Payment', :atype, :aid, 'Initial Memo Payment', :uid)");
                $stmtSP->execute([
                    's'     => $shopId,
                    'pno'   => $payNo,
                    'sid'   => $saleId,
                    'cid'   => $customerId,
                    'pdate' => $sDate,
                    'amt'   => $effectivePaidForSale,
                    'atype' => $accountType,
                    'aid'   => $accountId,
                    'uid'   => $userId
                ]);
            }

            // Credit: Advance Used
            if ($advanceToUse > 0) {
                CustomerLedgerService::recordEntry(
                    $customerId,
                    'ADVANCE_ADJUSTMENT',
                    'SALE_ADVANCE',
                    $saleId,
                    $memoNo,
                    "Advance balance adjusted against memo [{$memoNo}]",
                    0.00,
                    $advanceToUse,
                    $userId,
                    $sDate,
                    $shopId
                );
            }

            // 8. If Overpayment Advance was created, deposit into customer advance
            if ($overpaymentAdvance > 0) {
                CustomerAdvanceService::receiveAdvance(
                    $customerId,
                    $overpaymentAdvance,
                    'Cash (Memo Overpayment)',
                    $accountType,
                    $accountId,
                    "Overpayment balance from Memo [{$memoNo}]",
                    $userId,
                    $sDate,
                    $shopId
                );
            }

            return [
                'sale_id'             => $saleId,
                'memo_number'         => $memoNo,
                'grand_total'         => $grandTotal,
                'paid_amount'         => $effectivePaidForSale,
                'advance_used'        => $advanceToUse,
                'due_amount'          => $dueAmount,
                'overpayment_advance' => $overpaymentAdvance
            ];
        });
    }

    /**
     * Get Complete Memo Details for View / Two-Copy Print
     */
    public static function getMemoDetails(int|string $saleIdOrNumber, int $shopId = 1): ?array
    {
        $pdo = DB::pdo();

        $whereClause = is_numeric($saleIdOrNumber) ? "s.id = :val" : "s.memo_number = :val";
        $stmt = $pdo->prepare("SELECT s.*, 
                                      c.name as customer_name, c.mobile as customer_mobile, 
                                      c.address as customer_address, c.customer_code,
                                      c.current_due as customer_total_due, c.current_advance as customer_total_advance,
                                      u.full_name as created_by_name
                               FROM sales s
                               JOIN customers c ON s.customer_id = c.id
                               JOIN users u ON s.created_by = u.id
                               WHERE s.shop_id = :s AND {$whereClause} LIMIT 1");
        $stmt->execute(['s' => $shopId, 'val' => $saleIdOrNumber]);
        $sale = $stmt->fetch();

        if (!$sale) {
            return null;
        }

        // Fetch Items in exact serial order
        $itemStmt = $pdo->prepare("SELECT si.*, p.name as product_name, p.sku, 
                                          u.name as unit_name, u.symbol as unit_symbol,
                                          b.name as brand_name, cat.name as category_name
                                   FROM sale_items si
                                   JOIN products p ON si.product_id = p.id
                                   JOIN units u ON si.unit_id = u.id
                                   LEFT JOIN brands b ON p.brand_id = b.id
                                   LEFT JOIN categories cat ON p.category_id = cat.id
                                   WHERE si.sale_id = :sid
                                   ORDER BY si.item_order ASC, si.id ASC");
        $itemStmt->execute(['sid' => $sale['id']]);
        $sale['items'] = $itemStmt->fetchAll();

        // Fetch Payments
        $payStmt = $pdo->prepare("SELECT * FROM sale_payments WHERE sale_id = :sid ORDER BY id ASC");
        $payStmt->execute(['sid' => $sale['id']]);
        $sale['payments'] = $payStmt->fetchAll();

        return $sale;
    }
}
