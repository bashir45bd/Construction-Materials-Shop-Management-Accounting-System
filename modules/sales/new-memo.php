<?php
/**
 * New Sales Memo (বিক্রয়) Point of Sale Interface
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Services\MemoService;
use App\Services\CustomerLedgerService;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('sales.create');

$shopId = Auth::shopId();
$recentCustomers = CustomerLedgerService::getRecentCustomers(6, $shopId);
$topProducts = MemoService::getTop20Products(30, $shopId);
$recentProducts = MemoService::getRecentlySoldProducts(6, $shopId);

// Fetch categories for filter
$pdo = DB::pdo();
$catStmt = $pdo->prepare("SELECT id, name FROM categories WHERE shop_id = :s AND is_active = 1 ORDER BY name ASC");
$catStmt->execute(['s' => $shopId]);
$categories = $catStmt->fetchAll();

$brandStmt = $pdo->prepare("SELECT id, name FROM brands WHERE shop_id = :s AND is_active = 1 ORDER BY name ASC");
$brandStmt->execute(['s' => $shopId]);
$brands = $brandStmt->fetchAll();

$pageTitle = 'New Sales Memo (নতুন মেমো তৈরি)';
$activeMenu = 'sales';
$activeSub = 'new-memo';
$extraScripts = [BASE_URL . '/assets/js/memo.js?v=2.1'];

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="sales-pos-layout">
  <!-- LEFT COLUMN: Customer Selection & Product Catalog -->
  <div>
    <!-- STEP 1: CUSTOMER SELECTION -->
    <div class="card" style="margin-bottom:1rem;">
      <div class="card-header" style="margin-bottom:0.75rem;padding-bottom:0.5rem;">
        <div class="card-title" style="font-size:1rem;"><i class="fa fa-user-check"></i> 1. Customer Selection (কাস্টমার নির্বাচন)</div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="openModal('new-customer-modal')">
          <i class="fa fa-user-plus"></i> + New Customer
        </button>
      </div>

      <!-- Live Search Customer -->
      <div style="position:relative;margin-bottom:0.75rem;">
        <input type="text" id="customer-search-input" class="form-control" placeholder="Search existing customer by Name, Mobile Number or Address..." autocomplete="off">
        <div id="customer-search-results" style="display:none;position:absolute;top:100%;left:0;right:0;background:var(--bg-surface);border:1px solid var(--border-color);border-radius:var(--radius);box-shadow:var(--shadow-lg);z-index:25;max-height:240px;overflow-y:auto;"></div>
      </div>

      <!-- Recent Customer Quick-Select Badges -->
      <?php if (!empty($recentCustomers)): ?>
        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.4rem;">Recent Customers (সাম্প্রতিক কাস্টমার):</div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.75rem;">
          <?php foreach ($recentCustomers as $rc): ?>
            <button type="button" class="btn btn-secondary btn-sm" style="font-size:0.8rem;padding:0.3rem 0.6rem;" onclick='selectCustomer(<?= json_encode($rc, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
              <i class="fa fa-user"></i> <?= e($rc['name']) ?>
              <?php if ((float)$rc['current_due'] > 0): ?>
                <span style="color:var(--danger);font-weight:700;margin-left:4px;">(৳<?= number_format((float)$rc['current_due']) ?>)</span>
              <?php elseif ((float)$rc['current_advance'] > 0): ?>
                <span style="color:var(--success);font-weight:700;margin-left:4px;">(+৳<?= number_format((float)$rc['current_advance']) ?>)</span>
              <?php endif; ?>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Selected Customer Profile Card -->
      <div id="selected-customer-card" style="display:none;background:var(--bg-surface-secondary);border:1px solid var(--border-color);border-radius:var(--radius);padding:0.75rem 1rem;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <div>
            <div style="font-weight:800;font-size:1.05rem;" id="cust-disp-name">-</div>
            <div style="font-size:0.85rem;color:var(--text-secondary);margin-top:2px;">
              <i class="fa fa-phone"></i> <span id="cust-disp-mobile">-</span> | 
              <i class="fa fa-location-dot"></i> <span id="cust-disp-address">-</span>
            </div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:0.85rem;font-weight:700;color:var(--danger);">Due: <span id="cust-disp-due">৳0.00</span></div>
            <div style="font-size:0.85rem;font-weight:700;color:var(--success);">Advance: <span id="cust-disp-advance">৳0.00</span></div>
          </div>
        </div>
      </div>
    </div>

    <!-- STEP 2: PRODUCT SELECTION & TOP 20 -->
    <div class="card">
      <div class="card-header" style="margin-bottom:0.75rem;padding-bottom:0.5rem;flex-wrap:wrap;gap:0.5rem;">
        <div class="card-title" style="font-size:1rem;"><i class="fa fa-boxes-stacked"></i> 2. Select Materials (পণ্য নির্বাচন)</div>
        
        <!-- Category Filter -->
        <div class="memo-product-filters" style="display:flex;gap:0.5rem;flex:1;max-width:620px;">
          <input type="text" id="pos-product-search" class="form-control form-control-sm" placeholder="Live search product name or SKU...">
          <select id="pos-category-filter" class="form-control form-control-sm" style="width:150px;">
            <option value="0">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select id="pos-brand-filter" class="form-control form-control-sm" style="width:145px;">
            <option value="0">All Brands</option>
            <?php foreach ($brands as $brand): ?>
              <option value="<?= $brand['id'] ?>"><?= e($brand['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Top 20 Selling Materials Grid -->
      <div style="font-size:0.8rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;margin-bottom:0.5rem;">
        <i class="fa fa-fire" style="color:var(--warning);"></i> Top 20 Selling Construction Materials (সর্বোচ্চ বিক্রিত মালামাল):
      </div>

      <div class="top-products-grid" id="pos-products-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(180px, 1fr));gap:0.75rem;max-height:360px;overflow-y:auto;padding-right:4px;">
        <?php foreach ($topProducts as $p): ?>
          <div class="card" style="margin:0;cursor:pointer;padding:0.85rem;transition:var(--transition);" onclick='addProductToCart(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
            <div style="font-weight:700;font-size:0.9rem;line-height:1.2;"><?= e($p['name']) ?></div>
            <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px;"><?= e($p['brand_name'] ?? 'General') ?> | <?= e($p['category_name']) ?></div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.6rem;">
              <span style="font-weight:800;color:var(--primary);font-size:0.95rem;">
                <?= formatMoney($p['selling_price']) ?> <small style="font-size:0.75rem;font-weight:400;">/<?= e($p['unit_symbol']) ?></small>
              </span>
              <span class="badge <?= (float)$p['current_stock'] <= (float)$p['min_stock'] ? 'badge-danger' : 'badge-success' ?>" style="font-size:0.7rem;">
                <?= formatQuantity($p['current_stock']) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- RIGHT COLUMN: MEMO CART & FINANCIAL SETTLEMENT -->
  <div class="memo-cart-panel">
    <div class="card memo-cart-card" style="position:sticky;top:calc(var(--navbar-height) + 1rem);">
      <div class="card-header" style="margin-bottom:0.75rem;padding-bottom:0.5rem;">
        <div class="card-title" style="font-size:1.05rem;"><i class="fa fa-cart-shopping"></i> Memo Cart (মেমো বিল)</div>
        <div style="font-size:0.8rem;color:var(--text-secondary);">Date: <?= date('d-m-Y') ?></div>
      </div>

      <!-- Preserved Sequential Cart Table -->
      <div class="table-responsive memo-cart-table" style="border:1px solid var(--border-color);border-radius:var(--radius);margin-bottom:1rem;">
        <table class="table" style="font-size:0.85rem;">
          <thead>
            <tr>
              <th style="width:30px;">#</th>
              <th>Product</th>
              <th style="width:90px;">Qty</th>
              <th>Unit</th>
              <th style="width:100px;">Rate</th>
              <th style="text-align:right;">Total</th>
              <th style="text-align:center;width:40px;"></th>
            </tr>
          </thead>
          <tbody id="memo-cart-tbody">
            <!-- Rendered by memo.js -->
          </tbody>
        </table>
      </div>

      <!-- Financial Calculation Summary Box -->
      <div style="background:var(--bg-surface-secondary);border:1px solid var(--border-color);border-radius:var(--radius);padding:1rem;margin-bottom:1rem;font-size:0.9rem;">
        <!-- Subtotal -->
        <div style="display:flex;justify-content:space-between;margin-bottom:0.4rem;">
          <span style="color:var(--text-secondary);font-weight:600;">Subtotal (মোট):</span>
          <span style="font-weight:700;" id="disp-subtotal">৳0.00</span>
        </div>

        <!-- Discount -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
          <div style="display:flex;align-items:center;gap:0.4rem;">
            <span style="color:var(--text-secondary);font-weight:600;">Discount (ছাড়):</span>
            <select id="memo-discount-type" class="form-control form-control-sm" style="width:65px;padding:0.2rem 0.4rem;font-size:0.75rem;">
              <option value="fixed">৳</option>
              <option value="percentage">%</option>
            </select>
            <input type="number" step="any" min="0" id="memo-discount-val" class="form-control form-control-sm" style="width:75px;padding:0.2rem 0.4rem;" value="0">
          </div>
          <span style="color:var(--danger);font-weight:700;" id="disp-discount">৳0.00</span>
        </div>

        <!-- Grand Total -->
        <div style="display:flex;justify-content:space-between;margin-bottom:0.75rem;padding:0.5rem 0;border-top:1px dashed var(--border-color);border-bottom:1px dashed var(--border-color);font-size:1.1rem;font-weight:800;">
          <span>Grand Total (সর্বমোট):</span>
          <span style="color:var(--primary);" id="disp-grand-total">৳0.00</span>
        </div>

        <!-- Available Advance Option -->
        <div id="advance-available-container" style="display:none;align-items:center;justify-content:space-between;background:var(--success-light);padding:0.4rem 0.75rem;border-radius:var(--radius);margin-bottom:0.6rem;">
          <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;font-weight:700;color:var(--success);cursor:pointer;">
            <input type="checkbox" id="memo-use-advance" checked>
            <span id="advance-available-label">Use Advance Deposit</span>
          </label>
          <span style="font-weight:700;color:var(--success);" id="disp-advance-used">৳0.00</span>
        </div>

        <!-- Paid Now Input -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.6rem;">
          <span style="color:var(--text-secondary);font-weight:600;">Paid Now (নগদ গ্রহণ):</span>
          <input type="number" step="any" min="0" id="memo-paid-amount" class="form-control form-control-sm" style="width:140px;font-weight:700;font-size:0.95rem;" placeholder="0.00">
        </div>

        <!-- Remaining Due -->
        <div style="display:flex;justify-content:space-between;padding-top:0.4rem;border-top:1px solid var(--border-color);font-size:1.05rem;font-weight:800;">
          <span style="color:var(--danger);">Remaining Due (বাকি):</span>
          <span style="color:var(--danger);" id="disp-due-amount">৳0.00</span>
        </div>

        <!-- Overpayment Advance Alert (if any) -->
        <div id="disp-overpayment-row" style="display:none;justify-content:space-between;margin-top:0.4rem;color:var(--info);font-weight:700;font-size:0.85rem;">
          <span>Excess Cash (Credited to Advance):</span>
          <span id="disp-overpayment">৳0.00</span>
        </div>
      </div>

      <!-- Additional Memo Notes -->
      <div class="form-group" style="margin-bottom:1rem;">
        <input type="text" id="memo-notes" class="form-control form-control-sm" placeholder="Memo notes / Site reference / Delivery remarks...">
      </div>

      <!-- Big Action Buttons -->
      <div style="display:flex;gap:0.75rem;">
        <button type="button" class="btn btn-primary btn-lg" id="btn-save-memo" style="flex:1;" onclick="submitMemo(true)">
          <i class="fa fa-print"></i> Save & Print Memo
        </button>
        <button type="button" class="btn btn-secondary btn-lg" onclick="if(confirm('Clear current cart?')){cartItems=[];renderCart();calculateTotals();}">
          <i class="fa fa-rotate-left"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- INLINE NEW CUSTOMER CREATION MODAL -->
<div class="modal-backdrop" id="new-customer-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;font-size:1.1rem;"><i class="fa fa-user-plus"></i> Add New Customer (নতুন কাস্টমার)</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('new-customer-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form id="inline-customer-form" onsubmit="event.preventDefault(); handleInlineCustomerSubmit();">
        <div class="form-group">
          <label class="form-label"><i class="fa fa-user"></i> Customer Name (কাস্টমারের নাম) *</label>
          <input type="text" id="in-cust-name" class="form-control" placeholder="e.g. Haji Abdul Jalil" required>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fa fa-phone"></i> Mobile Number (মোবাইল নম্বর) *</label>
          <input type="text" id="in-cust-mobile" class="form-control" placeholder="e.g. 01711223344" required>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fa fa-location-dot"></i> Site / Delivery Address (ঠিকানা)</label>
          <textarea id="in-cust-address" class="form-control" rows="2" placeholder="e.g. Holding 45, Hospital Road, Habiganj"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Customer Type</label>
          <select id="in-cust-type" class="form-control">
            <option value="retail">Retail (খুচরা ক্রেতা)</option>
            <option value="contractor">Contractor (ঠিকাদার)</option>
            <option value="builder">Builder / Real Estate (বিল্ডার)</option>
            <option value="dealer">Sub-Dealer (ডিলার)</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('new-customer-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary" id="btn-save-inline-cust"><i class="fa fa-save"></i> Save & Select</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
async function handleInlineCustomerSubmit() {
  const name = document.getElementById('in-cust-name').value.trim();
  const mobile = document.getElementById('in-cust-mobile').value.trim();
  const address = document.getElementById('in-cust-address').value.trim();
  const customer_type = document.getElementById('in-cust-type').value;

  if (!name || !mobile) {
    showToast('Customer Name and Mobile are required.', 'warning');
    return;
  }

  const btn = document.getElementById('btn-save-inline-cust');
  btn.disabled = true;

  try {
    const data = await apiRequest(`${BASE_URL}/ajax/customers.php?action=create_inline`, 'POST', {
      name, mobile, address, customer_type
    });

    if (data.success && data.customer) {
      closeModal('new-customer-modal');
      selectCustomer(data.customer);
      document.getElementById('inline-customer-form').reset();
    } else {
      showToast(data.message || 'Failed to save customer.', 'error');
    }
  } catch(e) {
    // handled
  } finally {
    btn.disabled = false;
  }
}
</script>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
