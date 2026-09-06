<?php
/**
 * Sales Analytics & Revenue Reports
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('reports.sales');

$shopId = Auth::shopId();
$pdo = DB::pdo();

$startDate = cleanInput($_GET['start_date'] ?? date('Y-m-01'));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d'));

$sql = "SELECT s.*, c.name as customer_name, c.mobile as customer_mobile
        FROM sales s
        JOIN customers c ON s.customer_id = c.id
        WHERE s.shop_id = :s AND s.sale_status != 'cancelled' AND s.sale_date BETWEEN :start AND :end
        ORDER BY s.sale_date DESC, s.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId, 'start' => $startDate, 'end' => $endDate]);
$sales = $stmt->fetchAll();

$totalSales = array_sum(array_column($sales, 'grand_total'));
$totalDiscounts = array_sum(array_column($sales, 'discount_amount'));
$totalPaid = array_sum(array_column($sales, 'paid_amount'));
$totalDue = array_sum(array_column($sales, 'due_amount'));
$totalGrossProfit = array_sum(array_column($sales, 'gross_profit'));

$pageTitle = 'Sales & Revenue Report (বিক্রয় রিপোর্ট)';
$activeMenu = 'reports';
$activeSub = 'sales-report';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-chart-line"></i> Sales & Revenue Report (বিক্রয় রিপোর্ট)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Periodical sales breakdown, margins, collections and remaining dues.</p>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
      <i class="fa fa-print"></i> Print Report
    </button>
  </div>

  <form method="GET" style="display:flex;gap:0.75rem;margin-bottom:1.25rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);flex-wrap:wrap;">
    <input type="date" name="start_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($startDate) ?>">
    <input type="date" name="end_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($endDate) ?>">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Generate Report</button>
  </form>

  <!-- Summary KPI Row -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));gap:1rem;margin-bottom:1.5rem;">
    <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);">TOTAL SALES</div>
      <div style="font-size:1.2rem;font-weight:800;color:var(--primary);"><?= formatMoney($totalSales) ?></div>
    </div>
    <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);">COLLECTED</div>
      <div style="font-size:1.2rem;font-weight:800;color:var(--success);"><?= formatMoney($totalPaid) ?></div>
    </div>
    <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);">NEW DUE CREATED</div>
      <div style="font-size:1.2rem;font-weight:800;color:var(--danger);"><?= formatMoney($totalDue) ?></div>
    </div>
    <div style="background:var(--bg-surface-secondary);padding:0.75rem 1rem;border-radius:var(--radius);">
      <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);">GROSS PROFIT</div>
      <div style="font-size:1.2rem;font-weight:800;color:var(--success);"><?= formatMoney($totalGrossProfit) ?></div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Memo No</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Grand Total</th>
          <th>Discounts</th>
          <th>Paid / Advance</th>
          <th>Due</th>
          <th>Gross Profit</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($sales)): ?>
          <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:2.5rem;">No sales records in selected period.</td></tr>
        <?php else: ?>
          <?php foreach ($sales as $s): ?>
            <tr>
              <td style="font-weight:700;color:var(--primary);"><?= e($s['memo_number']) ?></td>
              <td><?= formatDate($s['sale_date']) ?></td>
              <td><?= e($s['customer_name']) ?></td>
              <td style="font-weight:700;"><?= formatMoney($s['grand_total']) ?></td>
              <td style="color:var(--danger);"><?= formatMoney($s['discount_amount']) ?></td>
              <td style="color:var(--success);font-weight:600;"><?= formatMoney($s['paid_amount'] + $s['advance_used']) ?></td>
              <td style="color:var(--danger);font-weight:700;"><?= formatMoney($s['due_amount']) ?></td>
              <td style="color:var(--success);font-weight:700;"><?= formatMoney($s['gross_profit']) ?></td>
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
