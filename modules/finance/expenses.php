<?php
/**
 * Overhead Expenses Management
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\FinanceService;

Auth::requireLogin();
Auth::requirePermission('finance.expense');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

// Handle Expense Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $catId   = (int)($_POST['category_id'] ?? 0);
    $amount  = (float)($_POST['amount'] ?? 0.0);
    $accType = (string)($_POST['account_type'] ?? 'cash');
    $accId   = (int)($_POST['account_id'] ?? 1);
    $payee   = cleanInput($_POST['payee_name'] ?? '');
    $notes   = cleanInput($_POST['notes'] ?? '');
    $date    = cleanInput($_POST['expense_date'] ?? date('Y-m-d'));

    if ($catId > 0 && $amount > 0) {
        FinanceService::createExpense(
            $catId,
            $amount,
            $accType,
            $accId,
            $payee,
            $notes,
            $userId,
            $date,
            $shopId
        );
        flash('success', "Expense voucher of " . formatMoney($amount) . " logged.");
        redirect(BASE_URL . '/modules/finance/expenses.php');
    }
}

$categories = $pdo->query("SELECT id, name FROM expense_categories WHERE shop_id = {$shopId} AND is_active = 1 ORDER BY name ASC")->fetchAll();

$sql = "SELECT e.*, ec.name as category_name, u.full_name as created_by_name
        FROM expenses e
        JOIN expense_categories ec ON e.category_id = ec.id
        JOIN users u ON e.created_by = u.id
        WHERE e.shop_id = :s
        ORDER BY e.id DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId]);
$expenses = $stmt->fetchAll();

$totalExpenses = array_sum(array_column($expenses, 'amount'));

$pageTitle = 'Expenses (খরচ)';
$activeMenu = 'finance';
$activeSub = 'expenses';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-arrow-trend-down"></i> Operational Expenses (দোকানের খরচ)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Track rent, loading/unloading labor, truck freight, staff salary and utilities.</p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center;">
      <div style="font-size:1.05rem;font-weight:800;color:var(--danger);">
        Total Expenses: <?= formatMoney($totalExpenses) ?>
      </div>
      <button type="button" class="btn btn-primary btn-sm" onclick="openModal('expense-modal')">
        <i class="fa fa-plus"></i> + Add Expense
      </button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Voucher No</th>
          <th>Date</th>
          <th>Expense Category</th>
          <th>Amount</th>
          <th>Paid Via</th>
          <th>Payee Name</th>
          <th>Audited By</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($expenses)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">No expense vouchers logged.</td></tr>
        <?php else: ?>
          <?php foreach ($expenses as $e): ?>
            <tr>
              <td style="font-weight:700;color:var(--primary);"><?= e($e['expense_no']) ?></td>
              <td><?= formatDate($e['expense_date']) ?></td>
              <td style="font-weight:600;"><?= e($e['category_name']) ?></td>
              <td style="font-weight:800;color:var(--danger);"><?= formatMoney($e['amount']) ?></td>
              <td><span class="badge badge-info"><?= strtoupper($e['account_type']) ?></span></td>
              <td><?= e($e['payee_name'] ?: '-') ?></td>
              <td style="font-size:0.85rem;"><?= e($e['created_by_name']) ?></td>
              <td style="font-size:0.8rem;color:var(--text-secondary);"><?= e($e['notes'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Expense Modal -->
<div class="modal-backdrop" id="expense-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;"><i class="fa fa-arrow-trend-down"></i> Record Expense Voucher</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('expense-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <div class="form-group">
          <label class="form-label">Expense Category *</label>
          <select name="category_id" class="form-control" required>
            <option value="">-- Choose Category --</option>
            <?php foreach ($categories as $ec): ?>
              <option value="<?= $ec['id'] ?>"><?= e($ec['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Amount (টাকার পরিমাণ) *</label>
            <input type="number" step="any" min="1" name="amount" class="form-control" placeholder="0.00" required>
          </div>
          <div class="form-group">
            <label class="form-label">Expense Date *</label>
            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Payee / Recipient Name</label>
          <input type="text" name="payee_name" class="form-control" placeholder="e.g. Labor Sardar, Truck Driver, Landlord">
        </div>
        <div class="form-group">
          <label class="form-label">Remarks / Bill Reference</label>
          <input type="text" name="notes" class="form-control" placeholder="Optional details">
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('expense-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Expense</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
