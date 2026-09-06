<?php
/**
 * Customer Directory & Accounts Receivable Overview
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\CustomerLedgerService;

Auth::requireLogin();
Auth::requirePermission('customer.view');

$shopId = Auth::shopId();
$pdo = DB::pdo();

// Handle Customer Edit / Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::hasPermission('customer.manage')) {
    Csrf::verifyOrDie();
    $custId  = (int)($_POST['customer_id'] ?? 0);
    $name    = cleanInput($_POST['name'] ?? '');
    $mobile  = cleanInput($_POST['mobile'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $type    = cleanInput($_POST['customer_type'] ?? 'retail');
    $notes   = cleanInput($_POST['notes'] ?? '');

    if (!empty($name) && !empty($mobile)) {
        if ($custId > 0) {
            $stmt = $pdo->prepare("UPDATE customers SET name = :name, mobile = :mob, address = :addr, customer_type = :type, notes = :notes, updated_at = NOW() WHERE id = :id AND shop_id = :s");
            $stmt->execute(['name' => $name, 'mob' => $mobile, 'addr' => $address, 'type' => $type, 'notes' => $notes, 'id' => $custId, 's' => $shopId]);
            flash('success', "Customer \"{$name}\" updated.");
        } else {
            $custCountStmt = $pdo->prepare("SELECT COUNT(id) FROM customers WHERE shop_id = :s");
            $custCountStmt->execute(['s' => $shopId]);
            $code = 'CUST-' . str_pad((string)((int)$custCountStmt->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);

            $stmt = $pdo->prepare("INSERT INTO customers (shop_id, customer_code, name, mobile, address, customer_type, opening_due, opening_advance, current_due, current_advance, status, notes) VALUES (:s, :code, :name, :mob, :addr, :type, 0.00, 0.00, 0.00, 0.00, 'active', :notes)");
            $stmt->execute(['s' => $shopId, 'code' => $code, 'name' => $name, 'mob' => $mobile, 'addr' => $address, 'type' => $type, 'notes' => $notes]);
            flash('success', "Customer \"{$name}\" registered.");
        }
        redirect(BASE_URL . '/modules/customers/list.php');
    }
}

$search = cleanInput($_GET['q'] ?? '');
$sql = "SELECT * FROM customers WHERE shop_id = :s";
$params = ['s' => $shopId];

if (!empty($search)) {
    $sql .= " AND (name LIKE :q OR mobile LIKE :q OR customer_code LIKE :q OR address LIKE :q)";
    $params['q'] = "%{$search}%";
}
$sql .= " ORDER BY name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$totalDue = array_sum(array_column($customers, 'current_due'));
$totalAdvance = array_sum(array_column($customers, 'current_advance'));

$pageTitle = 'Customer Directory (কাস্টমার তালিকা)';
$activeMenu = 'customers';
$activeSub = 'customers';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-users"></i> Customer Directory (কাস্টমার তালিকা)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Manage contractors, builders and retail buyers with isolated Due & Advance tracking.</p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center;">
      <div style="font-size:0.9rem;font-weight:700;">
        <span style="color:var(--danger);">Total Due: <?= formatMoney($totalDue) ?></span> | 
        <span style="color:var(--success);">Total Advance: <?= formatMoney($totalAdvance) ?></span>
      </div>
      <?php if (Auth::hasPermission('customer.manage')): ?>
        <button type="button" class="btn btn-primary btn-sm" onclick="openCustModal()">
          <i class="fa fa-user-plus"></i> + Add Customer
        </button>
      <?php endif; ?>
    </div>
  </div>

  <form method="GET" style="display:flex;gap:0.75rem;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);">
    <input type="text" name="q" class="form-control form-control-sm" style="max-width:300px;" placeholder="Search Name, Mobile or Code..." value="<?= e($search) ?>">
    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa fa-filter"></i> Search</button>
    <a href="<?= BASE_URL ?>/modules/customers/list.php" class="btn btn-secondary btn-sm">Reset</a>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Customer Code</th>
          <th>Customer Name</th>
          <th>Mobile</th>
          <th>Type</th>
          <th>Address</th>
          <th>Current Due (বাকি)</th>
          <th>Current Advance (জমা)</th>
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">No customers found.</td></tr>
        <?php else: ?>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td style="font-weight:700;color:var(--text-muted);"><?= e($c['customer_code']) ?></td>
              <td style="font-weight:700;">
                <a href="<?= BASE_URL ?>/modules/customers/ledger.php?customer_id=<?= $c['id'] ?>" style="color:var(--text-primary);text-decoration:none;">
                  <?= e($c['name']) ?>
                </a>
              </td>
              <td><?= e($c['mobile']) ?></td>
              <td><span class="badge badge-info"><?= ucfirst($c['customer_type']) ?></span></td>
              <td style="color:var(--text-secondary);"><?= e($c['address'] ?: '-') ?></td>
              <td style="font-weight:800;color:<?= (float)$c['current_due'] > 0 ? 'var(--danger)' : 'var(--text-muted)' ?>;">
                <?= formatMoney($c['current_due']) ?>
              </td>
              <td style="font-weight:800;color:<?= (float)$c['current_advance'] > 0 ? 'var(--success)' : 'var(--text-muted)' ?>;">
                <?= formatMoney($c['current_advance']) ?>
              </td>
              <td style="text-align:center;white-space:nowrap;">
                <a href="<?= BASE_URL ?>/modules/customers/ledger.php?customer_id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm" title="View Ledger Statement">
                  <i class="fa fa-book"></i>
                </a>
                <?php if (Auth::hasPermission('customer.payment') && (float)$c['current_due'] > 0): ?>
                  <button type="button" class="btn btn-success btn-sm" title="Collect Due Payment" onclick='openPaymentModal(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                    <i class="fa fa-money-bill-wave"></i>
                  </button>
                <?php endif; ?>
                <?php if (Auth::hasPermission('customer.manage')): ?>
                  <button type="button" class="btn btn-secondary btn-sm" onclick='editCust(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                    <i class="fa fa-edit"></i>
                  </button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Customer Modal -->
<div class="modal-backdrop" id="cust-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;font-size:1.1rem;" id="cust-modal-title"><i class="fa fa-user-plus"></i> Add Customer</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('cust-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <input type="hidden" name="customer_id" id="cust-id" value="0">
        <div class="form-group">
          <label class="form-label">Customer Name *</label>
          <input type="text" name="name" id="cust-name" class="form-control" placeholder="e.g. Abdul Karim" required>
        </div>
        <div class="form-group">
          <label class="form-label">Mobile Number *</label>
          <input type="text" name="mobile" id="cust-mobile" class="form-control" placeholder="e.g. 017XXXXXXXX" required>
        </div>
        <div class="form-group">
          <label class="form-label">Address / Project Site</label>
          <textarea name="address" id="cust-address" class="form-control" rows="2" placeholder="e.g. Hospital Road, Habiganj"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Customer Type</label>
          <select name="customer_type" id="cust-type" class="form-control">
            <option value="retail">Retail Buyer</option>
            <option value="contractor">Contractor (ঠিকাদার)</option>
            <option value="builder">Builder / Real Estate</option>
            <option value="dealer">Sub-Dealer</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes / Remarks</label>
          <input type="text" name="notes" id="cust-notes" class="form-control" placeholder="Optional notes">
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('cust-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- DUE PAYMENT COLLECTION MODAL -->
<div class="modal-backdrop" id="pay-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;font-size:1.1rem;"><i class="fa fa-money-bill-wave"></i> Collect Due Payment (বাকি আদায়)</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('pay-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form id="pay-form" onsubmit="event.preventDefault(); submitPayment();">
        <input type="hidden" id="pay-cust-id" value="0">
        <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);margin-bottom:1rem;">
          <div style="font-weight:800;" id="pay-cust-name">-</div>
          <div style="color:var(--danger);font-weight:700;font-size:0.95rem;margin-top:2px;">
            Current Total Due: <span id="pay-cust-due">৳0.00</span>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Payment Amount (আদায়ের পরিমাণ) *</label>
          <input type="number" step="any" min="1" id="pay-amount" class="form-control" placeholder="0.00" required>
        </div>
        <div class="form-group">
          <label class="form-label">Payment Method</label>
          <select id="pay-method" class="form-control">
            <option value="Cash">Cash (নগদ)</option>
            <option value="bKash">bKash (বিকাশ)</option>
            <option value="Nagad">Nagad (নগদ)</option>
            <option value="Bank">Bank Deposit / Cheque</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Remarks / Receipt Notes</label>
          <input type="text" id="pay-notes" class="form-control" placeholder="e.g. Received by Salesman">
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('pay-modal')">Cancel</button>
          <button type="submit" class="btn btn-success" id="btn-submit-pay"><i class="fa fa-check"></i> Collect Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openCustModal() {
  document.getElementById('cust-modal-title').innerHTML = '<i class="fa fa-user-plus"></i> Add Customer';
  document.getElementById('cust-id').value = '0';
  document.getElementById('cust-name').value = '';
  document.getElementById('cust-mobile').value = '';
  document.getElementById('cust-address').value = '';
  document.getElementById('cust-notes').value = '';
  openModal('cust-modal');
}
function editCust(c) {
  document.getElementById('cust-modal-title').innerHTML = '<i class="fa fa-edit"></i> Edit Customer';
  document.getElementById('cust-id').value = c.id;
  document.getElementById('cust-name').value = c.name;
  document.getElementById('cust-mobile').value = c.mobile;
  document.getElementById('cust-address').value = c.address || '';
  document.getElementById('cust-type').value = c.customer_type || 'retail';
  document.getElementById('cust-notes').value = c.notes || '';
  openModal('cust-modal');
}
function openPaymentModal(c) {
  document.getElementById('pay-cust-id').value = c.id;
  document.getElementById('pay-cust-name').textContent = `${c.name} (${c.mobile})`;
  document.getElementById('pay-cust-due').textContent = '৳' + parseFloat(c.current_due).toLocaleString();
  document.getElementById('pay-amount').value = c.current_due;
  openModal('pay-modal');
}
async function submitPayment() {
  const custId = document.getElementById('pay-cust-id').value;
  const amount = parseFloat(document.getElementById('pay-amount').value) || 0;
  const method = document.getElementById('pay-method').value;
  const notes = document.getElementById('pay-notes').value;

  if (amount <= 0) return;

  const btn = document.getElementById('btn-submit-pay');
  btn.disabled = true;

  try {
    const data = await apiRequest(`${BASE_URL}/ajax/customers.php?action=receive_payment`, 'POST', {
      customer_id: custId,
      amount: amount,
      payment_method: method,
      account_type: 'cash',
      account_id: 1,
      notes: notes
    });

    if (data.success) {
      showToast(data.message, 'success');
      closeModal('pay-modal');
      setTimeout(() => location.reload(), 500);
    } else {
      showToast(data.message || 'Payment collection failed.', 'error');
    }
  } catch (e) {}
  finally {
    btn.disabled = false;
  }
}
</script>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
