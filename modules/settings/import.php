<?php
/**
 * Bulk CSV Data Migration & Importer
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;
use App\Services\StockEngineService;
use App\Services\CustomerLedgerService;

Auth::requireLogin();
Auth::requirePermission('settings.manage');

$shopId = Auth::shopId();
$userId = (int)Auth::id();
$pdo = DB::pdo();

$importSuccess = 0;
$importErrors = [];

// Handle CSV Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    Csrf::verifyOrDie();
    $type = cleanInput($_POST['import_type'] ?? 'products');
    $file = $_FILES['csv_file']['tmp_name'] ?? '';

    if (!empty($file) && is_uploaded_file($file)) {
        $handle = fopen($file, 'r');
        if ($handle !== false) {
            $header = fgetcsv($handle); // skip header row

            if ($type === 'products') {
                // Format: SKU, Name, CategoryName, BaseUnitSymbol, PurchasePrice, SellingPrice, OpeningStock
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 6) continue;
                    $sku       = cleanInput($row[0] ?? '');
                    $name      = cleanInput($row[1] ?? '');
                    $catName   = cleanInput($row[2] ?? 'General');
                    $unitSym   = cleanInput($row[3] ?? 'Piece');
                    $buyPrice  = (float)($row[4] ?? 0.0);
                    $sellPrice = (float)($row[5] ?? 0.0);
                    $stock     = (float)($row[6] ?? 0.0);

                    if (empty($sku) || empty($name)) continue;

                    try {
                        // Category lookup/create
                        $catStmt = $pdo->prepare("SELECT id FROM categories WHERE shop_id = :s AND name = :n LIMIT 1");
                        $catStmt->execute(['s' => $shopId, 'n' => $catName]);
                        $catId = $catStmt->fetchColumn();
                        if (!$catId) {
                            $pdo->prepare("INSERT INTO categories (shop_id, name, is_active) VALUES (:s, :n, 1)")->execute(['s' => $shopId, 'n' => $catName]);
                            $catId = $pdo->lastInsertId();
                        }

                        // Unit lookup/create
                        $unitStmt = $pdo->prepare("SELECT id FROM units WHERE shop_id = :s AND symbol = :sym LIMIT 1");
                        $unitStmt->execute(['s' => $shopId, 'sym' => $unitSym]);
                        $unitId = $unitStmt->fetchColumn();
                        if (!$unitId) {
                            $pdo->prepare("INSERT INTO units (shop_id, name, symbol, is_base_unit) VALUES (:s, :n, :sym, 1)")->execute(['s' => $shopId, 'n' => $unitSym, 'sym' => $unitSym]);
                            $unitId = $pdo->lastInsertId();
                        }

                        // Insert product
                        $insProd = $pdo->prepare("INSERT INTO products (shop_id, category_id, base_unit_id, sku, name, purchase_price, selling_price, current_stock, min_stock, average_cost, is_active)
                            VALUES (:s, :cid, :uid, :sku, :name, :pprice, :sprice, 0.0, 10.0, :cost, 1)
                            ON DUPLICATE KEY UPDATE selling_price = VALUES(selling_price), purchase_price = VALUES(purchase_price)");
                        $insProd->execute(['s' => $shopId, 'cid' => $catId, 'uid' => $unitId, 'sku' => $sku, 'name' => $name, 'pprice' => $buyPrice, 'sprice' => $sellPrice, 'cost' => $buyPrice]);
                        $prodId = (int)$pdo->lastInsertId();

                        if ($stock > 0 && $prodId > 0) {
                            StockEngineService::recordMovement($prodId, 'OPENING_STOCK', $stock, (int)$unitId, $buyPrice, 'CSV_IMPORT', $prodId, 'IMP-' . $sku, $userId, 'CSV Bulk Import', $shopId);
                        }

                        $importSuccess++;
                    } catch (\Throwable $e) {
                        $importErrors[] = "Error importing row {$sku}: " . $e->getMessage();
                    }
                }
            } elseif ($type === 'customers') {
                // Format: CustomerCode, Name, Mobile, Address, OpeningDue
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 3) continue;
                    $code   = cleanInput($row[0] ?? '');
                    $name   = cleanInput($row[1] ?? '');
                    $mobile = cleanInput($row[2] ?? '');
                    $addr   = cleanInput($row[3] ?? '');
                    $due    = (float)($row[4] ?? 0.0);

                    if (empty($name) || empty($mobile)) continue;
                    if (empty($code)) $code = 'CUST-' . rand(1000, 9999);

                    try {
                        $insCust = $pdo->prepare("INSERT INTO customers (shop_id, customer_code, name, mobile, address, opening_due, opening_advance, current_due, current_advance, status)
                            VALUES (:s, :code, :name, :mob, :addr, :due, 0.0, :due, 0.0, 'active')
                            ON DUPLICATE KEY UPDATE name = VALUES(name), address = VALUES(address)");
                        $insCust->execute(['s' => $shopId, 'code' => $code, 'name' => $name, 'mob' => $mobile, 'addr' => $addr, 'due' => $due]);
                        $cId = (int)$pdo->lastInsertId();

                        if ($due > 0 && $cId > 0) {
                            CustomerLedgerService::recordEntry($cId, 'OPENING_BALANCE', 'CSV_IMPORT', $cId, 'IMP-' . $code, 'CSV Import Opening Due', $due, 0.0, $userId, date('Y-m-d'), $shopId);
                        }

                        $importSuccess++;
                    } catch (\Throwable $e) {
                        $importErrors[] = "Error importing customer {$name}: " . $e->getMessage();
                    }
                }
            }
            fclose($handle);
            flash('success', "Bulk migration completed: {$importSuccess} records imported successfully.");
        }
    }
}

$pageTitle = 'CSV Bulk Data Migration';
$activeMenu = 'settings';
$activeSub = 'import';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card" style="max-width:800px;margin:0 auto;">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-file-csv"></i> Bulk CSV Data Migration (ডাটা ইমপোর্ট)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Quickly import existing customer ledgers, price lists, and inventory stocks via CSV.</p>
    </div>
  </div>

  <?php if (!empty($importErrors)): ?>
    <div style="background:var(--danger-light);color:var(--danger);padding:1rem;border-radius:var(--radius);margin-bottom:1rem;font-size:0.85rem;">
      <div style="font-weight:700;">Import warnings:</div>
      <ul>
        <?php foreach ($importErrors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="" enctype="multipart/form-data">
    <?= Csrf::field() ?>

    <div class="form-group">
      <label class="form-label">Select Data Type *</label>
      <select name="import_type" class="form-control" required>
        <option value="products">Materials & Products Catalog (SKU, Name, Category, Unit, BuyingRate, SellingRate, Stock)</option>
        <option value="customers">Customers Directory & Opening Dues (Code, Name, Mobile, Address, OpeningDue)</option>
      </select>
    </div>

    <div class="form-group">
      <label class="form-label">Upload CSV File (.csv) *</label>
      <input type="file" name="csv_file" class="form-control" accept=".csv" required>
    </div>

    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;margin-top:1rem;">
      <i class="fa fa-upload"></i> Process CSV Import
    </button>
  </form>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
