<?php
/**
 * Purchase Procurement History
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('purchase.view');

$shopId = Auth::shopId();
$pdo = DB::pdo();

$search = cleanInput($_GET['q'] ?? '');
$sql = "SELECT p.*, s.name as supplier_name, s.company_name, u.full_name as created_by_name
        FROM purchases p
        JOIN suppliers s ON p.supplier_id = s.id
        JOIN users u ON p.created_by = u.id
        WHERE p.shop_id = :s";
$params = ['s' => $shopId];

if (!empty($search)) {
    $sql .= " AND (p.purchase_number LIKE :q OR s.name LIKE :q OR s.company_name LIKE :q)";
    $params['q'] = "%{$search}%";
}

$sql .= " ORDER BY p.id DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$purchases = $stmt->fetchAll();

$pageTitle = 'Purchase History (ক্রয় খতিয়ান)';
$activeMenu = 'purchases';
$activeSub = 'purchase-history';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-receipt"></i> Purchase History (ক্রয় খতিয়ান)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Browse all material procurement vouchers.</p>
    </div>
    <a href="<?= BASE_URL ?>/modules/purchases/new-purchase.php" class="btn btn-primary btn-sm">
      <i class="fa fa-plus"></i> New Purchase
    </a>
  </div>

  <form method="GET" style="display:flex;gap:0.75rem;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);">
    <input type="text" name="q" class="form-control form-control-sm" style="max-width:280px;" placeholder="Search Purchase No or Supplier..." value="<?= e($search) ?>">
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa fa-filter"></i> Search</button>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Purchase No</th>
          <th>Date</th>
          <th>Supplier</th>
          <th>Subtotal</th>
          <th>Discount</th>
          <th>Grand Total</th>
          <th>Paid</th>
          <th>Due</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($purchases)): ?>
          <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:2rem;">No purchases found.</td></tr>
        <?php else: ?>
          <?php foreach ($purchases as $p): ?>
            <tr>
              <td style="font-weight:700;color:var(--primary);"><?= e($p['purchase_number']) ?></td>
              <td><?= formatDate($p['purchase_date']) ?></td>
              <td>
                <div style="font-weight:600;"><?= e($p['supplier_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($p['company_name'] ?? '') ?></div>
              </td>
              <td><?= formatMoney($p['subtotal']) ?></td>
              <td style="color:var(--danger);"><?= formatMoney($p['discount_amount']) ?></td>
              <td style="font-weight:800;"><?= formatMoney($p['grand_total']) ?></td>
              <td style="color:var(--success);"><?= formatMoney($p['paid_amount']) ?></td>
              <td style="color:var(--danger);font-weight:700;"><?= formatMoney($p['due_amount']) ?></td>
              <td>
                <span class="badge <?= $p['payment_status'] === 'paid' ? 'badge-success' : 'badge-danger' ?>">
                  <?= ucfirst($p['payment_status']) ?>
                </span>
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
