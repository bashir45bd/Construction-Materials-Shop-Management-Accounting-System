<?php
/**
 * Product Master Catalog & Management
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\StockEngineService;

Auth::requireLogin();
Auth::requirePermission('inventory.view');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

// Handle Product Add / Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::hasPermission('inventory.manage')) {
    Csrf::verifyOrDie();
    $productId = (int)($_POST['product_id'] ?? 0);
    $name      = cleanInput($_POST['name'] ?? '');
    $sku       = cleanInput($_POST['sku'] ?? '');
    $catId     = (int)($_POST['category_id'] ?? 0);
    $brandId   = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $unitId    = (int)($_POST['base_unit_id'] ?? 1);
    $buyPrice  = (float)($_POST['purchase_price'] ?? 0.0);
    $sellPrice = (float)($_POST['selling_price'] ?? 0.0);
    $minSell   = (float)($_POST['min_selling_price'] ?? 0.0);
    $minStock  = (float)($_POST['min_stock'] ?? 0.0);
    $openingQty = (float)($_POST['opening_stock'] ?? 0.0);

    if (!empty($name) && !empty($sku) && $catId > 0) {
        if ($productId > 0) {
            // Update
            $stmt = $pdo->prepare("UPDATE products SET 
                name = :name, sku = :sku, category_id = :cid, brand_id = :bid, 
                base_unit_id = :uid, purchase_price = :pprice, selling_price = :sprice, 
                min_selling_price = :msell, min_stock = :mstock, updated_at = NOW() 
                WHERE id = :id AND shop_id = :s");
            $stmt->execute([
                'name'   => $name,
                'sku'    => $sku,
                'cid'    => $catId,
                'bid'    => $brandId,
                'uid'    => $unitId,
                'pprice' => $buyPrice,
                'sprice' => $sellPrice,
                'msell'  => $minSell,
                'mstock' => $minStock,
                'id'     => $productId,
                's'      => $shopId
            ]);
            flash('success', "Product \"{$name}\" updated successfully.");
        } else {
            // Create
            $stmt = $pdo->prepare("INSERT INTO products 
                (shop_id, category_id, brand_id, base_unit_id, sku, name, purchase_price, selling_price, min_selling_price, current_stock, min_stock, average_cost, is_active)
                VALUES (:s, :cid, :bid, :uid, :sku, :name, :pprice, :sprice, :msell, 0.0000, :mstock, :cost, 1)");
            $stmt->execute([
                's'      => $shopId,
                'cid'    => $catId,
                'bid'    => $brandId,
                'uid'    => $unitId,
                'sku'    => $sku,
                'name'   => $name,
                'pprice' => $buyPrice,
                'sprice' => $sellPrice,
                'msell'  => $minSell,
                'mstock' => $minStock,
                'cost'   => $buyPrice
            ]);
            $newId = (int)$pdo->lastInsertId();

            if ($openingQty > 0) {
                StockEngineService::recordMovement(
                    $newId,
                    'OPENING_STOCK',
                    $openingQty,
                    $unitId,
                    $buyPrice,
                    'OPENING_STOCK',
                    $newId,
                    'OP-STOCK-' . $newId,
                    $userId,
                    'Initial opening stock',
                    $shopId
                );
            }
            flash('success', "Product \"{$name}\" created successfully.");
        }
        redirect(BASE_URL . '/modules/inventory/products.php');
    }
}

// Fetch Dropdowns
$categories = $pdo->query("SELECT id, name FROM categories WHERE shop_id = {$shopId} AND is_active = 1 ORDER BY name ASC")->fetchAll();
$brands = $pdo->query("SELECT id, name FROM brands WHERE shop_id = {$shopId} AND is_active = 1 ORDER BY name ASC")->fetchAll();
$units = $pdo->query("SELECT id, name, symbol FROM units WHERE shop_id = {$shopId} ORDER BY name ASC")->fetchAll();

// Search Filter
$search = cleanInput($_GET['q'] ?? '');
$catFilter = (int)($_GET['category_id'] ?? 0);

$sql = "SELECT p.*, c.name as category_name, b.name as brand_name, u.name as unit_name, u.symbol as unit_symbol
        FROM products p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        JOIN units u ON p.base_unit_id = u.id
        WHERE p.shop_id = :s";
$params = ['s' => $shopId];

if (!empty($search)) {
    $sql .= " AND (p.name LIKE :q OR p.sku LIKE :q)";
    $params['q'] = "%{$search}%";
}
if ($catFilter > 0) {
    $sql .= " AND p.category_id = :cid";
    $params['cid'] = $catFilter;
}

$sql .= " ORDER BY p.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Products & Materials Master';
$activeMenu = 'inventory';
$activeSub = 'products';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-boxes-stacked"></i> Products & Construction Materials (মালামাল তালিকা)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Manage products, buying/selling rates, units and minimum stock limits.</p>
    </div>
    <?php if (Auth::hasPermission('inventory.manage')): ?>
      <button type="button" class="btn btn-primary btn-sm" onclick="openProductModal()">
        <i class="fa fa-plus"></i> + Add New Product
      </button>
    <?php endif; ?>
  </div>

  <!-- Search & Filter Form -->
  <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);">
    <input type="text" name="q" class="form-control form-control-sm" style="max-width:260px;" placeholder="Search SKU or Name..." value="<?= e($search) ?>">
    
    <select name="category_id" class="form-control form-control-sm" style="max-width:180px;">
      <option value="0">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $catFilter == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-secondary btn-sm"><i class="fa fa-filter"></i> Search</button>
    <a href="<?= BASE_URL ?>/modules/inventory/products.php" class="btn btn-secondary btn-sm">Reset</a>
  </form>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>SKU</th>
          <th>Product Name</th>
          <th>Category / Brand</th>
          <th>Unit</th>
          <th>Purchase Rate</th>
          <th>Selling Rate</th>
          <th>Current Stock</th>
          <th>Stock Status</th>
          <?php if (Auth::hasPermission('inventory.manage')): ?>
            <th style="text-align:center;">Action</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:2.5rem;">No products found.</td></tr>
        <?php else: ?>
          <?php foreach ($products as $p): ?>
            <tr>
              <td style="font-weight:700;font-size:0.85rem;color:var(--text-muted);"><?= e($p['sku']) ?></td>
              <td style="font-weight:700;"><?= e($p['name']) ?></td>
              <td>
                <div style="font-size:0.85rem;"><?= e($p['category_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($p['brand_name'] ?? 'General') ?></div>
              </td>
              <td><?= e($p['unit_symbol']) ?> (<?= e($p['unit_name']) ?>)</td>
              <td><?= formatMoney($p['purchase_price']) ?></td>
              <td style="font-weight:700;color:var(--primary);"><?= formatMoney($p['selling_price']) ?></td>
              <td style="font-weight:800;"><?= formatQuantity($p['current_stock']) ?> <?= e($p['unit_symbol']) ?></td>
              <td>
                <?php if ((float)$p['current_stock'] <= (float)$p['min_stock']): ?>
                  <span class="badge badge-danger">Low Stock</span>
                <?php else: ?>
                  <span class="badge badge-success">In Stock</span>
                <?php endif; ?>
              </td>
              <?php if (Auth::hasPermission('inventory.manage')): ?>
                <td style="text-align:center;">
                  <button type="button" class="btn btn-secondary btn-sm" onclick='editProduct(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                    <i class="fa fa-edit"></i>
                  </button>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ADD/EDIT PRODUCT MODAL -->
<div class="modal-backdrop" id="product-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;font-size:1.1rem;" id="prod-modal-title"><i class="fa fa-box"></i> Add Product</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('product-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <input type="hidden" name="product_id" id="prod-id" value="0">

        <div class="form-group">
          <label class="form-label">Product Name (পণ্যের নাম) *</label>
          <input type="text" name="name" id="prod-name" class="form-control" placeholder="e.g. Shah Cement Special PCC 50kg" required>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">SKU / Item Code *</label>
            <input type="text" name="sku" id="prod-sku" class="form-control" placeholder="e.g. CEM-SHAH-01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Category *</label>
            <select name="category_id" id="prod-category" class="form-control" required>
              <option value="">Select Category</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Brand</label>
            <select name="brand_id" id="prod-brand" class="form-control">
              <option value="">No Brand / General</option>
              <?php foreach ($brands as $b): ?>
                <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Base Unit *</label>
            <select name="base_unit_id" id="prod-unit" class="form-control" required>
              <?php foreach ($units as $u): ?>
                <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['symbol']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Purchase Price (ক্রয়মূল্য)</label>
            <input type="number" step="any" min="0" name="purchase_price" id="prod-purchase-price" class="form-control" placeholder="0.00">
          </div>
          <div class="form-group">
            <label class="form-label">Selling Price (বিক্রয়মূল্য) *</label>
            <input type="number" step="any" min="0" name="selling_price" id="prod-selling-price" class="form-control" placeholder="0.00" required>
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Min Selling Price</label>
            <input type="number" step="any" min="0" name="min_selling_price" id="prod-min-selling-price" class="form-control" placeholder="0.00">
          </div>
          <div class="form-group">
            <label class="form-label">Low Stock Alert Limit</label>
            <input type="number" step="any" min="0" name="min_stock" id="prod-min-stock" class="form-control" placeholder="10">
          </div>
        </div>

        <div class="form-group" id="opening-stock-group">
          <label class="form-label">Opening Stock Quantity (প্রারম্ভিক মজুদ)</label>
          <input type="number" step="any" min="0" name="opening_stock" id="prod-opening-stock" class="form-control" placeholder="0">
        </div>

        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('product-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Product</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openProductModal() {
  document.getElementById('prod-modal-title').innerHTML = '<i class="fa fa-box"></i> Add New Product';
  document.getElementById('prod-id').value = '0';
  document.getElementById('prod-name').value = '';
  document.getElementById('prod-sku').value = '';
  document.getElementById('prod-category').value = '';
  document.getElementById('prod-brand').value = '';
  document.getElementById('prod-purchase-price').value = '';
  document.getElementById('prod-selling-price').value = '';
  document.getElementById('prod-min-selling-price').value = '';
  document.getElementById('prod-min-stock').value = '10';
  document.getElementById('opening-stock-group').style.display = 'block';
  openModal('product-modal');
}

function editProduct(p) {
  document.getElementById('prod-modal-title').innerHTML = '<i class="fa fa-edit"></i> Edit Product';
  document.getElementById('prod-id').value = p.id;
  document.getElementById('prod-name').value = p.name;
  document.getElementById('prod-sku').value = p.sku;
  document.getElementById('prod-category').value = p.category_id;
  document.getElementById('prod-brand').value = p.brand_id || '';
  document.getElementById('prod-unit').value = p.base_unit_id;
  document.getElementById('prod-purchase-price').value = p.purchase_price;
  document.getElementById('prod-selling-price').value = p.selling_price;
  document.getElementById('prod-min-selling-price').value = p.min_selling_price;
  document.getElementById('prod-min-stock').value = p.min_stock;
  document.getElementById('opening-stock-group').style.display = 'none';
  openModal('product-modal');
}
</script>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
