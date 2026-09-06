<?php
/**
 * Stock Movement Audit Ledger
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

$prodFilter = (int)($_GET['product_id'] ?? 0);
$typeFilter = cleanInput($_GET['type'] ?? '');

$sql = "SELECT sm.*, p.name as product_name, p.sku, u.symbol as unit_symbol, usr.full_name as user_name
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        JOIN units u ON sm.unit_id = u.id
        JOIN users usr ON sm.user_id = usr.id
        WHERE sm.shop_id = :s";
$params = ['s' => $shopId];

if ($prodFilter > 0) {
    $sql .= " AND sm.product_id = :pid";
    $params['pid'] = $prodFilter;
}
if (!empty($typeFilter)) {
    $sql .= " AND sm.movement_type = :mtype";
    $params['mtype'] = $typeFilter;
}

$sql .= " ORDER BY sm.id DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movements = $stmt->fetchAll();

$products = $pdo->query("SELECT id, name FROM products WHERE shop_id = {$shopId} ORDER BY name ASC")->fetchAll();

$pageTitle = 'Stock Movements Ledger (স্টক মুভমেন্ট লগ)';
$activeMenu = 'inventory';
$activeSub = 'stock-movement';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-arrow-right-arrow-left"></i> Stock Movement Audit Ledger</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Chronological, immutable audit trail of every warehouse inventory addition or deduction.</p>
    </div>
  </div>

  <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);">
    <select name="product_id" class="form-control form-control-sm" style="max-width:240px;">
      <option value="0">All Products</option>
      <?php foreach ($products as $pr): ?>
        <option value="<?= $pr['id'] ?>" <?= $prodFilter == $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="type" class="form-control form-control-sm" style="max-width:180px;">
      <option value="">All Movement Types</option>
      <option value="SALE" <?= $typeFilter === 'SALE' ? 'selected' : '' ?>>Sale (বিক্রয়)</option>
      <option value="PURCHASE" <?= $typeFilter === 'PURCHASE' ? 'selected' : '' ?>>Purchase (ক্রয়)</option>
      <option value="SALE_RETURN" <?= $typeFilter === 'SALE_RETURN' ? 'selected' : '' ?>>Sales Return</option>
      <option value="PURCHASE_RETURN" <?= $typeFilter === 'PURCHASE_RETURN' ? 'selected' : '' ?>>Purchase Return</option>
      <option value="ADJUSTMENT_IN" <?= $typeFilter === 'ADJUSTMENT_IN' ? 'selected' : '' ?>>Adjustment In</option>
      <option value="ADJUSTMENT_OUT" <?= $typeFilter === 'ADJUSTMENT_OUT' ? 'selected' : '' ?>>Adjustment Out</option>
      <option value="OPENING_STOCK" <?= $typeFilter === 'OPENING_STOCK' ? 'selected' : '' ?>>Opening Stock</option>
    </select>

    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa fa-filter"></i> Filter</button>
    <a href="<?= BASE_URL ?>/modules/inventory/stock-movement.php" class="btn btn-secondary btn-sm">Reset</a>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Date & Time</th>
          <th>Product</th>
          <th>Type</th>
          <th>Reference No</th>
          <th>Quantity Delta</th>
          <th>Cost Rate</th>
          <th>Balance After</th>
          <th>User</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($movements)): ?>
          <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:2.5rem;">No stock movements logged.</td></tr>
        <?php else: ?>
          <?php foreach ($movements as $m): 
            $isPositive = (float)$m['base_quantity'] > 0;
          ?>
            <tr>
              <td style="font-size:0.8rem;color:var(--text-muted);"><?= date('d-m-Y H:i', strtotime($m['created_at'])) ?></td>
              <td style="font-weight:700;"><?= e($m['product_name']) ?></td>
              <td>
                <span class="badge <?= $isPositive ? 'badge-success' : 'badge-danger' ?>">
                  <?= e($m['movement_type']) ?>
                </span>
              </td>
              <td style="font-weight:600;"><?= e($m['reference_no']) ?></td>
              <td style="font-weight:800;color:<?= $isPositive ? 'var(--success)' : 'var(--danger)' ?>;">
                <?= $isPositive ? '+' : '' ?><?= formatQuantity($m['quantity']) ?> <?= e($m['unit_symbol']) ?>
              </td>
              <td><?= formatMoney($m['unit_cost']) ?></td>
              <td style="font-weight:700;"><?= formatQuantity($m['balance_after']) ?> <?= e($m['unit_symbol']) ?></td>
              <td style="font-size:0.8rem;"><?= e($m['user_name']) ?></td>
              <td style="font-size:0.8rem;color:var(--text-secondary);"><?= e($m['notes'] ?: '-') ?></td>
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
