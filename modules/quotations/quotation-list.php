<?php
/**
 * Quotations List & Conversion
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();

$shopId = Auth::shopId();
$pdo = DB::pdo();

$sql = "SELECT q.*, c.name as customer_name, c.mobile as customer_mobile, u.full_name as created_by_name
        FROM quotations q
        JOIN customers c ON q.customer_id = c.id
        JOIN users u ON q.created_by = u.id
        WHERE q.shop_id = :s
        ORDER BY q.id DESC LIMIT 50";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId]);
$quotations = $stmt->fetchAll();

$pageTitle = 'Price Quotations & Estimates';
$activeMenu = 'quotations';
$activeSub = 'quotation-list';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-file-lines"></i> Price Quotations & Estimates (কোটেশন তালিকা)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Manage, print and convert price quotations into completed sales memos.</p>
    </div>
    <a href="<?= BASE_URL ?>/modules/quotations/new-quotation.php" class="btn btn-primary btn-sm">
      <i class="fa fa-plus"></i> + New Quotation
    </a>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Quotation No</th>
          <th>Date</th>
          <th>Valid Until</th>
          <th>Customer</th>
          <th>Subtotal</th>
          <th>Grand Total</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($quotations)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2.5rem;">No quotations generated yet.</td></tr>
        <?php else: ?>
          <?php foreach ($quotations as $q): ?>
            <tr>
              <td style="font-weight:700;color:var(--primary);"><?= e($q['quotation_no']) ?></td>
              <td><?= formatDate($q['quotation_date']) ?></td>
              <td><?= formatDate($q['valid_until']) ?></td>
              <td>
                <div style="font-weight:600;"><?= e($q['customer_name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($q['customer_mobile']) ?></div>
              </td>
              <td><?= formatMoney($q['subtotal']) ?></td>
              <td style="font-weight:800;color:var(--primary);"><?= formatMoney($q['grand_total']) ?></td>
              <td>
                <span class="badge badge-info"><?= ucfirst($q['status']) ?></span>
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
