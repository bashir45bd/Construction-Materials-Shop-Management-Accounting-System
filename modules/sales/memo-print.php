<?php
/**
 * Dual-Copy Printable Sales Memo
 * Output: Two Vouchers (SHOP COPY & CUSTOMER COPY)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Services\MemoService;

Auth::requireLogin();
Auth::requirePermission('sales.view');

$saleId = $_GET['id'] ?? '';
$shopId = Auth::shopId();
$shop = getShop($shopId);

$sale = MemoService::getMemoDetails($saleId, $shopId);
if (!$sale) {
    die("Error: Sales memo not found.");
}

$autoPrint = !empty($_GET['autoprint']);
$pageTitle = "Print Memo {$sale['memo_number']}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Memo <?= e($sale['memo_number']) ?> | <?= e($shop['name']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/memo-print.css">
  <style>
    body {
      background: #f1f5f9;
      padding: 20px;
    }
    .print-control-bar {
      max-width: 900px;
      margin: 0 auto 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #ffffff;
      padding: 12px 20px;
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--border-color);
    }
  </style>
</head>
<body>

<!-- Non-Printable Top Action Bar -->
<div class="print-control-bar no-print">
  <div style="font-weight:700;font-size:1.1rem;display:flex;align-items:center;gap:8px;">
    <i class="fa fa-file-invoice" style="color:var(--primary);"></i>
    Memo: <?= e($sale['memo_number']) ?>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="<?= BASE_URL ?>/modules/sales/new-memo.php" class="btn btn-secondary btn-sm">
      <i class="fa fa-plus"></i> New Sale
    </a>
    <a href="<?= BASE_URL ?>/modules/sales/memo-history.php" class="btn btn-secondary btn-sm">
      <i class="fa fa-list"></i> History
    </a>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
      <i class="fa fa-print"></i> Print Two Copies
    </button>
  </div>
</div>

<div class="memo-dual-container" id="print-memo-container">
  <?php
  // Helper to render one voucher
  function renderVoucher(array $sale, array $shop, string $copyType, string $copyLabelBangla, string $cssClass): void
  {
  ?>
    <div class="memo-voucher <?= $cssClass ?>">
      <div class="memo-watermark-tag"><?= $copyType ?> (<?= $copyLabelBangla ?>)</div>

      <!-- Shop Header -->
      <div class="memo-header">
        <div class="shop-title"><?= e($shop['name']) ?></div>
        <div class="shop-subtitle"><?= e($shop['address']) ?></div>
        <div class="shop-subtitle" style="font-weight:600;margin-top:2px;">
          Mobile: <?= e($shop['phone']) ?> <?= !empty($shop['alt_phone']) ? ' | ' . e($shop['alt_phone']) : '' ?>
        </div>
      </div>

      <!-- Memo Meta & Customer Details -->
      <div class="memo-meta-grid">
        <div>
          <div style="font-size:0.8rem;color:#64748b;text-transform:uppercase;font-weight:700;">Customer Information (গ্রাহকের বিবরণ):</div>
          <div style="font-weight:800;font-size:0.95rem;margin-top:2px;"><?= e($sale['customer_name']) ?></div>
          <div>Mobile: <?= e($sale['customer_mobile']) ?></div>
          <div>Address: <?= e($sale['customer_address'] ?: 'Habiganj') ?></div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:0.8rem;color:#64748b;text-transform:uppercase;font-weight:700;">Memo Details:</div>
          <div style="font-weight:800;font-size:1.05rem;color:#1e40af;margin-top:2px;"># <?= e($sale['memo_number']) ?></div>
          <div>Date: <?= formatDate($sale['sale_date']) ?></div>
          <div>Salesperson: <?= e($sale['created_by_name']) ?></div>
        </div>
      </div>

      <!-- Itemized Table (Exact Serial Order) -->
      <table class="memo-items-table">
        <thead>
          <tr>
            <th style="width:35px;" class="text-center">Sl</th>
            <th>Item Description (মালামালের বিবরণ)</th>
            <th class="text-center" style="width:75px;">Unit</th>
            <th class="text-right" style="width:85px;">Quantity</th>
            <th class="text-right" style="width:95px;">Rate</th>
            <th class="text-right" style="width:115px;">Total Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sale['items'] as $item): ?>
            <tr>
              <td class="text-center" style="font-weight:700;"><?= str_pad((string)$item['item_order'], 2, '0', STR_PAD_LEFT) ?></td>
              <td>
                <span style="font-weight:700;"><?= e($item['product_name']) ?></span>
                <?php if (!empty($item['brand_name'])): ?>
                  <span style="font-size:0.75rem;color:#64748b;">(<?= e($item['brand_name']) ?>)</span>
                <?php endif; ?>
              </td>
              <td class="text-center"><?= e($item['unit_symbol']) ?></td>
              <td class="text-right" style="font-weight:700;"><?= formatQuantity($item['quantity']) ?></td>
              <td class="text-right"><?= number_format((float)$item['unit_price'], 2) ?></td>
              <td class="text-right" style="font-weight:700;"><?= number_format((float)$item['line_total'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Financial Totals -->
      <table class="memo-totals-table">
        <tr>
          <td class="total-label">Subtotal (মোট):</td>
          <td class="text-right" style="font-weight:700;"><?= formatMoney($sale['subtotal']) ?></td>
        </tr>
        <?php if ((float)$sale['discount_amount'] > 0): ?>
          <tr>
            <td class="total-label">Discount (ছাড়):</td>
            <td class="text-right" style="color:#dc2626;font-weight:700;">- <?= formatMoney($sale['discount_amount']) ?></td>
          </tr>
        <?php endif; ?>
        <tr class="grand-total-row">
          <td class="total-label">Grand Total (সর্বমোট বিল):</td>
          <td class="text-right"><?= formatMoney($sale['grand_total']) ?></td>
        </tr>
        <?php if ((float)$sale['advance_used'] > 0): ?>
          <tr>
            <td class="total-label">Advance Adjusted (পূর্বের জমা থেকে কর্তন):</td>
            <td class="text-right" style="color:#059669;font-weight:700;"><?= formatMoney($sale['advance_used']) ?></td>
          </tr>
        <?php endif; ?>
        <tr>
          <td class="total-label">Paid Now (নগদ পরিশোধ):</td>
          <td class="text-right" style="color:#059669;font-weight:700;"><?= formatMoney($sale['paid_amount']) ?></td>
        </tr>
        <tr>
          <td class="total-label" style="font-size:0.95rem;color:#dc2626;">Remaining Due (মেমো বাকি):</td>
          <td class="text-right" style="font-size:0.95rem;font-weight:800;color:#dc2626;"><?= formatMoney($sale['due_amount']) ?></td>
        </tr>
      </table>

      <!-- Signatures -->
      <div class="memo-signatures">
        <div class="sign-line">Customer Signature</div>
        <div class="sign-line">Authorized Sign & Seal</div>
      </div>

      <div class="memo-footer-note">
        <?= e($copyType === 'SHOP COPY' ? ($shop['invoice_footer_shop'] ?? '') : ($shop['invoice_footer_customer'] ?? '')) ?>
      </div>
    </div>
  <?php
  }

  // Render COPY 1: SHOP COPY
  renderVoucher($sale, $shop, 'SHOP COPY', 'দোকান কপি', 'shop-copy');

  // Render COPY 2: CUSTOMER COPY
  renderVoucher($sale, $shop, 'CUSTOMER COPY', 'গ্রাহক কপি', 'customer-copy');
  ?>
</div>

<?php if ($autoPrint): ?>
<script>
  window.addEventListener('load', () => {
    setTimeout(() => {
      window.print();
    }, 400);
  });
</script>
<?php endif; ?>

</body>
</html>
