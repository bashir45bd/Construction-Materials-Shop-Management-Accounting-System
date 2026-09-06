<?php
/**
 * Supplier Directory & Accounts Payable
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\SupplierLedgerService;

Auth::requireLogin();
Auth::requirePermission('supplier.view');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

// Handle Add / Edit Supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_supplier') {
    Csrf::verifyOrDie();
    $suppId  = (int)($_POST['supplier_id'] ?? 0);
    $name    = cleanInput($_POST['name'] ?? '');
    $company = cleanInput($_POST['company_name'] ?? '');
    $mobile  = cleanInput($_POST['mobile'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $notes   = cleanInput($_POST['notes'] ?? '');

    if (!empty($name) && !empty($mobile)) {
        if ($suppId > 0) {
            $stmt = $pdo->prepare("UPDATE suppliers SET name = :name, company_name = :comp, mobile = :mob, address = :addr, notes = :notes, updated_at = NOW() WHERE id = :id AND shop_id = :s");
            $stmt->execute(['name' => $name, 'comp' => $company, 'mob' => $mobile, 'addr' => $address, 'notes' => $notes, 'id' => $suppId, 's' => $shopId]);
            flash('success', "Supplier \"{$name}\" updated.");
        } else {
            $suppCountStmt = $pdo->prepare("SELECT COUNT(id) FROM suppliers WHERE shop_id = :s");
            $suppCountStmt->execute(['s' => $shopId]);
            $code = 'SUPP-' . str_pad((string)((int)$suppCountStmt->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO suppliers (shop_id, supplier_code, name, company_name, mobile, address, opening_due, opening_advance, current_due, current_advance, status, notes) VALUES (:s, :code, :name, :comp, :mob, :addr, 0.00, 0.00, 0.00, 0.00, 'active', :notes)");
            $stmt->execute(['s' => $shopId, 'code' => $code, 'name' => $name, 'comp' => $company, 'mob' => $mobile, 'addr' => $address, 'notes' => $notes]);
            flash('success', "Supplier \"{$name}\" registered.");
        }
        redirect(BASE_URL . '/modules/suppliers/list.php');
    }
}

// Handle Supplier Payment Disbursed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'disburse_payment') {
    Csrf::verifyOrDie();
    $suppId = (int)($_POST['supplier_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0.0);
    $method = cleanInput($_POST['payment_method'] ?? 'Bank Transfer');
    $notes  = cleanInput($_POST['notes'] ?? '');
    $date   = cleanInput($_POST['payment_date'] ?? date('Y-m-d'));

    if ($suppId > 0 && $amount > 0) {
        SupplierLedgerService::makePayment(
            $suppId,
            $amount,
            $method,
            'cash',
            1,
            $notes,
            $userId,
            $date,
            $shopId
        );
        flash('success', "Payment of " . formatMoney($amount) . " disbursed to supplier.");
        redirect(BASE_URL . '/modules/suppliers/list.php');
    }
}

$search = cleanInput($_GET['q'] ?? '');
$sql = "SELECT * FROM suppliers WHERE shop_id = :s";
$params = ['s' => $shopId];

if (!empty($search)) {
    $sql .= " AND (name LIKE :q OR company_name LIKE :q OR mobile LIKE :q OR supplier_code LIKE :q)";
    $params['q'] = "%{$search}%";
}
$sql .= " ORDER BY name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$suppliers = $stmt->fetchAll();

$totalPayable = array_sum(array_column($suppliers, 'current_due'));

$pageTitle = 'Suppliers & Distributors (মহাজন)';
$activeMenu = 'purchases';
$activeSub = 'suppliers';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-truck-field"></i> Suppliers & Distributors (মহাজন তালিকা)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Manage manufacturer depots, cement distributors, and steel rebar vendors.</p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center;">
      <div style="font-size:0.95rem;font-weight:700;color:var(--danger);">
        Total Payable Due: <?= formatMoney($totalPayable) ?>
      </div>
      <button type="button" class="btn btn-primary btn-sm" onclick="openSuppModal()">
        <i class="fa fa-plus"></i> + Add Supplier
      </button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Code</th>
          <th>Supplier Name</th>
          <th>Company / Agency</th>
          <th>Mobile</th>
          <th>Address</th>
          <th>Current Payable Due (বাকি)</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($suppliers as $s): ?>
          <tr>
            <td style="font-weight:700;color:var(--text-muted);"><?= e($s['supplier_code']) ?></td>
            <td style="font-weight:700;"><?= e($s['name']) ?></td>
            <td style="color:var(--primary);font-weight:600;"><?= e($s['company_name'] ?: 'Direct Vendor') ?></td>
            <td><?= e($s['mobile']) ?></td>
            <td style="color:var(--text-secondary);"><?= e($s['address'] ?: '-') ?></td>
            <td style="font-weight:800;color:<?= (float)$s['current_due'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
              <?= formatMoney($s['current_due']) ?>
            </td>
            <td style="text-align:center;white-space:nowrap;">
              <?php if ((float)$s['current_due'] > 0): ?>
                <button type="button" class="btn btn-success btn-sm" title="Disburse Payment" onclick='openSuppPayModal(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                  <i class="fa fa-money-bill-wave"></i> Pay
                </button>
              <?php endif; ?>
              <button type="button" class="btn btn-secondary btn-sm" onclick='editSupp(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                <i class="fa fa-edit"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Supplier Modal -->
<div class="modal-backdrop" id="supp-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;" id="supp-modal-title"><i class="fa fa-truck-field"></i> Add Supplier</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('supp-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="save_supplier">
        <input type="hidden" name="supplier_id" id="supp-id" value="0">
        <div class="form-group">
          <label class="form-label">Contact Person Name *</label>
          <input type="text" name="name" id="supp-name" class="form-control" placeholder="e.g. Md. Kabir Hossain" required>
        </div>
        <div class="form-group">
          <label class="form-label">Company / Distributor Name</label>
          <input type="text" name="company_name" id="supp-comp" class="form-control" placeholder="e.g. Shah Cement Sylhet Agency">
        </div>
        <div class="form-group">
          <label class="form-label">Mobile Number *</label>
          <input type="text" name="mobile" id="supp-mob" class="form-control" placeholder="017XXXXXXXX" required>
        </div>
        <div class="form-group">
          <label class="form-label">Depot / Yard Address</label>
          <textarea name="address" id="supp-addr" class="form-control" rows="2"></textarea>
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('supp-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Supplier</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- SUPPLIER PAYMENT MODAL -->
<div class="modal-backdrop" id="supp-pay-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;"><i class="fa fa-money-bill-wave"></i> Disburse Supplier Payment</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('supp-pay-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="disburse_payment">
        <input type="hidden" name="supplier_id" id="spay-supp-id" value="0">

        <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);margin-bottom:1rem;">
          <div style="font-weight:800;" id="spay-supp-name">-</div>
          <div style="color:var(--danger);font-weight:700;margin-top:2px;">
            Current Payable: <span id="spay-supp-due">৳0.00</span>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Payment Amount *</label>
          <input type="number" step="any" min="1" name="amount" id="spay-amount" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Payment Method</label>
          <select name="payment_method" class="form-control">
            <option value="Cash">Cash (দোকানের ক্যাশ)</option>
            <option value="Bank Transfer">Bank Transfer / Cheque</option>
            <option value="bKash Merchant">bKash</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Payment Date</label>
          <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Remarks / Cheque No</label>
          <input type="text" name="notes" class="form-control" placeholder="Optional notes">
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('supp-pay-modal')">Cancel</button>
          <button type="submit" class="btn btn-success">Disburse Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openSuppModal() {
  document.getElementById('supp-modal-title').innerHTML = '<i class="fa fa-truck-field"></i> Add Supplier';
  document.getElementById('supp-id').value = '0';
  document.getElementById('supp-name').value = '';
  document.getElementById('supp-comp').value = '';
  document.getElementById('supp-mob').value = '';
  document.getElementById('supp-addr').value = '';
  openModal('supp-modal');
}
function editSupp(s) {
  document.getElementById('supp-modal-title').innerHTML = '<i class="fa fa-edit"></i> Edit Supplier';
  document.getElementById('supp-id').value = s.id;
  document.getElementById('supp-name').value = s.name;
  document.getElementById('supp-comp').value = s.company_name || '';
  document.getElementById('supp-mob').value = s.mobile;
  document.getElementById('supp-addr').value = s.address || '';
  openModal('supp-modal');
}
function openSuppPayModal(s) {
  document.getElementById('spay-supp-id').value = s.id;
  document.getElementById('spay-supp-name').textContent = `${s.name} (${s.company_name || 'Vendor'})`;
  document.getElementById('spay-supp-due').textContent = '৳' + parseFloat(s.current_due).toLocaleString();
  document.getElementById('spay-amount').value = s.current_due;
  openModal('supp-pay-modal');
}
</script>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
