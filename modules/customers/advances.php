<?php
/**
 * Customer Advances & Unallocated Deposits Log
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\CustomerAdvanceService;

Auth::requireLogin();
Auth::requirePermission('customer.advance');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

// Handle New Advance Intake
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $amount     = (float)($_POST['amount'] ?? 0.0);
    $method     = cleanInput($_POST['payment_method'] ?? 'Cash');
    $notes      = cleanInput($_POST['notes'] ?? '');
    $date       = cleanInput($_POST['receipt_date'] ?? date('Y-m-d'));

    if ($customerId > 0 && $amount > 0) {
        CustomerAdvanceService::receiveAdvance(
            $customerId,
            $amount,
            $method,
            'cash',
            1,
            $notes,
            $userId,
            $date,
            $shopId
        );
        flash('success', "Customer advance deposit of " . formatMoney($amount) . " received.");
        redirect(BASE_URL . '/modules/customers/advances.php');
    }
}

$customers = $pdo->query("SELECT id, name, mobile, current_advance FROM customers WHERE shop_id = {$shopId} AND status = 'active' ORDER BY name ASC")->fetchAll();

$sql = "SELECT ca.*, c.name as customer_name, c.mobile as customer_mobile, u.full_name as created_by_name
        FROM customer_advances ca
        JOIN customers c ON ca.customer_id = c.id
        JOIN users u ON ca.created_by = u.id
        WHERE ca.shop_id = :s
        ORDER BY ca.id DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId]);
$advances = $stmt->fetchAll();

$pageTitle = 'Customer Advance Deposits (কাস্টমার অগ্রিম জমা)';
$activeMenu = 'customers';
$activeSub = 'customer-advances';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-hand-holding-dollar"></i> Customer Advance Deposits (অগ্রিম জমা)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Manage unallocated prepayments and site booking advances.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('advance-modal')">
      <i class="fa fa-plus"></i> + Receive Advance Deposit
    </button>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Receipt No</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Total Received</th>
          <th>Adjusted to Memos</th>
          <th>Remaining Balance</th>
          <th>Payment Mode</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($advances)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">No advance deposits recorded.</td></tr>
        <?php else: ?>
          <?php foreach ($advances as $a): ?>
            <tr>
              <td style="font-weight:700;color:var(--primary);"><?= e($a['advance_no']) ?></td>
              <td><?= formatDate($a['receipt_date']) ?></td>
              <td>
                <div style="font-weight:600;"><?= e($a['customer_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($a['customer_mobile']) ?></div>
              </td>
              <td style="font-weight:700;"><?= formatMoney($a['amount']) ?></td>
              <td style="color:var(--text-secondary);"><?= formatMoney($a['adjusted_amount']) ?></td>
              <td style="font-weight:800;color:var(--success);font-size:0.95rem;"><?= formatMoney($a['remaining_balance']) ?></td>
              <td><?= e($a['payment_method']) ?></td>
              <td>
                <span class="badge <?= $a['status'] === 'active' ? 'badge-success' : 'badge-secondary' ?>">
                  <?= ucfirst(str_replace('_', ' ', $a['status'])) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ADVANCE INTAKE MODAL -->
<div class="modal-backdrop" id="advance-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;font-size:1.1rem;"><i class="fa fa-hand-holding-dollar"></i> Receive Customer Advance Deposit</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('advance-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <div class="form-group">
          <label class="form-label">Customer *</label>
          <select name="customer_id" class="form-control" required>
            <option value="">-- Choose Customer --</option>
            <?php foreach ($customers as $c): ?>
              <option value="<?= $c['id'] ?>">
                <?= e($c['name']) ?> (<?= e($c['mobile']) ?>) - Current Advance: ৳<?= number_format((float)$c['current_advance']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Advance Amount (টাকার পরিমাণ) *</label>
            <input type="number" step="any" min="1" name="amount" class="form-control" placeholder="0.00" required>
          </div>
          <div class="form-group">
            <label class="form-label">Receipt Date *</label>
            <input type="date" name="receipt_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Payment Method</label>
          <select name="payment_method" class="form-control">
            <option value="Cash">Cash (নগদ)</option>
            <option value="bKash">bKash (বিকাশ)</option>
            <option value="Nagad">Nagad (নগদ)</option>
            <option value="Bank Deposit">Bank Deposit / Cheque</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Remarks / Site Purpose</label>
          <input type="text" name="notes" class="form-control" placeholder="e.g. Advance deposit for 200 bags Shah Cement">
        </div>

        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('advance-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Receive Advance</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
