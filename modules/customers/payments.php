<?php
/**
 * Customer Due Payments & Collections Journal
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('customer.payment');

$shopId = Auth::shopId();
$pdo = DB::pdo();

$search = cleanInput($_GET['q'] ?? '');
$startDate = cleanInput($_GET['start_date'] ?? date('Y-m-01'));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d'));

$sql = "SELECT ft.*, c.name as customer_name, c.mobile as customer_mobile, u.full_name as created_by_name
        FROM financial_transactions ft
        LEFT JOIN customer_ledger cl ON ft.reference_no = cl.reference_no
        LEFT JOIN customers c ON cl.customer_id = c.id
        JOIN users u ON ft.created_by = u.id
        WHERE ft.shop_id = :s AND ft.category IN ('CUSTOMER_COLLECTION', 'SALE_PAYMENT') AND ft.transaction_date BETWEEN :start AND :end";
$params = ['s' => $shopId, 'start' => $startDate, 'end' => $endDate];

if (!empty($search)) {
    $sql .= " AND (ft.transaction_no LIKE :q OR c.name LIKE :q OR c.mobile LIKE :q)";
    $params['q'] = "%{$search}%";
}

$sql .= " ORDER BY ft.id DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$totalCollected = array_sum(array_column($payments, 'amount'));

$pageTitle = 'Due Collections Journal (বাকি আদায় খতিয়ান)';
$activeMenu = 'customers';
$activeSub = 'customer-payments';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-money-bill-wave"></i> Customer Due Collections Journal (বাকি আদায় খতিয়ান)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Audit log of all due collections and payments received from customers.</p>
    </div>
    <div style="background:var(--success-light);color:var(--success);padding:0.5rem 1rem;border-radius:var(--radius);font-weight:800;font-size:1.1rem;">
      Total Collected in Period: <?= formatMoney($totalCollected) ?>
    </div>
  </div>

  <form method="GET" style="display:flex;gap:0.75rem;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);flex-wrap:wrap;">
    <input type="text" name="q" class="form-control form-control-sm" style="max-width:240px;" placeholder="Search Voucher or Customer..." value="<?= e($search) ?>">
    <input type="date" name="start_date" class="form-control form-control-sm" style="max-width:150px;" value="<?= e($startDate) ?>">
    <input type="date" name="end_date" class="form-control form-control-sm" style="max-width:150px;" value="<?= e($endDate) ?>">
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa fa-filter"></i> Search</button>
    <a href="<?= BASE_URL ?>/modules/customers/payments.php" class="btn btn-secondary btn-sm">Reset</a>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Voucher No</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Account Type</th>
          <th>Category</th>
          <th>Collected Amount</th>
          <th>Collected By</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2.5rem;">No payment records in selected period.</td></tr>
        <?php else: ?>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td style="font-weight:700;color:var(--primary);"><?= e($p['transaction_no']) ?></td>
              <td><?= formatDate($p['transaction_date']) ?></td>
              <td style="font-weight:600;"><?= e($p['customer_name'] ?: 'Retail Customer') ?></td>
              <td><span class="badge badge-info"><?= strtoupper($p['account_type']) ?></span></td>
              <td><?= ucfirst(str_replace('_', ' ', strtolower($p['category']))) ?></td>
              <td style="font-weight:800;color:var(--success);font-size:0.95rem;"><?= formatMoney($p['amount']) ?></td>
              <td style="font-size:0.85rem;"><?= e($p['created_by_name']) ?></td>
              <td style="font-size:0.8rem;color:var(--text-secondary);"><?= e($p['notes'] ?: '-') ?></td>
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
