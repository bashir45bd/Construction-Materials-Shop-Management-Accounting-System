<?php
/**
 * Customer Due & Advance Summary Statement
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('reports.due');

$shopId = Auth::shopId();
$pdo = DB::pdo();

$customers = $pdo->query("SELECT * FROM customers WHERE shop_id = {$shopId} AND (current_due > 0 OR current_advance > 0) ORDER BY current_due DESC")->fetchAll();

$totalDue = array_sum(array_column($customers, 'current_due'));
$totalAdvance = array_sum(array_column($customers, 'current_advance'));

$pageTitle = 'Customer Due & Advance Statement';
$activeMenu = 'reports';
$activeSub = 'due-report';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-file-invoice-dollar"></i> Customer Due & Advance Statement</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Summary report of all active customer receivables and credit advance deposits.</p>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
      <i class="fa fa-print"></i> Print Statement
    </button>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
    <div style="background:var(--danger-light);color:var(--danger);padding:1rem;border-radius:var(--radius);">
      <div style="font-size:0.8rem;font-weight:700;">TOTAL ACCOUNTS RECEIVABLE (মোট বাকি)</div>
      <div style="font-size:1.5rem;font-weight:900;"><?= formatMoney($totalDue) ?></div>
    </div>
    <div style="background:var(--success-light);color:var(--success);padding:1rem;border-radius:var(--radius);">
      <div style="font-size:0.8rem;font-weight:700;">TOTAL CUSTOMER ADVANCES (মোট জমা/অগ্রিম)</div>
      <div style="font-size:1.5rem;font-weight:900;"><?= formatMoney($totalAdvance) ?></div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Customer Code</th>
          <th>Customer Name</th>
          <th>Mobile</th>
          <th>Type</th>
          <th>Address</th>
          <th style="text-align:right;">Current Due (বাকি)</th>
          <th style="text-align:right;">Current Advance (জমা)</th>
          <th style="text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2rem;">No outstanding customer dues or advances.</td></tr>
        <?php else: ?>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td style="font-weight:700;color:var(--text-muted);"><?= e($c['customer_code']) ?></td>
              <td style="font-weight:700;">
                <a href="<?= BASE_URL ?>/modules/customers/ledger.php?customer_id=<?= $c['id'] ?>" style="color:var(--text-primary);text-decoration:none;">
                  <?= e($c['name']) ?>
                </a>
              </td>
              <td><?= e($c['mobile']) ?></td>
              <td><span class="badge badge-info"><?= ucfirst($c['customer_type']) ?></span></td>
              <td style="color:var(--text-secondary);"><?= e($c['address'] ?: '-') ?></td>
              <td style="text-align:right;font-weight:800;color:<?= (float)$c['current_due'] > 0 ? 'var(--danger)' : 'var(--text-muted)' ?>;">
                <?= formatMoney($c['current_due']) ?>
              </td>
              <td style="text-align:right;font-weight:800;color:<?= (float)$c['current_advance'] > 0 ? 'var(--success)' : 'var(--text-muted)' ?>;">
                <?= formatMoney($c['current_advance']) ?>
              </td>
              <td style="text-align:center;">
                <a href="<?= BASE_URL ?>/modules/customers/ledger.php?customer_id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">
                  <i class="fa fa-book"></i> Statement
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
