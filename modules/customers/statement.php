<?php
/**
 * Customer Printable A4 Account Statement (কাস্টমার স্টেটমেন্ট)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('customer.view');

$shopId = Auth::shopId();
$shop = getShop($shopId);
$customerId = (int)($_GET['customer_id'] ?? 0);
$pdo = DB::pdo();

// Fetch Customer
$stmtCust = $pdo->prepare("SELECT * FROM customers WHERE id = :id AND shop_id = :s");
$stmtCust->execute(['id' => $customerId, 's' => $shopId]);
$customer = $stmtCust->fetch();

if (!$customer) {
    die("Customer not found.");
}

$startDate = cleanInput($_GET['start_date'] ?? date('Y-01-01'));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d'));

$sql = "SELECT cl.*, u.full_name as user_name 
        FROM customer_ledger cl
        JOIN users u ON cl.user_id = u.id
        WHERE cl.shop_id = :s AND cl.customer_id = :cid AND cl.transaction_date BETWEEN :start AND :end
        ORDER BY cl.transaction_date ASC, cl.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId, 'cid' => $customerId, 'start' => $startDate, 'end' => $endDate]);
$transactions = $stmt->fetchAll();

$totalDebit = array_sum(array_column($transactions, 'debit'));
$totalCredit = array_sum(array_column($transactions, 'credit'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer Statement - <?= e($customer['name']) ?> | <?= e($shop['name']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <style>
    body {
      background: #ffffff;
      padding: 30px;
      color: #0f172a;
      font-size: 13px;
    }
    .statement-box {
      max-width: 850px;
      margin: 0 auto;
    }
    @media print {
      body { padding: 0 !important; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="statement-box">
  <!-- Print Button Bar -->
  <div class="no-print" style="display:flex;justify-content:space-between;margin-bottom:20px;padding:10px 0;border-bottom:1px solid #e2e8f0;">
    <a href="<?= BASE_URL ?>/modules/customers/ledger.php?customer_id=<?= $customer['id'] ?>" class="btn btn-secondary btn-sm">
      <i class="fa fa-arrow-left"></i> Back to Ledger
    </a>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
      <i class="fa fa-print"></i> Print Statement (A4)
    </button>
  </div>

  <!-- Header -->
  <div style="text-align:center;border-bottom:2px solid #0f172a;padding-bottom:15px;margin-bottom:20px;">
    <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:2px;"><?= e($shop['name']) ?></h1>
    <div style="color:#475569;font-size:0.9rem;"><?= e($shop['address']) ?></div>
    <div style="color:#475569;font-size:0.85rem;font-weight:600;">Phone: <?= e($shop['phone']) ?></div>
    <div style="display:inline-block;background:#0f172a;color:#ffffff;padding:4px 16px;border-radius:4px;font-weight:800;font-size:0.85rem;margin-top:8px;text-transform:uppercase;letter-spacing:0.05em;">
      Customer Account Statement (কাস্টমার হিসাব বিবরণী)
    </div>
  </div>

  <!-- Statement Meta Grid -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;">
    <div>
      <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Customer Profile:</div>
      <div style="font-weight:800;font-size:1.1rem;color:#0f172a;"><?= e($customer['name']) ?></div>
      <div>Customer Code: <code><?= e($customer['customer_code']) ?></code></div>
      <div>Mobile: <?= e($customer['mobile']) ?></div>
      <div>Address: <?= e($customer['address'] ?: 'Habiganj') ?></div>
    </div>
    <div style="text-align:right;">
      <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;">Statement Period:</div>
      <div style="font-weight:700;"><?= formatDate($startDate) ?> to <?= formatDate($endDate) ?></div>
      <div style="margin-top:8px;font-weight:800;font-size:1.05rem;color:#dc2626;">
        Current Due (মোট বাকি): <?= formatMoney($customer['current_due']) ?>
      </div>
      <?php if ((float)$customer['current_advance'] > 0): ?>
        <div style="font-weight:800;color:#059669;">
          Advance Deposit (জমা): <?= formatMoney($customer['current_advance']) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Transactions Table -->
  <table class="table" style="border:1px solid #cbd5e1;margin-bottom:25px;">
    <thead>
      <tr style="background:#f1f5f9;">
        <th style="border:1px solid #cbd5e1;">Date</th>
        <th style="border:1px solid #cbd5e1;">Reference No</th>
        <th style="border:1px solid #cbd5e1;">Description</th>
        <th style="border:1px solid #cbd5e1;text-align:right;">Debit (বিক্রয়)</th>
        <th style="border:1px solid #cbd5e1;text-align:right;">Credit (আদায়/ফেরত)</th>
        <th style="border:1px solid #cbd5e1;text-align:right;">Due Balance</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($transactions)): ?>
        <tr><td colspan="6" style="text-align:center;padding:2rem;color:#64748b;">No transactions in this period.</td></tr>
      <?php else: ?>
        <?php foreach ($transactions as $t): ?>
          <tr>
            <td style="border:1px solid #cbd5e1;"><?= formatDate($t['transaction_date']) ?></td>
            <td style="border:1px solid #cbd5e1;font-weight:700;"><?= e($t['reference_no']) ?></td>
            <td style="border:1px solid #cbd5e1;"><?= e($t['description']) ?></td>
            <td style="border:1px solid #cbd5e1;text-align:right;font-weight:700;color:<?= (float)$t['debit'] > 0 ? '#dc2626' : 'inherit' ?>;">
              <?= (float)$t['debit'] > 0 ? number_format((float)$t['debit'], 2) : '-' ?>
            </td>
            <td style="border:1px solid #cbd5e1;text-align:right;font-weight:700;color:<?= (float)$t['credit'] > 0 ? '#059669' : 'inherit' ?>;">
              <?= (float)$t['credit'] > 0 ? number_format((float)$t['credit'], 2) : '-' ?>
            </td>
            <td style="border:1px solid #cbd5e1;text-align:right;font-weight:800;">
              <?= number_format((float)$t['running_balance'], 2) ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <tr style="background:#f8fafc;font-weight:800;">
        <td colspan="3" style="border:1px solid #cbd5e1;text-align:right;">Period Total:</td>
        <td style="border:1px solid #cbd5e1;text-align:right;color:#dc2626;"><?= number_format($totalDebit, 2) ?></td>
        <td style="border:1px solid #cbd5e1;text-align:right;color:#059669;"><?= number_format($totalCredit, 2) ?></td>
        <td style="border:1px solid #cbd5e1;text-align:right;"><?= formatMoney($customer['current_due']) ?></td>
      </tr>
    </tfoot>
  </table>

  <!-- Signatures -->
  <div style="display:flex;justify-content:space-between;margin-top:60px;">
    <div style="width:200px;border-top:1px dashed #475569;text-align:center;font-size:0.8rem;color:#475569;padding-top:4px;">
      Customer Signature
    </div>
    <div style="width:200px;border-top:1px dashed #475569;text-align:center;font-size:0.8rem;color:#475569;padding-top:4px;">
      Authorized Sign & Seal
    </div>
  </div>
</div>

</body>
</html>
