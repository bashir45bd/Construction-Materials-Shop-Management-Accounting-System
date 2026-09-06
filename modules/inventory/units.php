<?php
/**
 * Measurement Units & Conversion Matrix
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

// Handle New Unit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_unit') {
    Csrf::verifyOrDie();
    $name   = cleanInput($_POST['name'] ?? '');
    $symbol = cleanInput($_POST['symbol'] ?? '');
    $dec    = (int)($_POST['decimal_places'] ?? 2);

    if (!empty($name) && !empty($symbol)) {
        $stmt = $pdo->prepare("INSERT INTO units (shop_id, name, symbol, is_base_unit, decimal_places) VALUES (:s, :n, :sym, 1, :dec)");
        $stmt->execute(['s' => $shopId, 'n' => $name, 'sym' => $symbol, 'dec' => $dec]);
        flash('success', "Unit \"{$name}\" added.");
        redirect(BASE_URL . '/modules/inventory/units.php');
    }
}

// Handle New Conversion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_conversion') {
    Csrf::verifyOrDie();
    $fromUnit = (int)($_POST['from_unit_id'] ?? 0);
    $toUnit   = (int)($_POST['to_unit_id'] ?? 0);
    $factor   = (float)($_POST['conversion_factor'] ?? 1.0);

    if ($fromUnit > 0 && $toUnit > 0 && $fromUnit !== $toUnit && $factor > 0) {
        $stmt = $pdo->prepare("INSERT INTO unit_conversions (shop_id, from_unit_id, to_unit_id, conversion_factor) 
                               VALUES (:s, :from, :to, :fac) 
                               ON DUPLICATE KEY UPDATE conversion_factor = VALUES(conversion_factor)");
        $stmt->execute(['s' => $shopId, 'from' => $fromUnit, 'to' => $toUnit, 'fac' => $factor]);
        flash('success', "Unit conversion factor registered.");
        redirect(BASE_URL . '/modules/inventory/units.php');
    }
}

$units = $pdo->query("SELECT * FROM units WHERE shop_id = {$shopId} ORDER BY name ASC")->fetchAll();

$convStmt = $pdo->prepare("SELECT uc.*, u1.name as from_name, u1.symbol as from_symbol, u2.name as to_name, u2.symbol as to_symbol
                           FROM unit_conversions uc
                           JOIN units u1 ON uc.from_unit_id = u1.id
                           JOIN units u2 ON uc.to_unit_id = u2.id
                           WHERE uc.shop_id = :s");
$convStmt->execute(['s' => $shopId]);
$conversions = $convStmt->fetchAll();

$pageTitle = 'Units & Conversion Matrix';
$activeMenu = 'inventory';
$activeSub = 'units';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:1.5rem;">
  <!-- Units List -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-scale-balanced"></i> Units of Measurement</div>
      <button type="button" class="btn btn-primary btn-sm" onclick="openModal('unit-modal')">+ Add Unit</button>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Unit Name</th>
            <th>Symbol</th>
            <th>Decimals</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($units as $u): ?>
            <tr>
              <td style="font-weight:700;"><?= e($u['name']) ?></td>
              <td><span class="badge badge-info"><?= e($u['symbol']) ?></span></td>
              <td><?= $u['decimal_places'] ?> decimals</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Conversions List -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-arrow-right-arrow-left"></i> Conversion Formulas</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="openModal('conv-modal')">+ Add Formula</button>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>From Unit</th>
            <th>Conversion Multiplier</th>
            <th>To Unit</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($conversions)): ?>
            <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:2rem;">No custom conversion rules configured.</td></tr>
          <?php else: ?>
            <?php foreach ($conversions as $cv): ?>
              <tr>
                <td style="font-weight:700;">1 <?= e($cv['from_symbol']) ?> (<?= e($cv['from_name']) ?>)</td>
                <td style="font-weight:800;color:var(--primary);">= <?= rtrim(rtrim((string)$cv['conversion_factor'], '0'), '.') ?></td>
                <td style="font-weight:700;"><?= e($cv['to_symbol']) ?> (<?= e($cv['to_name']) ?>)</td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Unit Modal -->
<div class="modal-backdrop" id="unit-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;"><i class="fa fa-scale-balanced"></i> Add Unit</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('unit-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="save_unit">
        <div class="form-group">
          <label class="form-label">Unit Name *</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Metric Ton, Bundle, Box" required>
        </div>
        <div class="form-group">
          <label class="form-label">Unit Symbol *</label>
          <input type="text" name="symbol" class="form-control" placeholder="e.g. Ton, Bdl, Box" required>
        </div>
        <div class="form-group">
          <label class="form-label">Decimal Places</label>
          <input type="number" name="decimal_places" class="form-control" value="2" min="0" max="4">
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('unit-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Unit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Conversion Modal -->
<div class="modal-backdrop" id="conv-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;"><i class="fa fa-arrow-right-arrow-left"></i> Add Conversion Multiplier</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('conv-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="save_conversion">
        <div class="form-group">
          <label class="form-label">1 From Unit *</label>
          <select name="from_unit_id" class="form-control" required>
            <?php foreach ($units as $u): ?>
              <option value="<?= $u['id'] ?>">1 <?= e($u['name']) ?> (<?= e($u['symbol']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Multiplication Factor *</label>
          <input type="number" step="any" min="0.000001" name="conversion_factor" class="form-control" placeholder="e.g. 1000 for Ton to KG" required>
        </div>
        <div class="form-group">
          <label class="form-label">Equals To Unit *</label>
          <select name="to_unit_id" class="form-control" required>
            <?php foreach ($units as $u): ?>
              <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['symbol']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('conv-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Formula</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
