<?php
/**
 * Warehouse Inventory Movement & Stock Valuation Report
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('reports.stock');

$shopId = Auth::shopId();
$pdo = DB::pdo();

$sql = "SELECT p.*, c.name as category_name, b.name as brand_name, u.symbol as unit_symbol,
               (p.current_stock * p.average_cost) as total_valuation,
               (p.current_stock * p.selling_price) as total_retail_value
        FROM products p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        JOIN units u ON p.base_unit_id = u.id
        WHERE p.shop_id = :s
        ORDER BY p.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId]);
$products = $stmt->fetchAll();

$totalCostValue = array_sum(array_column($products, 'total_valuation'));
$totalRetailValue = array_sum(array_column($products, 'total_retail_value'));
$potentialProfit = $totalRetailValue - $totalCostValue;

$pageTitle = 'Inventory Valuation & Stock Report (স্টক রিপোর্ট)';
$activeMenu = 'reports';
$activeSub = 'stock-report';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-boxes-stacked"></i> Inventory Valuation & Stock Report (স্টক রিপোর্ট)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Summary of current warehouse inventory levels, weighted average purchase costs, and potential profit margins.</p>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
      <i class="fa fa-print"></i> Print Report
    </button>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:1rem;margin-bottom:1.5rem;">
    <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);">TOTAL STOCK COST VALUATION</div>
      <div style="font-size:1.3rem;font-weight:800;color:var(--primary);"><?= formatMoney($totalCostValue) ?></div>
    </div>
    <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);">ESTIMATED RETAIL VALUE</div>
      <div style="font-size:1.3rem;font-weight:800;color:var(--success);"><?= formatMoney($totalRetailValue) ?></div>
    </div>
    <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);">POTENTIAL GROSS MARGIN</div>
      <div style="font-size:1.3rem;font-weight:800;color:var(--success);"><?= formatMoney($potentialProfit) ?></div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>SKU</th>
          <th>Product Name</th>
          <th>Category</th>
          <th>Current Stock</th>
          <th>Avg Cost</th>
          <th>Selling Rate</th>
          <th>Cost Valuation</th>
          <th>Retail Valuation</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td style="font-weight:700;color:var(--text-muted);"><?= e($p['sku']) ?></td>
            <td style="font-weight:700;"><?= e($p['name']) ?></td>
            <td><?= e($p['category_name']) ?></td>
            <td style="font-weight:800;"><?= formatQuantity($p['current_stock']) ?> <?= e($p['unit_symbol']) ?></td>
            <td><?= formatMoney($p['average_cost']) ?></td>
            <td style="font-weight:600;"><?= formatMoney($p['selling_price']) ?></td>
            <td style="font-weight:700;color:var(--primary);"><?= formatMoney($p['total_valuation']) ?></td>
            <td style="font-weight:700;color:var(--success);"><?= formatMoney($p['total_retail_value']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
