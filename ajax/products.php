<?php
/**
 * AJAX Controller: Product Operations
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();

$action = $_GET['action'] ?? '';
$shopId = Auth::shopId();

try {
    $pdo = DB::pdo();

    switch ($action) {
        case 'search':
            $q = cleanInput($_GET['q'] ?? '');
            $catId = (int)($_GET['category_id'] ?? 0);
            $brandId = (int)($_GET['brand_id'] ?? 0);

            $sql = "SELECT p.*, u.name as unit_name, u.symbol as unit_symbol,
                           c.name as category_name, b.name as brand_name
                    FROM products p
                    JOIN units u ON p.base_unit_id = u.id
                    JOIN categories c ON p.category_id = c.id
                    LEFT JOIN brands b ON p.brand_id = b.id
                    WHERE p.shop_id = :s AND p.is_active = 1";
            $params = ['s' => $shopId];

            if (!empty($q)) {
                $sql .= " AND (p.name LIKE :q_name OR p.sku LIKE :q_sku)";
                $params['q_name'] = "%{$q}%";
                $params['q_sku'] = "%{$q}%";
            }
            if ($catId > 0) {
                $sql .= " AND p.category_id = :cid";
                $params['cid'] = $catId;
            }
            if ($brandId > 0) {
                $sql .= " AND p.brand_id = :bid";
                $params['bid'] = $brandId;
            }

            $sql .= " ORDER BY p.name ASC LIMIT 30";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();

            jsonResponse(['success' => true, 'products' => $products]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
    }
} catch (\Throwable $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
