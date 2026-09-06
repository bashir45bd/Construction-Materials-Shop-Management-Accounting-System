<?php
/**
 * AJAX Controller: Global Live Search
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();

$shopId = Auth::shopId();
$pdo = DB::pdo();
$q = cleanInput($_GET['q'] ?? '');

if (strlen($q) < 2) {
    jsonResponse(['success' => true, 'results' => []]);
}

$results = [
    'customers' => [],
    'products'  => [],
    'memos'     => [],
    'suppliers' => []
];

try {
    $term = "%{$q}%";

    // 1. Customers
    $stmtC = $pdo->prepare("SELECT id, customer_code, name, mobile, address, current_due, current_advance 
                            FROM customers 
                            WHERE shop_id = :s AND (name LIKE :q OR mobile LIKE :q OR customer_code LIKE :q)
                            ORDER BY name ASC LIMIT 5");
    $stmtC->execute(['s' => $shopId, 'q' => $term]);
    $results['customers'] = $stmtC->fetchAll();

    // 2. Products
    $stmtP = $pdo->prepare("SELECT p.id, p.sku, p.name, p.selling_price, p.current_stock, u.symbol as unit_symbol
                            FROM products p
                            JOIN units u ON p.base_unit_id = u.id
                            WHERE p.shop_id = :s AND (p.name LIKE :q OR p.sku LIKE :q)
                            ORDER BY p.name ASC LIMIT 5");
    $stmtP->execute(['s' => $shopId, 'q' => $term]);
    $results['products'] = $stmtP->fetchAll();

    // 3. Sales Memos
    $stmtM = $pdo->prepare("SELECT s.id, s.memo_number, s.grand_total, s.sale_date, s.payment_status, c.name as customer_name
                            FROM sales s
                            JOIN customers c ON s.customer_id = c.id
                            WHERE s.shop_id = :s AND s.memo_number LIKE :q
                            ORDER BY s.id DESC LIMIT 5");
    $stmtM->execute(['s' => $shopId, 'q' => $term]);
    $results['memos'] = $stmtM->fetchAll();

    // 4. Suppliers
    $stmtS = $pdo->prepare("SELECT id, supplier_code, name, company_name, mobile, current_due 
                            FROM suppliers 
                            WHERE shop_id = :s AND (name LIKE :q OR company_name LIKE :q OR mobile LIKE :q)
                            ORDER BY name ASC LIMIT 5");
    $stmtS->execute(['s' => $shopId, 'q' => $term]);
    $results['suppliers'] = $stmtS->fetchAll();

    jsonResponse(['success' => true, 'results' => $results]);
} catch (\Throwable $e) {
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
