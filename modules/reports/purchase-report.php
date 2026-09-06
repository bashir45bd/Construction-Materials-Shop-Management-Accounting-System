<?php
/**
 * Material Procurement & Supplier Purchase Report
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('reports.purchase');

$shopId = Auth::shopId();
$pdo = DB::pdo();

$startDate = cleanInput($_GET['start_date'] ?? date('Y-01-01'));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d'));

$sql = "SELECT p.*, s.name as supplier_name, s.company_name
        FROM purchases p
        JOIN suppliers s ON p.supplier_id = s.id
        WHERE p.shop_id = :s AND p.purchase_date BETWEEN :start AND :end
        ORDER BY p.purchase_date DESC, p.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId, 'start' => $startDate, 'end' => $endDate]);
$purchases = $stmt->fetchAll();

$totalPurchases = array_sum(array_column($purchases, 'grand_total'));
$totalPaid = array_sum(array_column($purchases, 'paid_amount'));
$totalDue = array_sum(array_column($purchases, 'due_amount'));

$pageTitle = 'Procurement & Purchase Report (ক্রয় রিপোর্ট)';
$activeMenu = 'reports';
$activeSub = 'purchase-report';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-receipt"></i> Procurement & Purchase Report (ক্রয় রিপোর্ট)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Supplier procurement vouchers, discounts received, disbursements and payables.</p>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
      <i class="fa fa-print"></i> Print Report
    </button>
  </div>

  <form method="GET" style="display:flex;gap:0.75rem;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);flex-wrap:wrap;">
    <input type="date" name="start_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($startDate) ?>">
    <input type="date" name="end_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($endDate) ?>">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Filter</button>
  </form>

  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:1.5rem;">
    <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);">TOTAL PURCHASES</div>
      <div style="font-size:1.3rem;font-weight:800;color:var(--primary);"><?= formatMoney($totalPurchases) ?></div>
    </div>
    <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);">PAID OUTFLOW</div>
      <div style="font-size:1.3rem;font-weight:800;color:var(--success);"><?= formatMoney($totalPaid) ?></div>
    </div>
    <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);">SUPPLIER DUE CREATED</div>
      <div style="font-size:1.3rem;font-weight:800;color:var(--danger);"><?= formatMoney($totalDue) ?></div>
    </div>
  </div>

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
          <th>Paid Amount</th>
          <th>Due Balance</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($purchases)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">No purchases found in selected period.</td></tr>
        <?php else: ?>
          <?php foreach ($purchases as $p): ?>
            <tr>
              <td style="font-weight:700;color:var(--primary);"><?= e($p['purchase_number']) ?></td>
              <td><?= formatDate($p['purchase_date']) ?></td>
              <td><?= e($p['supplier_name']) ?> (<?= e($p['company_name'] ?: 'Vendor') ?>)</td>
              <td><?= formatMoney($p['subtotal']) ?></td>
              <td style="color:var(--danger);"><?= formatMoney($p['discount_amount']) ?></td>
              <td style="font-weight:800;"><?= formatMoney($p['grand_total']) ?></td>
              <td style="color:var(--success);font-weight:700;"><?= formatMoney($p['paid_amount']) ?></td>
              <td style="color:var(--danger);font-weight:700;"><?= formatMoney($p['due_amount']) ?></td>
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
