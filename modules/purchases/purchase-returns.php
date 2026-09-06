<?php
/**
 * Purchase Returns to Suppliers (ক্রয় ফেরত)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\StockEngineService;
use App\Services\SupplierLedgerService;
use App\Services\DocumentNumberService;

Auth::requireLogin();
Auth::requirePermission('purchase.return');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

// Handle Purchase Return Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $supplierId = (int)($_POST['supplier_id'] ?? 0);
    $productId  = (int)($_POST['product_id'] ?? 0);
    $quantity   = (float)($_POST['quantity'] ?? 0.0);
    $unitCost   = (float)($_POST['unit_cost'] ?? 0.0);
    $reason     = cleanInput($_POST['reason'] ?? 'Damaged Return');

    if ($supplierId > 0 && $productId > 0 && $quantity > 0) {
        DB::transaction(function($pdo) use ($supplierId, $productId, $quantity, $unitCost, $reason, $userId, $shopId) {
            $returnNo = DocumentNumberService::getNextNumber('PURCHASE_RETURN', $shopId);
            $totalAmount = $quantity * $unitCost;
            $today = date('Y-m-d');

            // Product Base Unit
            $pStmt = $pdo->prepare("SELECT base_unit_id FROM products WHERE id = :id FOR UPDATE");
            $pStmt->execute(['id' => $productId]);
            $prod = $pStmt->fetch();
            $baseUnitId = (int)$prod['base_unit_id'];

            // 1. Stock Out
            StockEngineService::recordMovement(
                $productId,
                'PURCHASE_RETURN',
                $quantity,
                $baseUnitId,
                $unitCost,
                'PURCHASE_RETURN',
                null,
                $returnNo,
                $userId,
                "Material returned to supplier - {$reason}",
                $shopId
            );

            // 2. Adjust Supplier Ledger (Debit: Reduce Payable)
            SupplierLedgerService::recordEntry(
                $supplierId,
                'PURCHASE_RETURN',
                'PURCHASE_RETURN',
                null,
                $returnNo,
                "Purchase Return [{$returnNo}] - {$reason}",
                $totalAmount,
                0.00,
                $userId,
                $today,
                $shopId
            );
        });

        flash('success', "Purchase return processed and supplier ledger adjusted.");
        redirect(BASE_URL . '/modules/purchases/purchase-returns.php');
    }
}

$suppliers = $pdo->query("SELECT id, name, company_name FROM suppliers WHERE shop_id = {$shopId} AND status = 'active'")->fetchAll();
$products = $pdo->query("SELECT id, name, purchase_price, (SELECT symbol FROM units WHERE id = products.base_unit_id) as unit_symbol FROM products WHERE shop_id = {$shopId} AND is_active = 1")->fetchAll();

$pageTitle = 'Purchase Returns to Supplier (ক্রয় ফেরত)';
$activeMenu = 'purchases';
$activeSub = 'purchase-returns';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card" style="max-width:750px;margin:0 auto;">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-rotate-left"></i> Return Materials to Supplier (মহাজনকে ফেরত)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Deduct warehouse inventory and reduce outstanding supplier payable debt.</p>
    </div>
  </div>

  <form method="POST" action="">
    <?= Csrf::field() ?>
    <div class="form-group">
      <label class="form-label">Supplier / Distributor *</label>
      <select name="supplier_id" class="form-control" required>
        <option value="">-- Choose Supplier --</option>
        <?php foreach ($suppliers as $s): ?>
          <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['company_name'] ?: 'Vendor') ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Material Product *</label>
      <select name="product_id" id="ret-prod-select" class="form-control" onchange="document.getElementById('ret-cost-input').value = this.options[this.selectedIndex].getAttribute('data-cost')" required>
        <option value="">-- Choose Product --</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= $p['id'] ?>" data-cost="<?= $p['purchase_price'] ?>"><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div class="form-group">
        <label class="form-label">Return Quantity *</label>
        <input type="number" step="any" min="0.01" name="quantity" class="form-control" placeholder="e.g. 20" required>
      </div>
      <div class="form-group">
        <label class="form-label">Unit Cost Rate *</label>
        <input type="number" step="any" min="0" name="unit_cost" id="ret-cost-input" class="form-control" placeholder="0.00" required>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Reason for Return *</label>
      <input type="text" name="reason" class="form-control" placeholder="e.g. Hardened cement bags, defective rod batch" required>
    </div>

    <button type="submit" class="btn btn-danger" style="width:100%;margin-top:0.5rem;">
      <i class="fa fa-rotate-left"></i> Confirm Return & Deduct Stock
    </button>
  </form>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
