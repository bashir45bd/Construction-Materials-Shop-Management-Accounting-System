<?php
/**
 * Physical Cash Drawer & Transactions Ledger
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('finance.view');

$shopId = Auth::shopId();
$pdo = DB::pdo();

// Cash Account Balance
$stmtAcc = $pdo->prepare("SELECT * FROM cash_accounts WHERE shop_id = :s AND is_default = 1 LIMIT 1");
$stmtAcc->execute(['s' => $shopId]);
$cashAccount = $stmtAcc->fetch();

// Filter
$startDate = cleanInput($_GET['start_date'] ?? date('Y-m-01'));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d'));

$sql = "SELECT ft.*, u.full_name as user_name 
        FROM financial_transactions ft
        JOIN users u ON ft.created_by = u.id
        WHERE ft.shop_id = :s AND ft.account_type = 'cash' AND ft.transaction_date BETWEEN :start AND :end
        ORDER BY ft.id DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId, 'start' => $startDate, 'end' => $endDate]);
$transactions = $stmt->fetchAll();

$pageTitle = 'Cash Drawer Ledger (ক্যাশ বাক্স)';
$activeMenu = 'finance';
$activeSub = 'cash';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-cash-register"></i> Physical Cash Drawer (ক্যাশ বাক্স)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Real-time cash in hand and daily cash reconciliation journal.</p>
    </div>
    <div style="background:var(--success-light);color:var(--success);padding:0.75rem 1.25rem;border-radius:var(--radius);text-align:right;">
      <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;">Current Cash in Hand</div>
      <div style="font-size:1.4rem;font-weight:800;"><?= formatMoney($cashAccount['current_balance'] ?? 0) ?></div>
    </div>
  </div>

  <form method="GET" style="display:flex;gap:0.75rem;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);">
    <input type="date" name="start_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($startDate) ?>">
    <input type="date" name="end_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($endDate) ?>">
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa fa-filter"></i> Filter</button>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Voucher No</th>
          <th>Flow Nature</th>
          <th>Category</th>
          <th>Description</th>
          <th style="text-align:right;">Inflow (+)</th>
          <th style="text-align:right;">Outflow (-)</th>
          <th style="text-align:right;">Cash Balance After</th>
          <th>Audited By</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($transactions)): ?>
          <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:2rem;">No cash transactions recorded in this period.</td></tr>
        <?php else: ?>
          <?php foreach ($transactions as $t): 
            $isInflow = ($t['transaction_nature'] === 'INFLOW');
          ?>
            <tr>
              <td><?= formatDate($t['transaction_date']) ?></td>
              <td style="font-weight:700;color:var(--primary);"><?= e($t['transaction_no']) ?></td>
              <td>
                <span class="badge <?= $isInflow ? 'badge-success' : 'badge-danger' ?>">
                  <?= $t['transaction_nature'] ?>
                </span>
              </td>
              <td><?= ucfirst(str_replace('_', ' ', strtolower($t['category']))) ?></td>
              <td style="font-size:0.85rem;"><?= e($t['notes'] ?: '-') ?></td>
              <td style="text-align:right;font-weight:700;color:var(--success);">
                <?= $isInflow ? '+' . formatMoney($t['amount']) : '-' ?>
              </td>
              <td style="text-align:right;font-weight:700;color:var(--danger);">
                <?= !$isInflow ? '-' . formatMoney($t['amount']) : '-' ?>
              </td>
              <td style="text-align:right;font-weight:800;font-size:0.95rem;">
                <?= formatMoney($t['balance_after']) ?>
              </td>
              <td style="font-size:0.8rem;"><?= e($t['user_name']) ?></td>
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
