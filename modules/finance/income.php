<?php
/**
 * Non-Sales Operational Incomes
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\FinanceService;

Auth::requireLogin();
Auth::requirePermission('finance.income');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

// Handle Income Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $catId   = (int)($_POST['category_id'] ?? 0);
    $amount  = (float)($_POST['amount'] ?? 0.0);
    $payer   = cleanInput($_POST['payer_name'] ?? '');
    $notes   = cleanInput($_POST['notes'] ?? '');
    $date    = cleanInput($_POST['income_date'] ?? date('Y-m-d'));

    if ($catId > 0 && $amount > 0) {
        FinanceService::createIncome(
            $catId,
            $amount,
            'cash',
            1,
            $payer,
            $notes,
            $userId,
            $date,
            $shopId
        );
        flash('success', "Income voucher of " . formatMoney($amount) . " logged.");
        redirect(BASE_URL . '/modules/finance/income.php');
    }
}

$categories = $pdo->query("SELECT id, name FROM income_categories WHERE shop_id = {$shopId} AND is_active = 1 ORDER BY name ASC")->fetchAll();

$sql = "SELECT i.*, ic.name as category_name, u.full_name as created_by_name
        FROM incomes i
        JOIN income_categories ic ON i.category_id = ic.id
        JOIN users u ON i.created_by = u.id
        WHERE i.shop_id = :s
        ORDER BY i.id DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId]);
$incomes = $stmt->fetchAll();

$totalIncome = array_sum(array_column($incomes, 'amount'));

$pageTitle = 'Other Incomes (অন্যান্য আয়)';
$activeMenu = 'finance';
$activeSub = 'income';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-arrow-trend-up"></i> Other Incomes (অন্যান্য আয়)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Record non-material sales revenue such as empty cement sacks, rod scrap sales, and commission.</p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center;">
      <div style="font-size:1.05rem;font-weight:800;color:var(--success);">
        Total Income: <?= formatMoney($totalIncome) ?>
      </div>
      <button type="button" class="btn btn-primary btn-sm" onclick="openModal('income-modal')">
        <i class="fa fa-plus"></i> + Add Income
      </button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Voucher No</th>
          <th>Date</th>
          <th>Income Category</th>
          <th>Amount</th>
          <th>Payer Name</th>
          <th>Audited By</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($incomes)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">No other income vouchers logged.</td></tr>
        <?php else: ?>
          <?php foreach ($incomes as $i): ?>
            <tr>
              <td style="font-weight:700;color:var(--primary);"><?= e($i['income_no']) ?></td>
              <td><?= formatDate($i['income_date']) ?></td>
              <td style="font-weight:600;"><?= e($i['category_name']) ?></td>
              <td style="font-weight:800;color:var(--success);"><?= formatMoney($i['amount']) ?></td>
              <td><?= e($i['payer_name'] ?: '-') ?></td>
              <td style="font-size:0.85rem;"><?= e($i['created_by_name']) ?></td>
              <td style="font-size:0.8rem;color:var(--text-secondary);"><?= e($i['notes'] ?: '-') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Income Modal -->
<div class="modal-backdrop" id="income-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;"><i class="fa fa-arrow-trend-up"></i> Record Income Voucher</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('income-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <div class="form-group">
          <label class="form-label">Income Category *</label>
          <select name="category_id" class="form-control" required>
            <option value="">-- Choose Category --</option>
            <?php foreach ($categories as $ic): ?>
              <option value="<?= $ic['id'] ?>"><?= e($ic['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Amount *</label>
            <input type="number" step="any" min="1" name="amount" class="form-control" placeholder="0.00" required>
          </div>
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="income_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Payer / Source Name</label>
          <input type="text" name="payer_name" class="form-control" placeholder="e.g. Empty bag buyer, scrap dealer">
        </div>
        <div class="form-group">
          <label class="form-label">Remarks</label>
          <input type="text" name="notes" class="form-control" placeholder="Optional remarks">
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('income-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Income</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
