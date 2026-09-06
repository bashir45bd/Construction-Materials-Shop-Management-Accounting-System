<?php
/**
 * New Purchase Procurement Voucher (মালামাল ক্রয় ভাউচার)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\SupplierLedgerService;

Auth::requireLogin();
Auth::requirePermission('purchase.create');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

// Handle Purchase Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $supplierId = (int)($_POST['supplier_id'] ?? 0);
    $itemsJson  = $_POST['items_json'] ?? '[]';
    $items      = json_decode($itemsJson, true);
    $subtotal   = (float)($_POST['subtotal'] ?? 0.0);
    $discount   = (float)($_POST['discount_amount'] ?? 0.0);
    $grandTotal = (float)($_POST['grand_total'] ?? 0.0);
    $paidAmount = (float)($_POST['paid_amount'] ?? 0.0);
    $accType    = (string)($_POST['account_type'] ?? 'cash');
    $accId      = (int)($_POST['account_id'] ?? 1);
    $notes      = cleanInput($_POST['notes'] ?? '');
    $pDate      = cleanInput($_POST['purchase_date'] ?? date('Y-m-d'));

    if ($supplierId > 0 && !empty($items)) {
        try {
            $res = SupplierLedgerService::createPurchase(
                $supplierId,
                $items,
                $subtotal,
                $discount,
                $grandTotal,
                $paidAmount,
                $accType,
                $accId,
                $notes,
                $userId,
                $pDate,
                $shopId
            );

            flash('success', "Purchase voucher {$res['purchase_number']} saved and warehouse inventory updated.");
            redirect(BASE_URL . '/modules/purchases/purchase-history.php');
        } catch (\Throwable $e) {
            flash('error', "Purchase failed: " . $e->getMessage());
        }
    }
}

$suppliers = $pdo->query("SELECT id, name, company_name, current_due FROM suppliers WHERE shop_id = {$shopId} AND status = 'active' ORDER BY name ASC")->fetchAll();
$products = $pdo->query("SELECT p.id, p.name, p.sku, p.purchase_price, p.base_unit_id, u.symbol as unit_symbol, u.name as unit_name
                         FROM products p 
                         JOIN units u ON p.base_unit_id = u.id
                         WHERE p.shop_id = {$shopId} AND p.is_active = 1 ORDER BY p.name ASC")->fetchAll();

$pageTitle = 'New Material Purchase (নতুন ক্রয় ভাউচার)';
$activeMenu = 'purchases';
$activeSub = 'new-purchase';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-cart-plus"></i> New Material Purchase (মালামাল ক্রয় ভাউচার)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Procure materials from cement distributors, steel mills, and brick suppliers.</p>
    </div>
  </div>

  <form method="POST" action="" id="purchase-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="items_json" id="purchase-items-json" value="[]">
    <input type="hidden" name="subtotal" id="pur-subtotal" value="0">
    <input type="hidden" name="grand_total" id="pur-grand-total" value="0">

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1rem;margin-bottom:1.5rem;">
      <div class="form-group">
        <label class="form-label">Supplier / Distributor (মহাজন) *</label>
        <select name="supplier_id" id="pur-supplier" class="form-control" required>
          <option value="">-- Choose Supplier --</option>
          <?php foreach ($suppliers as $s): ?>
            <option value="<?= $s['id'] ?>">
              <?= e($s['name']) ?> (<?= e($s['company_name'] ?? 'Direct') ?>) - Due: ৳<?= number_format((float)$s['current_due']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Purchase Date *</label>
        <input type="date" name="purchase_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Procured Materials Line *</label>
        <select id="select-add-product" class="form-control" onchange="addPurchaseItem(this.value)">
          <option value="">-- Click to Add Product Line --</option>
          <?php foreach ($products as $pr): ?>
            <option value="<?= $pr['id'] ?>" data-product='<?= json_encode($pr, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
              <?= e($pr['name']) ?> (Rate: ৳<?= number_format((float)$pr['purchase_price'], 2) ?>/<?= e($pr['unit_symbol']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Purchase Items Table -->
    <div class="table-responsive" style="margin-bottom:1.5rem;border:1px solid var(--border-color);border-radius:var(--radius);">
      <table class="table">
        <thead>
          <tr>
            <th>Product Description</th>
            <th style="width:130px;">Quantity</th>
            <th>Unit</th>
            <th style="width:140px;">Unit Cost</th>
            <th style="text-align:right;">Line Total</th>
            <th style="text-align:center;width:50px;"></th>
          </tr>
        </thead>
        <tbody id="purchase-tbody">
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No products added yet. Select materials above.</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Financial Settlement & Payments -->
    <div style="background:var(--bg-surface-secondary);border:1px solid var(--border-color);border-radius:var(--radius);padding:1.25rem;max-width:550px;margin-left:auto;">
      <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;">
        <span style="font-weight:600;color:var(--text-secondary);">Subtotal:</span>
        <span style="font-weight:700;" id="pur-disp-subtotal">৳0.00</span>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
        <span style="font-weight:600;color:var(--text-secondary);">Supplier Discount (ছাড়):</span>
        <input type="number" step="any" min="0" name="discount_amount" id="pur-discount" class="form-control form-control-sm" style="width:120px;" value="0" oninput="recalcPurchase()">
      </div>

      <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-top:1px dashed var(--border-color);border-bottom:1px dashed var(--border-color);font-size:1.1rem;font-weight:800;margin-bottom:0.75rem;">
        <span>Grand Total (সর্বমোট বিল):</span>
        <span style="color:var(--primary);" id="pur-disp-grand">৳0.00</span>
      </div>

      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
        <span style="font-weight:600;color:var(--text-secondary);">Paid Amount (পরিশোধ):</span>
        <input type="number" step="any" min="0" name="paid_amount" id="pur-paid" class="form-control form-control-sm" style="width:140px;font-weight:700;" value="0" oninput="recalcPurchase()">
      </div>

      <div style="display:flex;justify-content:space-between;padding-top:0.5rem;border-top:1px solid var(--border-color);font-size:1.05rem;font-weight:800;color:var(--danger);margin-bottom:1rem;">
        <span>Supplier Due (বাকি):</span>
        <span id="pur-disp-due">৳0.00</span>
      </div>

      <div class="form-group">
        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Purchase invoice / Truck challan notes...">
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
        <i class="fa fa-check"></i> Save Purchase Voucher & Stock In
      </button>
    </div>
  </form>
</div>

<script>
let purItems = [];

function addPurchaseItem(prodId) {
  if (!prodId) return;
  const select = document.getElementById('select-add-product');
  const option = select.options[select.selectedIndex];
  const prod = JSON.parse(option.getAttribute('data-product'));

  const existing = purItems.find(i => i.product_id === prod.id);
  if (existing) {
    existing.quantity += 1;
    existing.line_total = existing.quantity * existing.unit_cost;
  } else {
    purItems.push({
      product_id: prod.id,
      name: prod.name,
      unit_id: prod.base_unit_id,
      unit_symbol: prod.unit_symbol,
      quantity: 1,
      unit_cost: parseFloat(prod.purchase_price) || 0.0,
      line_total: parseFloat(prod.purchase_price) || 0.0
    });
  }

  select.value = '';
  renderPurTable();
  recalcPurchase();
}

function renderPurTable() {
  const tbody = document.getElementById('purchase-tbody');
  if (purItems.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No products added yet. Select materials above.</td></tr>';
    return;
  }

  tbody.innerHTML = purItems.map((item, idx) => `
    <tr>
      <td style="font-weight:700;">${item.name}</td>
      <td>
        <input type="number" step="any" min="0.01" class="form-control form-control-sm" value="${item.quantity}" onchange="updatePurQty(${idx}, this.value)">
      </td>
      <td>${item.unit_symbol}</td>
      <td>
        <input type="number" step="any" min="0" class="form-control form-control-sm" value="${item.unit_cost}" onchange="updatePurCost(${idx}, this.value)">
      </td>
      <td style="text-align:right;font-weight:700;">৳${item.line_total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
      <td style="text-align:center;">
        <button type="button" class="btn btn-sm btn-danger" onclick="removePurItem(${idx})">&times;</button>
      </td>
    </tr>
  `).join('');
}

function updatePurQty(idx, val) {
  const q = parseFloat(val) || 1;
  purItems[idx].quantity = q;
  purItems[idx].line_total = q * purItems[idx].unit_cost;
  renderPurTable();
  recalcPurchase();
}

function updatePurCost(idx, val) {
  const c = parseFloat(val) || 0;
  purItems[idx].unit_cost = c;
  purItems[idx].line_total = purItems[idx].quantity * c;
  renderPurTable();
  recalcPurchase();
}

function removePurItem(idx) {
  purItems.splice(idx, 1);
  renderPurTable();
  recalcPurchase();
}

function recalcPurchase() {
  const subtotal = purItems.reduce((sum, i) => sum + i.line_total, 0.0);
  const discount = parseFloat(document.getElementById('pur-discount').value) || 0.0;
  const grandTotal = Math.max(0.0, subtotal - discount);
  const paid = parseFloat(document.getElementById('pur-paid').value) || 0.0;
  const due = Math.max(0.0, grandTotal - paid);

  document.getElementById('pur-subtotal').value = subtotal;
  document.getElementById('pur-grand-total').value = grandTotal;
  document.getElementById('purchase-items-json').value = JSON.stringify(purItems);

  document.getElementById('pur-disp-subtotal').textContent = '৳' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
  document.getElementById('pur-disp-grand').textContent = '৳' + grandTotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
  document.getElementById('pur-disp-due').textContent = '৳' + due.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
