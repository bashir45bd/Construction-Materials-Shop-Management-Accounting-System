<?php
/**
 * Create Price Quotation / Estimate (দরপত্র / কোটেশন)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\DocumentNumberService;

Auth::requireLogin();

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

// Handle Quotation Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $itemsJson  = $_POST['items_json'] ?? '[]';
    $items      = json_decode($itemsJson, true);
    $subtotal   = (float)($_POST['subtotal'] ?? 0.0);
    $discount   = (float)($_POST['discount_amount'] ?? 0.0);
    $grandTotal = (float)($_POST['grand_total'] ?? 0.0);
    $validUntil = cleanInput($_POST['valid_until'] ?? date('Y-m-d', strtotime('+15 days')));
    $notes      = cleanInput($_POST['notes'] ?? '');

    if ($customerId > 0 && !empty($items)) {
        DB::transaction(function($pdo) use ($customerId, $items, $subtotal, $discount, $grandTotal, $validUntil, $notes, $userId, $shopId) {
            $quoteNo = DocumentNumberService::getNextNumber('QUOTATION', $shopId);
            $today = date('Y-m-d');

            $stmt = $pdo->prepare("INSERT INTO quotations (shop_id, quotation_no, customer_id, quotation_date, valid_until, subtotal, discount_amount, grand_total, status, notes, created_by) VALUES (:s, :qno, :cid, :qdate, :vuntil, :sub, :disc, :gtot, 'sent', :notes, :uid)");
            $stmt->execute([
                's'      => $shopId,
                'qno'    => $quoteNo,
                'cid'    => $customerId,
                'qdate'  => $today,
                'vuntil' => $validUntil,
                'sub'    => $subtotal,
                'disc'   => $discount,
                'gtot'   => $grandTotal,
                'notes'  => $notes,
                'uid'    => $userId
            ]);
            $quoteId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("INSERT INTO quotation_items (quotation_id, item_order, product_id, unit_id, quantity, unit_price, line_total) VALUES (:qid, :iorder, :pid, :uid, :qty, :price, :ltot)");
            $orderIdx = 1;
            foreach ($items as $it) {
                $itemStmt->execute([
                    'qid'    => $quoteId,
                    'iorder' => $orderIdx++,
                    'pid'    => (int)$it['product_id'],
                    'uid'    => (int)$it['unit_id'],
                    'qty'    => (float)$it['quantity'],
                    'price'  => (float)$it['unit_price'],
                    'ltot'   => (float)$it['line_total']
                ]);
            }
        });

        flash('success', "Quotation generated successfully.");
        redirect(BASE_URL . '/modules/quotations/quotation-list.php');
    }
}

$customers = $pdo->query("SELECT id, name, mobile FROM customers WHERE shop_id = {$shopId} AND status = 'active' ORDER BY name ASC")->fetchAll();
$products = $pdo->query("SELECT p.id, p.name, p.selling_price, p.base_unit_id, u.symbol as unit_symbol FROM products p JOIN units u ON p.base_unit_id = u.id WHERE p.shop_id = {$shopId} AND p.is_active = 1 ORDER BY p.name ASC")->fetchAll();

$pageTitle = 'New Price Quotation (নতুন কোটেশন)';
$activeMenu = 'quotations';
$activeSub = 'new-quotation';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-file-lines"></i> New Price Quotation / Estimate (দরপত্র)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Prepare construction price estimates for clients without affecting warehouse stock.</p>
    </div>
  </div>

  <form method="POST" action="" id="quote-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="items_json" id="quote-items-json" value="[]">
    <input type="hidden" name="subtotal" id="quote-subtotal" value="0">
    <input type="hidden" name="grand_total" id="quote-grand-total" value="0">

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1rem;margin-bottom:1.5rem;">
      <div class="form-group">
        <label class="form-label">Customer *</label>
        <select name="customer_id" class="form-control" required>
          <option value="">-- Choose Customer --</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['mobile']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Quotation Valid Until *</label>
        <input type="date" name="valid_until" class="form-control" value="<?= date('Y-m-d', strtotime('+15 days')) ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label">Add Products to Estimate</label>
        <select id="select-quote-product" class="form-control" onchange="addQuoteItem(this.value)">
          <option value="">-- Click to Add Material Line --</option>
          <?php foreach ($products as $pr): ?>
            <option value="<?= $pr['id'] ?>" data-product='<?= json_encode($pr, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
              <?= e($pr['name']) ?> (৳<?= number_format((float)$pr['selling_price'], 2) ?>/<?= e($pr['unit_symbol']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Items Table -->
    <div class="table-responsive" style="margin-bottom:1.5rem;border:1px solid var(--border-color);border-radius:var(--radius);">
      <table class="table">
        <thead>
          <tr>
            <th>Product Description</th>
            <th style="width:120px;">Quantity</th>
            <th>Unit</th>
            <th style="width:130px;">Offered Rate</th>
            <th style="text-align:right;">Total</th>
            <th style="text-align:center;width:50px;"></th>
          </tr>
        </thead>
        <tbody id="quote-tbody">
          <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No items in quotation. Select materials above.</td></tr>
        </tbody>
      </table>
    </div>

    <div style="background:var(--bg-surface-secondary);border:1px solid var(--border-color);border-radius:var(--radius);padding:1.25rem;max-width:500px;margin-left:auto;">
      <div style="display:flex;justify-content:space-between;margin-bottom:0.5rem;">
        <span>Subtotal:</span>
        <span style="font-weight:700;" id="q-disp-subtotal">৳0.00</span>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
        <span>Discount:</span>
        <input type="number" step="any" min="0" name="discount_amount" id="q-discount" class="form-control form-control-sm" style="width:120px;" value="0" oninput="recalcQuote()">
      </div>
      <div style="display:flex;justify-content:space-between;padding:0.5rem 0;border-top:1px dashed var(--border-color);border-bottom:1px dashed var(--border-color);font-size:1.1rem;font-weight:800;color:var(--primary);margin-bottom:0.75rem;">
        <span>Estimated Total:</span>
        <span id="q-disp-grand">৳0.00</span>
      </div>
      <div class="form-group">
        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Terms & Conditions (e.g. Delivery within 3 days)">
      </div>
      <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">
        <i class="fa fa-save"></i> Save & Generate Quotation
      </button>
    </div>
  </form>
</div>

<script>
let quoteItems = [];

function addQuoteItem(prodId) {
  if (!prodId) return;
  const select = document.getElementById('select-quote-product');
  const option = select.options[select.selectedIndex];
  const prod = JSON.parse(option.getAttribute('data-product'));

  quoteItems.push({
    product_id: prod.id,
    name: prod.name,
    unit_id: prod.base_unit_id,
    unit_symbol: prod.unit_symbol,
    quantity: 1,
    unit_price: parseFloat(prod.selling_price) || 0.0,
    line_total: parseFloat(prod.selling_price) || 0.0
  });

  select.value = '';
  renderQuoteTable();
  recalcQuote();
}

function renderQuoteTable() {
  const tbody = document.getElementById('quote-tbody');
  if (quoteItems.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);">No items in quotation. Select materials above.</td></tr>';
    return;
  }

  tbody.innerHTML = quoteItems.map((item, idx) => `
    <tr>
      <td style="font-weight:700;">${item.name}</td>
      <td><input type="number" step="any" min="0.01" class="form-control form-control-sm" value="${item.quantity}" onchange="updateQQty(${idx}, this.value)"></td>
      <td>${item.unit_symbol}</td>
      <td><input type="number" step="any" min="0" class="form-control form-control-sm" value="${item.unit_price}" onchange="updateQPrice(${idx}, this.value)"></td>
      <td style="text-align:right;font-weight:700;">৳${item.line_total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
      <td style="text-align:center;"><button type="button" class="btn btn-sm btn-danger" onclick="removeQItem(${idx})">&times;</button></td>
    </tr>
  `).join('');
}

function updateQQty(idx, val) {
  const q = parseFloat(val) || 1;
  quoteItems[idx].quantity = q;
  quoteItems[idx].line_total = q * quoteItems[idx].unit_price;
  renderQuoteTable();
  recalcQuote();
}

function updateQPrice(idx, val) {
  const p = parseFloat(val) || 0;
  quoteItems[idx].unit_price = p;
  quoteItems[idx].line_total = quoteItems[idx].quantity * p;
  renderQuoteTable();
  recalcQuote();
}

function removeQItem(idx) {
  quoteItems.splice(idx, 1);
  renderQuoteTable();
  recalcQuote();
}

function recalcQuote() {
  const subtotal = quoteItems.reduce((sum, i) => sum + i.line_total, 0.0);
  const discount = parseFloat(document.getElementById('q-discount').value) || 0.0;
  const grand = Math.max(0.0, subtotal - discount);

  document.getElementById('quote-subtotal').value = subtotal;
  document.getElementById('quote-grand-total').value = grand;
  document.getElementById('quote-items-json').value = JSON.stringify(quoteItems);

  document.getElementById('q-disp-subtotal').textContent = '৳' + subtotal.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
  document.getElementById('q-disp-grand').textContent = '৳' + grand.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
