<?php
/**
 * Bank & Digital Wallet Accounts (bKash, Nagad, Rocket)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;

Auth::requireLogin();
Auth::requirePermission('finance.view');

$shopId = Auth::shopId();
$pdo = DB::pdo();

// Handle New Bank Account
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $title   = cleanInput($_POST['account_title'] ?? '');
    $type    = cleanInput($_POST['account_type'] ?? 'bank');
    $bank    = cleanInput($_POST['bank_name'] ?? '');
    $number  = cleanInput($_POST['account_number'] ?? '');
    $branch  = cleanInput($_POST['branch_name'] ?? '');
    $opening = (float)($_POST['opening_balance'] ?? 0.0);

    if (!empty($title) && !empty($number)) {
        $stmt = $pdo->prepare("INSERT INTO bank_accounts (shop_id, account_title, account_type, bank_name, account_number, branch_name, opening_balance, current_balance, status) VALUES (:s, :title, :type, :bank, :num, :branch, :op, :op, 'active')");
        $stmt->execute(['s' => $shopId, 'title' => $title, 'type' => $type, 'bank' => $bank, 'num' => $number, 'branch' => $branch, 'op' => $opening]);
        flash('success', "Account \"{$title}\" added.");
        redirect(BASE_URL . '/modules/finance/bank.php');
    }
}

$accounts = $pdo->query("SELECT * FROM bank_accounts WHERE shop_id = {$shopId} ORDER BY id ASC")->fetchAll();
$totalBankFunds = array_sum(array_column($accounts, 'current_balance'));

$pageTitle = 'Bank & Mobile Accounts';
$activeMenu = 'finance';
$activeSub = 'bank';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-building-columns"></i> Bank & Mobile Accounts (ব্যাংক ও বিকাশ/নগদ)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Manage commercial bank current accounts and merchant wallets.</p>
    </div>
    <div style="display:flex;gap:0.75rem;align-items:center;">
      <div style="font-size:1.05rem;font-weight:800;color:var(--primary);">
        Total Bank/MFS Funds: <?= formatMoney($totalBankFunds) ?>
      </div>
      <button type="button" class="btn btn-primary btn-sm" onclick="openModal('bank-modal')">
        <i class="fa fa-plus"></i> + Add Account
      </button>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1.25rem;">
    <?php foreach ($accounts as $acc): ?>
      <div class="card" style="margin:0;border-left:4px solid var(--primary);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
          <div>
            <span class="badge badge-info" style="margin-bottom:0.4rem;"><?= strtoupper($acc['account_type']) ?></span>
            <div style="font-weight:800;font-size:1.05rem;"><?= e($acc['account_title']) ?></div>
            <div style="font-size:0.85rem;color:var(--text-secondary);"><?= e($acc['bank_name'] ?: 'Digital MFS Wallet') ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:2px;">A/C: <?= e($acc['account_number']) ?></div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:0.75rem;color:var(--text-muted);font-weight:700;">LIVE BALANCE</div>
            <div style="font-size:1.3rem;font-weight:800;color:var(--success);margin-top:2px;">
              <?= formatMoney($acc['current_balance']) ?>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Bank Modal -->
<div class="modal-backdrop" id="bank-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;"><i class="fa fa-building-columns"></i> Add Bank / MFS Account</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('bank-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <div class="form-group">
          <label class="form-label">Account Title *</label>
          <input type="text" name="account_title" class="form-control" placeholder="e.g. Maruf Traders Islami Bank CD" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Account Type *</label>
            <select name="account_type" class="form-control">
              <option value="bank">Commercial Bank</option>
              <option value="bkash">bKash Merchant</option>
              <option value="nagad">Nagad Merchant</option>
              <option value="rocket">Rocket</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Bank Name</label>
            <input type="text" name="bank_name" class="form-control" placeholder="e.g. Islami Bank PLC">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Account / Wallet Number *</label>
            <input type="text" name="account_number" class="form-control" placeholder="2050XXXXXXXX or 017XXXXXXXX" required>
          </div>
          <div class="form-group">
            <label class="form-label">Branch Name</label>
            <input type="text" name="branch_name" class="form-control" placeholder="e.g. Habiganj Branch">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Opening Balance</label>
          <input type="number" step="any" name="opening_balance" class="form-control" value="0.00">
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('bank-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
