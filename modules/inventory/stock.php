<?php
/**
 * Live Stock Valuation & Warehouse Overview
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('inventory.view');

$shopId = Auth::shopId();
$pdo = DB::pdo();

$search = cleanInput($_GET['q'] ?? '');
$catFilter = (int)($_GET['category_id'] ?? 0);

$sql = "SELECT p.*, c.name as category_name, b.name as brand_name, u.symbol as unit_symbol,
               (p.current_stock * p.average_cost) as total_stock_value
        FROM products p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        JOIN units u ON p.base_unit_id = u.id
        WHERE p.shop_id = :s";
$params = ['s' => $shopId];

if (!empty($search)) {
    $sql .= " AND (p.name LIKE :q OR p.sku LIKE :q)";
    $params['q'] = "%{$search}%";
}
if ($catFilter > 0) {
    $sql .= " AND p.category_id = :cid";
    $params['cid'] = $catFilter;
}

$sql .= " ORDER BY p.current_stock ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT id, name FROM categories WHERE shop_id = {$shopId} AND is_active = 1 ORDER BY name ASC")->fetchAll();

$totalWarehouseValue = array_sum(array_column($products, 'total_stock_value'));

$pageTitle = 'Stock & Valuation (গুদাম মজুদ ও মূল্য)';
$activeMenu = 'inventory';
$activeSub = 'stock';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-warehouse"></i> Warehouse Stock & Valuation</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Real-time inventory levels, average costs, and perpetual valuation.</p>
    </div>
    <div style="background:var(--primary-light);color:var(--primary);padding:0.5rem 1rem;border-radius:var(--radius);font-weight:800;font-size:1.05rem;">
      Total Stock Value: <?= formatMoney($totalWarehouseValue) ?>
    </div>
  </div>

  <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);">
    <input type="text" name="q" class="form-control form-control-sm" style="max-width:260px;" placeholder="Search Product..." value="<?= e($search) ?>">
    <select name="category_id" class="form-control form-control-sm" style="max-width:180px;">
      <option value="0">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa fa-filter"></i> Search</button>
    <a href="<?= BASE_URL ?>/modules/inventory/stock.php" class="btn btn-secondary btn-sm">Reset</a>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>SKU</th>
          <th>Product Name</th>
          <th>Category</th>
          <th>Unit</th>
          <th>Current Stock</th>
          <th>Average Cost</th>
          <th>Total Valuation</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">No items in stock.</td></tr>
        <?php else: ?>
          <?php foreach ($products as $p): ?>
            <tr>
              <td style="font-weight:700;color:var(--text-muted);"><?= e($p['sku']) ?></td>
              <td style="font-weight:700;"><?= e($p['name']) ?></td>
              <td><?= e($p['category_name']) ?></td>
              <td><?= e($p['unit_symbol']) ?></td>
              <td style="font-weight:800;font-size:1rem;"><?= formatQuantity($p['current_stock']) ?></td>
              <td><?= formatMoney($p['average_cost']) ?></td>
              <td style="font-weight:700;color:var(--primary);"><?= formatMoney($p['total_stock_value']) ?></td>
              <td>
                <?php if ((float)$p['current_stock'] <= (float)$p['min_stock']): ?>
                  <span class="badge badge-danger">Low Stock</span>
                <?php else: ?>
                  <span class="badge badge-success">Sufficient</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
