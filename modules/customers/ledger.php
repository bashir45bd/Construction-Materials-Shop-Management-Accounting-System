<?php
/**
 * Customer Detailed Transaction Ledger (খতিয়ান)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('customer.view');

$shopId = Auth::shopId();
$customerId = (int)($_GET['customer_id'] ?? 0);
$pdo = DB::pdo();

// Fetch Customer
$stmtCust = $pdo->prepare("SELECT * FROM customers WHERE id = :id AND shop_id = :s");
$stmtCust->execute(['id' => $customerId, 's' => $shopId]);
$customer = $stmtCust->fetch();

if (!$customer) {
    flash('error', 'Please select a customer to view their ledger statement.');
    redirect(BASE_URL . '/modules/customers/list.php');
}

// Date Filter
$startDate = cleanInput($_GET['start_date'] ?? date('Y-01-01'));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d'));

$sql = "SELECT cl.*, u.full_name as user_name 
        FROM customer_ledger cl
        JOIN users u ON cl.user_id = u.id
        WHERE cl.shop_id = :s AND cl.customer_id = :cid AND cl.transaction_date BETWEEN :start AND :end
        ORDER BY cl.transaction_date ASC, cl.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId, 'cid' => $customerId, 'start' => $startDate, 'end' => $endDate]);
$transactions = $stmt->fetchAll();

$pageTitle = "Ledger: {$customer['name']}";
$activeMenu = 'customers';
$activeSub = 'customer-ledger';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700;">CUSTOMER ACCOUNT STATEMENT (খতিয়ান)</div>
      <h1 class="card-title" style="font-size:1.4rem;margin-top:2px;">
        <?= e($customer['name']) ?> <small style="font-size:0.9rem;font-weight:500;color:var(--text-secondary);">(<?= e($customer['customer_code']) ?>)</small>
      </h1>
      <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px;">
        <i class="fa fa-phone"></i> <?= e($customer['mobile']) ?> | <i class="fa fa-location-dot"></i> <?= e($customer['address'] ?: 'Habiganj') ?>
      </div>
    </div>

    <!-- Balance Badges -->
    <div style="display:flex;gap:1rem;align-items:center;">
      <div style="background:var(--danger-light);color:var(--danger);padding:0.6rem 1.2rem;border-radius:var(--radius);text-align:right;">
        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Current Total Due (বাকি)</div>
        <div style="font-size:1.3rem;font-weight:800;"><?= formatMoney($customer['current_due']) ?></div>
      </div>
      <div style="background:var(--success-light);color:var(--success);padding:0.6rem 1.2rem;border-radius:var(--radius);text-align:right;">
        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Advance Deposit (জমা)</div>
        <div style="font-size:1.3rem;font-weight:800;"><?= formatMoney($customer['current_advance']) ?></div>
      </div>
    </div>
  </div>

  <!-- Filter -->
  <form method="GET" style="display:flex;gap:0.75rem;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);flex-wrap:wrap;">
    <input type="hidden" name="customer_id" value="<?= $customerId ?>">
    <input type="date" name="start_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($startDate) ?>">
    <input type="date" name="end_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($endDate) ?>">
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa fa-filter"></i> Filter Period</button>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="fa fa-print"></i> Print Statement</button>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Reference No</th>
          <th>Transaction Description</th>
          <th style="text-align:right;">Debit (বিক্রয়/বৃদ্ধি)</th>
          <th style="text-align:right;">Credit (আদায়/হ্রাস)</th>
          <th style="text-align:right;">Cumulative Due Balance</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2.5rem;">No transactions recorded for this customer in selected period.</td></tr>
        <?php else: ?>
          <?php foreach ($transactions as $t): ?>
            <tr>
              <td><?= formatDate($t['transaction_date']) ?></td>
              <td style="font-weight:700;color:var(--primary);"><?= e($t['reference_no']) ?></td>
              <td><?= e($t['description']) ?></td>
              <td style="text-align:right;font-weight:700;color:<?= (float)$t['debit'] > 0 ? 'var(--danger)' : 'inherit' ?>;">
                <?= (float)$t['debit'] > 0 ? formatMoney($t['debit']) : '-' ?>
              </td>
              <td style="text-align:right;font-weight:700;color:<?= (float)$t['credit'] > 0 ? 'var(--success)' : 'inherit' ?>;">
                <?= (float)$t['credit'] > 0 ? formatMoney($t['credit']) : '-' ?>
              </td>
              <td style="text-align:right;font-weight:800;font-size:0.95rem;">
                <?= formatMoney($t['running_balance']) ?>
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
