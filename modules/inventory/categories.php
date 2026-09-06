<?php
/**
 * Product Categories Management
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

// Handle Add / Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $catId = (int)($_POST['category_id'] ?? 0);
    $name  = cleanInput($_POST['name'] ?? '');
    $code  = cleanInput($_POST['code'] ?? '');
    $desc  = cleanInput($_POST['description'] ?? '');

    if (!empty($name)) {
        if ($catId > 0) {
            $stmt = $pdo->prepare("UPDATE categories SET name = :name, code = :code, description = :desc, updated_at = NOW() WHERE id = :id AND shop_id = :s");
            $stmt->execute(['name' => $name, 'code' => $code, 'desc' => $desc, 'id' => $catId, 's' => $shopId]);
            flash('success', "Category \"{$name}\" updated successfully.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (shop_id, name, code, description, is_active) VALUES (:s, :name, :code, :desc, 1)");
            $stmt->execute(['s' => $shopId, 'name' => $name, 'code' => $code, 'desc' => $desc]);
            flash('success', "Category \"{$name}\" added successfully.");
        }
        redirect(BASE_URL . '/modules/inventory/categories.php');
    }
}

$stmt = $pdo->prepare("SELECT c.*, COUNT(p.id) as product_count 
                       FROM categories c 
                       LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                       WHERE c.shop_id = :s 
                       GROUP BY c.id ORDER BY c.name ASC");
$stmt->execute(['s' => $shopId]);
$categories = $stmt->fetchAll();

$pageTitle = 'Product Categories';
$activeMenu = 'inventory';
$activeSub = 'categories';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-tags"></i> Product Categories (ক্যাটাগরি)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Organize construction materials into manageable categories.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openCategoryModal()">
      <i class="fa fa-plus"></i> + Add Category
    </button>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Category Code</th>
          <th>Category Name</th>
          <th>Description</th>
          <th>Active Products</th>
          <th style="text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
          <tr>
            <td style="font-weight:700;color:var(--text-muted);"><?= e($c['code'] ?: 'CAT-' . $c['id']) ?></td>
            <td style="font-weight:700;font-size:0.95rem;"><?= e($c['name']) ?></td>
            <td style="color:var(--text-secondary);"><?= e($c['description'] ?: '-') ?></td>
            <td><span class="badge badge-info"><?= $c['product_count'] ?> Products</span></td>
            <td style="text-align:center;">
              <button type="button" class="btn btn-secondary btn-sm" onclick='editCategory(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                <i class="fa fa-edit"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Category Modal -->
<div class="modal-backdrop" id="category-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;font-size:1.1rem;" id="cat-modal-title"><i class="fa fa-tags"></i> Add Category</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('category-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <input type="hidden" name="category_id" id="cat-id" value="0">
        <div class="form-group">
          <label class="form-label">Category Name *</label>
          <input type="text" name="name" id="cat-name" class="form-control" placeholder="e.g. Cement, Rod, Sand" required>
        </div>
        <div class="form-group">
          <label class="form-label">Category Code</label>
          <input type="text" name="code" id="cat-code" class="form-control" placeholder="e.g. CAT-CEM">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="cat-desc" class="form-control" rows="2" placeholder="Brief description"></textarea>
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('category-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openCategoryModal() {
  document.getElementById('cat-modal-title').innerHTML = '<i class="fa fa-tags"></i> Add Category';
  document.getElementById('cat-id').value = '0';
  document.getElementById('cat-name').value = '';
  document.getElementById('cat-code').value = '';
  document.getElementById('cat-desc').value = '';
  openModal('category-modal');
}
function editCategory(c) {
  document.getElementById('cat-modal-title').innerHTML = '<i class="fa fa-edit"></i> Edit Category';
  document.getElementById('cat-id').value = c.id;
  document.getElementById('cat-name').value = c.name;
  document.getElementById('cat-code').value = c.code || '';
  document.getElementById('cat-desc').value = c.description || '';
  openModal('category-modal');
}
</script>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
