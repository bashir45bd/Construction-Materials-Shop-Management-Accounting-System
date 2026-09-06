<?php
/**
 * Product Brands Management
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;

Auth::requireLogin();
Auth::requirePermission('inventory.manage');

$shopId = Auth::shopId();
$pdo = DB::pdo();

// Handle Add / Edit Brand
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $brandId = (int)($_POST['brand_id'] ?? 0);
    $name    = cleanInput($_POST['name'] ?? '');
    $code    = cleanInput($_POST['code'] ?? '');
    $country = cleanInput($_POST['origin_country'] ?? 'Bangladesh');

    if (!empty($name)) {
        if ($brandId > 0) {
            $stmt = $pdo->prepare("UPDATE brands SET name = :name, code = :code, origin_country = :country, updated_at = NOW() WHERE id = :id AND shop_id = :s");
            $stmt->execute(['name' => $name, 'code' => $code, 'country' => $country, 'id' => $brandId, 's' => $shopId]);
            flash('success', "Brand \"{$name}\" updated successfully.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO brands (shop_id, name, code, origin_country, is_active) VALUES (:s, :name, :code, :country, 1)");
            $stmt->execute(['s' => $shopId, 'name' => $name, 'code' => $code, 'country' => $country]);
            flash('success', "Brand \"{$name}\" created successfully.");
        }
        redirect(BASE_URL . '/modules/inventory/brands.php');
    }
}

$stmt = $pdo->prepare("SELECT b.*, COUNT(p.id) as product_count 
                       FROM brands b 
                       LEFT JOIN products p ON b.id = p.brand_id AND p.is_active = 1
                       WHERE b.shop_id = :s 
                       GROUP BY b.id ORDER BY b.name ASC");
$stmt->execute(['s' => $shopId]);
$brands = $stmt->fetchAll();

$pageTitle = 'Brands Master';
$activeMenu = 'inventory';
$activeSub = 'brands';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-copyright"></i> Manufacturer Brands (ব্র্যান্ড)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Manage cement companies, steel rebar mills, and material manufacturers.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openBrandModal()">
      <i class="fa fa-plus"></i> + Add Brand
    </button>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Brand Code</th>
          <th>Brand Name</th>
          <th>Origin</th>
          <th>Products Count</th>
          <th style="text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($brands as $b): ?>
          <tr>
            <td style="font-weight:700;color:var(--text-muted);"><?= e($b['code'] ?: 'BRD-' . $b['id']) ?></td>
            <td style="font-weight:700;"><?= e($b['name']) ?></td>
            <td><?= e($b['origin_country'] ?: 'Bangladesh') ?></td>
            <td><span class="badge badge-info"><?= $b['product_count'] ?> Products</span></td>
            <td style="text-align:center;">
              <button type="button" class="btn btn-secondary btn-sm" onclick='editBrand(<?= json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                <i class="fa fa-edit"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-backdrop" id="brand-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;font-size:1.1rem;" id="brd-modal-title"><i class="fa fa-copyright"></i> Add Brand</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('brand-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <input type="hidden" name="brand_id" id="brd-id" value="0">
        <div class="form-group">
          <label class="form-label">Brand Name *</label>
          <input type="text" name="name" id="brd-name" class="form-control" placeholder="e.g. Shah Cement, BSRM" required>
        </div>
        <div class="form-group">
          <label class="form-label">Brand Code</label>
          <input type="text" name="code" id="brd-code" class="form-control" placeholder="e.g. BRD-SHAH">
        </div>
        <div class="form-group">
          <label class="form-label">Country of Origin</label>
          <input type="text" name="origin_country" id="brd-country" class="form-control" value="Bangladesh">
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('brand-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Brand</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openBrandModal() {
  document.getElementById('brd-modal-title').innerHTML = '<i class="fa fa-copyright"></i> Add Brand';
  document.getElementById('brd-id').value = '0';
  document.getElementById('brd-name').value = '';
  document.getElementById('brd-code').value = '';
  document.getElementById('brd-country').value = 'Bangladesh';
  openModal('brand-modal');
}
function editBrand(b) {
  document.getElementById('brd-modal-title').innerHTML = '<i class="fa fa-edit"></i> Edit Brand';
  document.getElementById('brd-id').value = b.id;
  document.getElementById('brd-name').value = b.name;
  document.getElementById('brd-code').value = b.code || '';
  document.getElementById('brd-country').value = b.origin_country || 'Bangladesh';
  openModal('brand-modal');
}
</script>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
