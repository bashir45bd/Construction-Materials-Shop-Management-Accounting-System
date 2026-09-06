<?php
/**
 * Supplier Accounts Payable Ledger (মহাজন খতিয়ান)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('supplier.view');

$shopId = Auth::shopId();
$pdo = DB::pdo();
$supplierId = (int)($_GET['supplier_id'] ?? 0);

$stmtSupp = $pdo->prepare("SELECT * FROM suppliers WHERE id = :id AND shop_id = :s");
$stmtSupp->execute(['id' => $supplierId, 's' => $shopId]);
$supplier = $stmtSupp->fetch();

if (!$supplier) {
    flash('error', 'Please select a supplier from the list to inspect their payable ledger.');
    redirect(BASE_URL . '/modules/suppliers/list.php');
}

$startDate = cleanInput($_GET['start_date'] ?? date('Y-01-01'));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d'));

$sql = "SELECT sl.*, u.full_name as user_name 
        FROM supplier_ledger sl
        JOIN users u ON sl.user_id = u.id
        WHERE sl.shop_id = :s AND sl.supplier_id = :sid AND sl.transaction_date BETWEEN :start AND :end
        ORDER BY sl.transaction_date ASC, sl.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId, 'sid' => $supplierId, 'start' => $startDate, 'end' => $endDate]);
$transactions = $stmt->fetchAll();

$pageTitle = "Supplier Ledger: {$supplier['name']}";
$activeMenu = 'purchases';
$activeSub = 'suppliers';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <div style="font-size:0.85rem;color:var(--text-muted);font-weight:700;">SUPPLIER ACCOUNTS PAYABLE LEDGER (মহাজন খতিয়ান)</div>
      <h1 class="card-title" style="font-size:1.4rem;margin-top:2px;">
        <?= e($supplier['name']) ?> (<?= e($supplier['company_name'] ?: 'Vendor') ?>)
      </h1>
      <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px;">
        <i class="fa fa-phone"></i> <?= e($supplier['mobile']) ?> | <i class="fa fa-location-dot"></i> <?= e($supplier['address'] ?: '-') ?>
      </div>
    </div>
    <div style="background:var(--danger-light);color:var(--danger);padding:0.6rem 1.2rem;border-radius:var(--radius);text-align:right;">
      <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Total Payable Due (পাওনা বাকি)</div>
      <div style="font-size:1.3rem;font-weight:800;"><?= formatMoney($supplier['current_due']) ?></div>
    </div>
  </div>

  <form method="GET" style="display:flex;gap:0.75rem;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);flex-wrap:wrap;">
    <input type="hidden" name="supplier_id" value="<?= $supplierId ?>">
    <input type="date" name="start_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($startDate) ?>">
    <input type="date" name="end_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($endDate) ?>">
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa fa-filter"></i> Filter</button>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()"><i class="fa fa-print"></i> Print</button>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Reference No</th>
          <th>Transaction Description</th>
          <th style="text-align:right;">Debit (পরিশোধ/হ্রাস)</th>
          <th style="text-align:right;">Credit (ক্রয়/পাওনা বৃদ্ধি)</th>
          <th style="text-align:right;">Payable Balance</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:2.5rem;">No transactions recorded for this supplier.</td></tr>
        <?php else: ?>
          <?php foreach ($transactions as $t): ?>
            <tr>
              <td><?= formatDate($t['transaction_date']) ?></td>
              <td style="font-weight:700;color:var(--primary);"><?= e($t['reference_no']) ?></td>
              <td><?= e($t['description']) ?></td>
              <td style="text-align:right;font-weight:700;color:<?= (float)$t['debit'] > 0 ? 'var(--success)' : 'inherit' ?>;">
                <?= (float)$t['debit'] > 0 ? formatMoney($t['debit']) : '-' ?>
              </td>
              <td style="text-align:right;font-weight:700;color:<?= (float)$t['credit'] > 0 ? 'var(--danger)' : 'inherit' ?>;">
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
