<?php
/**
 * Sales Memo History & Tracking
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('sales.view');

$shopId = Auth::shopId();
$pdo = DB::pdo();

// Search & Date Filters
$search = cleanInput($_GET['q'] ?? '');
$status = cleanInput($_GET['status'] ?? '');
$startDate = cleanInput($_GET['start_date'] ?? date('Y-m-01'));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d'));

$sql = "SELECT s.*, c.name as customer_name, c.mobile as customer_mobile, u.full_name as created_by_name
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        JOIN users u ON s.created_by = u.id
        WHERE s.shop_id = :s AND s.sale_date BETWEEN :start AND :end";
$params = ['s' => $shopId, 'start' => $startDate, 'end' => $endDate];

if (!empty($search)) {
    $sql .= " AND (s.memo_number LIKE :q OR c.name LIKE :q OR c.mobile LIKE :q)";
    $params['q'] = "%{$search}%";
}
if (!empty($status)) {
    $sql .= " AND s.payment_status = :stat";
    $params['stat'] = $status;
}

$sql .= " ORDER BY s.id DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$memos = $stmt->fetchAll();

$pageTitle = 'Sales Memo History (বিক্রয় মেমো খতিয়ান)';
$activeMenu = 'sales';
$activeSub = 'memo-history';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-file-invoice"></i> Sales Memo History</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Browse, search and reprint sales memos.</p>
    </div>
    <a href="<?= BASE_URL ?>/modules/sales/new-memo.php" class="btn btn-primary btn-sm">
      <i class="fa fa-plus"></i> New Memo
    </a>
  </div>

  <!-- Filter Bar -->
  <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);">
    <input type="text" name="q" class="form-control form-control-sm" style="max-width:240px;" placeholder="Search Memo / Customer..." value="<?= e($search) ?>">
    
    <select name="status" class="form-control form-control-sm" style="max-width:160px;">
      <option value="">All Statuses</option>
      <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
      <option value="partial" <?= $status === 'partial' ? 'selected' : '' ?>>Partial</option>
      <option value="due" <?= $status === 'due' ? 'selected' : '' ?>>Due</option>
      <option value="advance_settled" <?= $status === 'advance_settled' ? 'selected' : '' ?>>Advance Settled</option>
    </select>

    <input type="date" name="start_date" class="form-control form-control-sm" style="max-width:150px;" value="<?= e($startDate) ?>">
    <input type="date" name="end_date" class="form-control form-control-sm" style="max-width:150px;" value="<?= e($endDate) ?>">

    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa fa-filter"></i> Filter</button>
    <a href="<?= BASE_URL ?>/modules/sales/memo-history.php" class="btn btn-secondary btn-sm">Reset</a>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Memo No</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Subtotal</th>
          <th>Discount</th>
          <th>Grand Total</th>
          <th>Paid / Advance</th>
          <th>Due</th>
          <th>Status</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($memos)): ?>
          <tr><td colspan="10" style="text-align:center;color:var(--text-muted);padding:2.5rem;">No sales memos match the criteria.</td></tr>
        <?php else: ?>
          <?php foreach ($memos as $m): ?>
            <tr>
              <td style="font-weight:700;">
                <a href="<?= BASE_URL ?>/modules/sales/memo-print.php?id=<?= $m['id'] ?>" style="color:var(--primary);text-decoration:none;">
                  <?= e($m['memo_number']) ?>
                </a>
              </td>
              <td><?= formatDate($m['sale_date']) ?></td>
              <td>
                <div style="font-weight:600;"><?= e($m['customer_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($m['customer_mobile']) ?></div>
              </td>
              <td><?= formatMoney($m['subtotal']) ?></td>
              <td style="color:var(--danger);"><?= formatMoney($m['discount_amount']) ?></td>
              <td style="font-weight:800;"><?= formatMoney($m['grand_total']) ?></td>
              <td style="color:var(--success);font-weight:600;"><?= formatMoney($m['paid_amount'] + $m['advance_used']) ?></td>
              <td style="color:var(--danger);font-weight:700;"><?= formatMoney($m['due_amount']) ?></td>
              <td>
                <?php if ($m['payment_status'] === 'paid'): ?>
                  <span class="badge badge-success">Paid</span>
                <?php elseif ($m['payment_status'] === 'partial'): ?>
                  <span class="badge badge-warning">Partial</span>
                <?php elseif ($m['payment_status'] === 'advance_settled'): ?>
                  <span class="badge badge-info">Advance</span>
                <?php else: ?>
                  <span class="badge badge-danger">Due</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <a href="<?= BASE_URL ?>/modules/sales/memo-print.php?id=<?= $m['id'] ?>" class="btn btn-secondary btn-sm" title="Print Two-Copy Memo">
                  <i class="fa fa-print"></i>
                </a>
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
