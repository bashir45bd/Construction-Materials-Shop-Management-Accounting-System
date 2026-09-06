<?php
/**
 * Stock Engine & Perpetual Inventory Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

use App\Database\DB;
use App\Config\Database;
use PDO;
use Exception;
use RuntimeException;

class StockEngineService
{
    /**
     * Convert Quantity across units using conversion matrix
     */
    public static function convertToBaseUnit(float $quantity, int $unitId, int $baseUnitId, int $shopId = 1): float
    {
        if ($unitId === $baseUnitId) {
            return $quantity;
        }

        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT conversion_factor FROM unit_conversions 
                               WHERE shop_id = :s AND from_unit_id = :from AND to_unit_id = :to LIMIT 1");
        $stmt->execute(['s' => $shopId, 'from' => $unitId, 'to' => $baseUnitId]);
        $factor = $stmt->fetchColumn();

        if ($factor !== false) {
            return $quantity * (float)$factor;
        }

        // Check inverse conversion
        $stmtInv = $pdo->prepare("SELECT conversion_factor FROM unit_conversions 
                                  WHERE shop_id = :s AND from_unit_id = :from AND to_unit_id = :to LIMIT 1");
        $stmtInv->execute(['s' => $shopId, 'from' => $baseUnitId, 'to' => $unitId]);
        $invFactor = $stmtInv->fetchColumn();

        if ($invFactor !== false && (float)$invFactor > 0) {
            return $quantity / (float)$invFactor;
        }

        // A missing conversion must never be silently treated as 1:1.  Doing so
        // corrupts stock when, for example, Ton is sold against a KG base unit.
        throw new RuntimeException('No compatible unit conversion is configured for this product.');
    }

    /**
     * Record Immutable Stock Movement and atomically update Product Stock
     *
     * @param string $movementType ('OPENING_STOCK', 'PURCHASE', 'SALE', 'SALE_RETURN', 'PURCHASE_RETURN', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT')
     */
    public static function recordMovement(
        int $productId,
        string $movementType,
        float $quantity,
        int $unitId,
        float $unitCost,
        string $referenceType,
        ?int $referenceId,
        string $referenceNo,
        int $userId,
        ?string $notes = null,
        int $shopId = 1
    ): float {
        $pdo = DB::pdo();

        // Lock product row for update (Pessimistic Locking)
        $stmt = $pdo->prepare("SELECT id, base_unit_id, current_stock, average_cost FROM products WHERE id = :id AND shop_id = :shop FOR UPDATE");
        $stmt->execute(['id' => $productId, 'shop' => $shopId]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new RuntimeException("Product ID {$productId} not found.");
        }

        $baseUnitId = (int)$product['base_unit_id'];
        $baseQty = self::convertToBaseUnit($quantity, $unitId, $baseUnitId, $shopId);

        // Determine direction
        $inflowTypes = ['OPENING_STOCK', 'PURCHASE', 'SALE_RETURN', 'ADJUSTMENT_IN'];
        $outflowTypes = ['SALE', 'PURCHASE_RETURN', 'ADJUSTMENT_OUT'];

        $isOutflow = in_array($movementType, $outflowTypes, true);
        $signedBaseQty = $isOutflow ? -abs($baseQty) : abs($baseQty);
        $signedQty = $isOutflow ? -abs($quantity) : abs($quantity);

        $currentStock = (float)$product['current_stock'];
        $newStock = $currentStock + $signedBaseQty;

        if ($isOutflow && $newStock < -0.0001) {
            throw new RuntimeException("Insufficient stock for product ID {$productId}.");
        }

        // Calculate weighted average cost on new purchases / opening stock
        $newAverageCost = (float)$product['average_cost'];
        if (in_array($movementType, ['PURCHASE', 'OPENING_STOCK'], true) && $newStock > 0) {
            $totalOldValue = max(0, $currentStock) * (float)$product['average_cost'];
            $addedValue = abs($baseQty) * $unitCost;
            $newAverageCost = ($totalOldValue + $addedValue) / $newStock;
        }

        // Insert into stock_movements
        $insertStmt = $pdo->prepare("INSERT INTO stock_movements 
            (shop_id, product_id, movement_type, reference_type, reference_id, reference_no, quantity, unit_id, base_quantity, unit_cost, balance_after, user_id, notes) 
            VALUES (:s, :pid, :mt, :rt, :rid, :rno, :qty, :uid, :bqty, :ucost, :bal, :uid_u, :notes)");
        
        $insertStmt->execute([
            's'      => $shopId,
            'pid'    => $productId,
            'mt'     => $movementType,
            'rt'     => $referenceType,
            'rid'    => $referenceId,
            'rno'    => $referenceNo,
            'qty'    => $signedQty,
            'uid'    => $unitId,
            'bqty'   => $signedBaseQty,
            'ucost'  => $unitCost,
            'bal'    => $newStock,
            'uid_u'  => $userId,
            'notes'  => $notes
        ]);

        // Update product table cached stock
        $updateStmt = $pdo->prepare("UPDATE products SET current_stock = :stock, average_cost = :avg_cost, updated_at = NOW() WHERE id = :id");
        $updateStmt->execute([
            'stock'    => $newStock,
            'avg_cost' => $newAverageCost,
            'id'       => $productId
        ]);

        return $newStock;
    }

    /**
     * Get Low Stock Alerts
     */
    public static function getLowStockProducts(int $shopId = 1): array
    {
        $pdo = DB::pdo();
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name, b.name as brand_name, u.name as unit_name, u.symbol as unit_symbol 
                               FROM products p
                               JOIN categories c ON p.category_id = c.id
                               LEFT JOIN brands b ON p.brand_id = b.id
                               JOIN units u ON p.base_unit_id = u.id
                               WHERE p.shop_id = :s AND p.is_active = 1 AND p.current_stock <= p.min_stock
                               ORDER BY (p.current_stock - p.min_stock) ASC");
        $stmt->execute(['s' => $shopId]);
        return $stmt->fetchAll();
    }
}
