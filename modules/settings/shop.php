<?php
/**
 * Shop Information & Invoice Configuration
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;

Auth::requireLogin();
Auth::requirePermission('settings.manage');

$shopId = Auth::shopId();
$pdo = DB::pdo();

// Handle Shop Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $name         = cleanInput($_POST['name'] ?? '');
    $proprietor   = cleanInput($_POST['proprietor'] ?? '');
    $address      = cleanInput($_POST['address'] ?? '');
    $phone        = cleanInput($_POST['phone'] ?? '');
    $altPhone     = cleanInput($_POST['alt_phone'] ?? '');
    $email        = cleanInput($_POST['email'] ?? '');
    $website      = cleanInput($_POST['website'] ?? '');
    $invPrefix    = cleanInput($_POST['invoice_prefix'] ?? 'MT');
    $currSymbol   = cleanInput($_POST['currency_symbol'] ?? '৳');
    $dateFormat   = cleanInput($_POST['date_format'] ?? 'd-m-Y');
    $footerShop   = cleanInput($_POST['invoice_footer_shop'] ?? '');
    $footerCust   = cleanInput($_POST['invoice_footer_customer'] ?? '');

    if (!empty($name) && !empty($phone)) {
        $stmt = $pdo->prepare("UPDATE shops SET 
            name = :name, proprietor = :prop, address = :addr, phone = :phone, 
            alt_phone = :altp, email = :email, website = :web, invoice_prefix = :prefix, 
            currency_symbol = :sym, date_format = :dformat, 
            invoice_footer_shop = :fshop, invoice_footer_customer = :fcust, updated_at = NOW() 
            WHERE id = :id");
        
        $stmt->execute([
            'name'   => $name,
            'prop'   => $proprietor,
            'addr'   => $address,
            'phone'  => $phone,
            'altp'   => $altPhone,
            'email'  => $email,
            'web'    => $website,
            'prefix' => $invPrefix,
            'sym'    => $currSymbol,
            'dformat'=> $dateFormat,
            'fshop'  => $footerShop,
            'fcust'  => $footerCust,
            'id'     => $shopId
        ]);

        flash('success', 'Shop settings and invoice configurations updated successfully.');
        redirect(BASE_URL . '/modules/settings/shop.php');
    }
}

$shop = getShop($shopId);

$pageTitle = 'Shop Settings & Preferences';
$activeMenu = 'settings';
$activeSub = 'shop-settings';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card" style="max-width:900px;margin:0 auto;">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-gear"></i> Shop Settings & Invoice Configuration</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Configure shop branding, memo prefixes, currency symbols, and invoice footers.</p>
    </div>
  </div>

  <form method="POST" action="">
    <?= Csrf::field() ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div class="form-group">
        <label class="form-label">Shop / Enterprise Name *</label>
        <input type="text" name="name" class="form-control" value="<?= e($shop['name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Proprietor Name</label>
        <input type="text" name="proprietor" class="form-control" value="<?= e($shop['proprietor'] ?? '') ?>" placeholder="e.g. Md. Maruf Hasan">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Shop Address (ঠিকানা) *</label>
      <textarea name="address" class="form-control" rows="2" required><?= e($shop['address']) ?></textarea>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div class="form-group">
        <label class="form-label">Primary Mobile Phone *</label>
        <input type="text" name="phone" class="form-control" value="<?= e($shop['phone']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Alternative Phone</label>
        <input type="text" name="alt_phone" class="form-control" value="<?= e($shop['alt_phone'] ?? '') ?>">
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" value="<?= e($shop['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Website</label>
        <input type="text" name="website" class="form-control" value="<?= e($shop['website'] ?? '') ?>">
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
      <div class="form-group">
        <label class="form-label">Invoice Prefix</label>
        <input type="text" name="invoice_prefix" class="form-control" value="<?= e($shop['invoice_prefix']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Currency Symbol</label>
        <input type="text" name="currency_symbol" class="form-control" value="<?= e($shop['currency_symbol']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Date Format</label>
        <select name="date_format" class="form-control">
          <option value="d-m-Y" <?= $shop['date_format'] === 'd-m-Y' ? 'selected' : '' ?>>DD-MM-YYYY (e.g. 01-09-2026)</option>
          <option value="Y-m-d" <?= $shop['date_format'] === 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Shop Copy Invoice Footer Note</label>
      <input type="text" name="invoice_footer_shop" class="form-control" value="<?= e($shop['invoice_footer_shop'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label class="form-label">Customer Copy Invoice Footer Note</label>
      <input type="text" name="invoice_footer_customer" class="form-control" value="<?= e($shop['invoice_footer_customer'] ?? '') ?>">
    </div>

    <div style="text-align:right;margin-top:1.5rem;">
      <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Settings</button>
    </div>
  </form>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
