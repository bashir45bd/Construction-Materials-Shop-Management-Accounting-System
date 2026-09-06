<?php
/**
 * Initial Migration Opening Balances Setup
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\StockEngineService;
use App\Services\CustomerLedgerService;
use App\Services\SupplierLedgerService;

Auth::requireLogin();
Auth::requirePermission('settings.manage');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

// Handle Customer Opening Balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_cust_op') {
    Csrf::verifyOrDie();
    $custId = (int)($_POST['customer_id'] ?? 0);
    $due    = (float)($_POST['opening_due'] ?? 0.0);

    if ($custId > 0 && $due > 0) {
        CustomerLedgerService::recordEntry(
            $custId,
            'OPENING_BALANCE',
            'OPENING',
            $custId,
            'OP-BAL-' . $custId,
            'Opening receivable balance setup',
            $due,
            0.00,
            $userId,
            date('Y-m-d'),
            $shopId
        );
        flash('success', "Customer opening due balance updated.");
        redirect(BASE_URL . '/modules/settings/opening-balances.php');
    }
}

// Handle Supplier Opening Balance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_supp_op') {
    Csrf::verifyOrDie();
    $suppId = (int)($_POST['supplier_id'] ?? 0);
    $due    = (float)($_POST['opening_due'] ?? 0.0);

    if ($suppId > 0 && $due > 0) {
        SupplierLedgerService::recordEntry(
            $suppId,
            'OPENING_BALANCE',
            'OPENING',
            $suppId,
            'SUPP-OP-' . $suppId,
            'Opening payable balance setup',
            0.00,
            $due,
            $userId,
            date('Y-m-d'),
            $shopId
        );
        flash('success', "Supplier opening payable balance updated.");
        redirect(BASE_URL . '/modules/settings/opening-balances.php');
    }
}

$customers = $pdo->query("SELECT id, name, mobile, current_due FROM customers WHERE shop_id = {$shopId} ORDER BY name ASC")->fetchAll();
$suppliers = $pdo->query("SELECT id, name, company_name, current_due FROM suppliers WHERE shop_id = {$shopId} ORDER BY name ASC")->fetchAll();

$pageTitle = 'Opening Balances Migration';
$activeMenu = 'settings';
$activeSub = 'opening-balances';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div style="margin-bottom:1.5rem;">
  <h1 style="font-size:1.5rem;font-weight:800;letter-spacing:-0.025em;"><i class="fa fa-hourglass-start"></i> Opening Balances & Data Migration</h1>
  <p style="color:var(--text-secondary);font-size:0.85rem;">Set up existing customer dues, supplier payables, warehouse stock and initial cash without creating fake sales or purchases.</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:1.5rem;">
  <!-- Customer Opening Due Setup -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-users"></i> Customer Opening Due (কাস্টমার প্রারম্ভিক বাকি)</div>
    </div>

    <form method="POST" action="">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="save_cust_op">
      <div class="form-group">
        <label class="form-label">Select Customer *</label>
        <select name="customer_id" class="form-control" required>
          <option value="">-- Choose Customer --</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>">
              <?= e($c['name']) ?> (<?= e($c['mobile']) ?>) - Current: ৳<?= number_format((float)$c['current_due']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Opening Due Amount (বাকি টাকা) *</label>
        <input type="number" step="any" min="1" name="opening_due" class="form-control" placeholder="0.00" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">
        <i class="fa fa-plus"></i> Post Customer Opening Due
      </button>
    </form>
  </div>

  <!-- Supplier Opening Due Setup -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-truck-field"></i> Supplier Opening Payable (মহাজন প্রারম্ভিক বাকি)</div>
    </div>

    <form method="POST" action="">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="save_supp_op">
      <div class="form-group">
        <label class="form-label">Select Supplier *</label>
        <select name="supplier_id" class="form-control" required>
          <option value="">-- Choose Supplier --</option>
          <?php foreach ($suppliers as $s): ?>
            <option value="<?= $s['id'] ?>">
              <?= e($s['name']) ?> (<?= e($s['company_name'] ?: 'Vendor') ?>) - Current: ৳<?= number_format((float)$s['current_due']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Opening Payable Due (পাওনা টাকা) *</label>
        <input type="number" step="any" min="1" name="opening_due" class="form-control" placeholder="0.00" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;">
        <i class="fa fa-plus"></i> Post Supplier Opening Due
      </button>
    </form>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
