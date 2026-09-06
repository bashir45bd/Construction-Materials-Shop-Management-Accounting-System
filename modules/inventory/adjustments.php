<?php
/**
 * Physical Stock Adjustments (Damage / Loss / Reconciliation)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\StockEngineService;
use App\Services\DocumentNumberService;

Auth::requireLogin();
Auth::requirePermission('inventory.adjust');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $productId = (int)($_POST['product_id'] ?? 0);
    $adjType   = cleanInput($_POST['adjustment_type'] ?? 'OUT'); // 'IN' or 'OUT'
    $quantity  = (float)($_POST['quantity'] ?? 0.0);
    $reason    = cleanInput($_POST['reason_code'] ?? 'damaged');
    $notes     = cleanInput($_POST['notes'] ?? '');

    if ($productId > 0 && $quantity > 0) {
        DB::transaction(function($pdo) use ($productId, $adjType, $quantity, $reason, $notes, $userId, $shopId) {
            $adjNo = DocumentNumberService::getNextNumber('ADJUSTMENT', $shopId);
            $today = date('Y-m-d');

            // Product Details
            $pStmt = $pdo->prepare("SELECT base_unit_id, average_cost FROM products WHERE id = :id FOR UPDATE");
            $pStmt->execute(['id' => $productId]);
            $prod = $pStmt->fetch();

            $baseUnitId = (int)$prod['base_unit_id'];
            $unitCost = (float)$prod['average_cost'];

            // Insert adjustment header
            $stmt = $pdo->prepare("INSERT INTO stock_adjustments (shop_id, adjustment_no, adjustment_date, reason_code, notes, created_by) VALUES (:s, :no, :d, :r, :n, :uid)");
            $stmt->execute(['s' => $shopId, 'no' => $adjNo, 'd' => $today, 'r' => $reason, 'n' => $notes, 'uid' => $userId]);
            $adjId = (int)$pdo->lastInsertId();

            // Insert adjustment item
            $stmtItem = $pdo->prepare("INSERT INTO stock_adjustment_items (adjustment_id, product_id, adjustment_type, quantity, base_quantity, unit_id, unit_cost) VALUES (:aid, :pid, :atype, :qty, :bqty, :uid, :cost)");
            $stmtItem->execute(['aid' => $adjId, 'pid' => $productId, 'atype' => $adjType, 'qty' => $quantity, 'bqty' => $quantity, 'uid' => $baseUnitId, 'cost' => $unitCost]);

            // Stock Movement
            $movType = ($adjType === 'IN') ? 'ADJUSTMENT_IN' : 'ADJUSTMENT_OUT';
            StockEngineService::recordMovement(
                $productId,
                $movType,
                $quantity,
                $baseUnitId,
                $unitCost,
                'STOCK_ADJUSTMENT',
                $adjId,
                $adjNo,
                $userId,
                "Stock Adjustment ({$reason}) - {$notes}",
                $shopId
            );
        });

        flash('success', "Stock adjustment processed successfully.");
        redirect(BASE_URL . '/modules/inventory/adjustments.php');
    }
}

$products = $pdo->query("SELECT id, name, sku, current_stock, (SELECT symbol FROM units WHERE id = products.base_unit_id) as unit_symbol FROM products WHERE shop_id = {$shopId} AND is_active = 1 ORDER BY name ASC")->fetchAll();

$adjHistory = $pdo->query("SELECT sa.*, sai.product_id, sai.adjustment_type, sai.quantity, p.name as product_name, u.symbol as unit_symbol, usr.full_name as created_by_name
                           FROM stock_adjustments sa
                           JOIN stock_adjustment_items sai ON sa.id = sai.adjustment_id
                           JOIN products p ON sai.product_id = p.id
                           JOIN units u ON sai.unit_id = u.id
                           JOIN users usr ON sa.created_by = usr.id
                           WHERE sa.shop_id = {$shopId}
                           ORDER BY sa.id DESC LIMIT 50")->fetchAll();

$pageTitle = 'Stock Adjustments';
$activeMenu = 'inventory';
$activeSub = 'adjustments';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:1.5rem;">
  <!-- Adjustment Form -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-sliders"></i> Adjust Product Stock</div>
    </div>

    <form method="POST" action="">
      <?= Csrf::field() ?>
      <div class="form-group">
        <label class="form-label">Select Product *</label>
        <select name="product_id" class="form-control" required>
          <option value="">-- Choose Material --</option>
          <?php foreach ($products as $pr): ?>
            <option value="<?= $pr['id'] ?>">
              <?= e($pr['name']) ?> (Current: <?= formatQuantity($pr['current_stock']) ?> <?= e($pr['unit_symbol']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
        <div class="form-group">
          <label class="form-label">Adjustment Direction *</label>
          <select name="adjustment_type" class="form-control">
            <option value="OUT">Stock Decrease / Deduction (-)</option>
            <option value="IN">Stock Increase / Surplus (+)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Quantity *</label>
          <input type="number" step="any" min="0.01" name="quantity" class="form-control" placeholder="e.g. 5" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Reason Code *</label>
        <select name="reason_code" class="form-control">
          <option value="damaged">Damaged / Broken / Hardened Cement (নষ্ট/ক্ষতিগ্রস্ত)</option>
          <option value="lost">Lost / Missing / Stolen (হারিয়ে যাওয়া)</option>
          <option value="counting_difference">Physical Count Difference (গণনায় পার্থক্য)</option>
          <option value="opening_correction">Opening Stock Correction (প্রারম্ভিক সংশোধন)</option>
          <option value="other">Other Miscellaneous</option>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Detailed Notes / Remarks</label>
        <textarea name="notes" class="form-control" rows="2" placeholder="Explain the reason for this manual adjustment"></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">
        <i class="fa fa-check"></i> Post Stock Adjustment
      </button>
    </form>
  </div>

  <!-- Recent Adjustments History -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-history"></i> Adjustment History</div>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Voucher</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($adjHistory)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">No adjustments recorded.</td></tr>
          <?php else: ?>
            <?php foreach ($adjHistory as $h): ?>
              <tr>
                <td style="font-size:0.8rem;"><?= formatDate($h['adjustment_date']) ?></td>
                <td style="font-weight:700;"><?= e($h['adjustment_no']) ?></td>
                <td style="font-weight:600;"><?= e($h['product_name']) ?></td>
                <td style="font-weight:800;color:<?= $h['adjustment_type'] === 'IN' ? 'var(--success)' : 'var(--danger)' ?>;">
                  <?= $h['adjustment_type'] === 'IN' ? '+' : '-' ?><?= formatQuantity($h['quantity']) ?> <?= e($h['unit_symbol']) ?>
                </td>
                <td><span class="badge badge-secondary"><?= ucfirst(str_replace('_', ' ', $h['reason_code'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
