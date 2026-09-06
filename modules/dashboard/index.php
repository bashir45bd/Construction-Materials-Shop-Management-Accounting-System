<?php
/**
 * Real-time Dashboard & KPI Analytics
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Services\ReportService;
use App\Services\MemoService;
use App\Services\StockEngineService;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('dashboard.view');

$shopId = Auth::shopId();
$period = cleanInput($_GET['period'] ?? 'today');
if (!in_array($period, ['today', 'yesterday', 'this_week', 'this_month', 'this_year'], true)) {
    $period = 'today';
}

$kpis = ReportService::getDashboardKPIs($period, $shopId);
$topProducts = MemoService::getTop20Products(30, $shopId);
$lowStock = StockEngineService::getLowStockProducts($shopId);

// Recent Sales Memos
$pdo = DB::pdo();
$recentSalesStmt = $pdo->prepare("SELECT s.*, c.name as customer_name, c.mobile as customer_mobile 
                                  FROM sales s
                                  JOIN customers c ON s.customer_id = c.id
                                  WHERE s.shop_id = :s
                                  ORDER BY s.id DESC LIMIT 8");
$recentSalesStmt->execute(['s' => $shopId]);
$recentSales = $recentSalesStmt->fetchAll();

$pageTitle = 'Dashboard Overview';
$activeMenu = 'dashboard';
$activeSub = '';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<!-- Header & Period Filter -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
  <div>
    <h1 style="font-size:1.5rem;font-weight:800;letter-spacing:-0.025em;">Shop Dashboard Overview</h1>
    <p style="color:var(--text-secondary);font-size:0.85rem;">Real-time sales, collections, customer dues and inventory metrics.</p>
  </div>

  <!-- Period Switcher Filter -->
  <div style="display:flex;gap:0.4rem;background:var(--bg-surface);padding:0.35rem;border-radius:var(--radius);border:1px solid var(--border-color);">
    <a href="?period=today" class="btn btn-sm <?= $period === 'today' ? 'btn-primary' : 'btn-secondary' ?>" style="border:none;">Today</a>
    <a href="?period=yesterday" class="btn btn-sm <?= $period === 'yesterday' ? 'btn-primary' : 'btn-secondary' ?>" style="border:none;">Yesterday</a>
    <a href="?period=this_week" class="btn btn-sm <?= $period === 'this_week' ? 'btn-primary' : 'btn-secondary' ?>" style="border:none;">This Week</a>
    <a href="?period=this_month" class="btn btn-sm <?= $period === 'this_month' ? 'btn-primary' : 'btn-secondary' ?>" style="border:none;">This Month</a>
    <a href="?period=this_year" class="btn btn-sm <?= $period === 'this_year' ? 'btn-primary' : 'btn-secondary' ?>" style="border:none;">This Year</a>
  </div>
</div>

<!-- Primary KPI Cards Grid -->
<div class="kpi-grid">
  <!-- Sales Card -->
  <div class="kpi-card">
    <div class="kpi-icon primary">
      <i class="fa fa-cart-shopping"></i>
    </div>
    <div>
      <div class="kpi-label"><?= ucfirst(str_replace('_', ' ', $period)) ?> Sales (মোট বিক্রয়)</div>
      <div class="kpi-value"><?= formatMoney($kpis['total_sales']) ?></div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;"><?= $kpis['memo_count'] ?> Memos Generated</div>
    </div>
  </div>

  <!-- Collections Card -->
  <div class="kpi-card">
    <div class="kpi-icon success">
      <i class="fa fa-hand-holding-dollar"></i>
    </div>
    <div>
      <div class="kpi-label"><?= ucfirst(str_replace('_', ' ', $period)) ?> Collection (আদায়)</div>
      <div class="kpi-value"><?= formatMoney($kpis['total_collection']) ?></div>
      <div style="font-size:0.75rem;color:var(--success);margin-top:2px;">Cash & Digital Intake</div>
    </div>
  </div>

  <!-- Customer Due Card -->
  <div class="kpi-card">
    <div class="kpi-icon danger">
      <i class="fa fa-file-invoice-dollar"></i>
    </div>
    <div>
      <div class="kpi-label">Customer Due (কাস্টমার বাকি)</div>
      <div class="kpi-value" style="color:var(--danger);"><?= formatMoney($kpis['customer_due']) ?></div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">Total Accounts Receivable</div>
    </div>
  </div>

  <!-- Customer Advance Card -->
  <div class="kpi-card">
    <div class="kpi-icon info">
      <i class="fa fa-vault"></i>
    </div>
    <div>
      <div class="kpi-label">Customer Advance (জমা/অগ্রিম)</div>
      <div class="kpi-value" style="color:var(--info);"><?= formatMoney($kpis['customer_advance']) ?></div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">Unallocated Customer Deposits</div>
    </div>
  </div>
</div>

<!-- Secondary KPI Cards Grid -->
<div class="kpi-grid">
  <!-- Stock Valuation -->
  <div class="kpi-card">
    <div class="kpi-icon warning">
      <i class="fa fa-boxes-stacked"></i>
    </div>
    <div>
      <div class="kpi-label">Stock Valuation (গুদাম মূল্য)</div>
      <div class="kpi-value"><?= formatMoney($kpis['stock_valuation']) ?></div>
      <div style="font-size:0.75rem;color:var(--danger);margin-top:2px;"><?= $kpis['low_stock_count'] ?> Low Stock Products</div>
    </div>
  </div>

  <!-- Supplier Due -->
  <div class="kpi-card">
    <div class="kpi-icon danger">
      <i class="fa fa-truck-ramp-box"></i>
    </div>
    <div>
      <div class="kpi-label">Supplier Due (মহাজন বাকি)</div>
      <div class="kpi-value" style="color:var(--danger);"><?= formatMoney($kpis['supplier_due']) ?></div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">Total Accounts Payable</div>
    </div>
  </div>

  <!-- Liquid Funds -->
  <div class="kpi-card">
    <div class="kpi-icon primary">
      <i class="fa fa-building-columns"></i>
    </div>
    <div>
      <div class="kpi-label">Liquid Cash & Bank (মোট তহবিল)</div>
      <div class="kpi-value"><?= formatMoney($kpis['total_liquid_funds']) ?></div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">Cash: <?= formatMoney($kpis['cash_balance']) ?> | Bank: <?= formatMoney($kpis['bank_balance']) ?></div>
    </div>
  </div>

  <!-- Net Profit -->
  <div class="kpi-card">
    <div class="kpi-icon success">
      <i class="fa fa-chart-line"></i>
    </div>
    <div>
      <div class="kpi-label"><?= ucfirst(str_replace('_', ' ', $period)) ?> Net Profit (নিট লাভ)</div>
      <div class="kpi-value" style="color:var(--success);"><?= formatMoney($kpis['net_profit']) ?></div>
      <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">Gross: <?= formatMoney($kpis['gross_profit']) ?> - Exp: <?= formatMoney($kpis['total_expenses']) ?></div>
    </div>
  </div>
</div>

<!-- Dashboard Content Two Columns -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(480px, 1fr));gap:1.5rem;">
  <!-- Recent Sales Memos -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-receipt"></i> Recent Sales Memos</div>
      <a href="<?= BASE_URL ?>/modules/sales/memo-history.php" class="btn btn-secondary btn-sm">View All</a>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Memo No</th>
            <th>Customer</th>
            <th>Grand Total</th>
            <th>Paid</th>
            <th>Due</th>
            <th>Status</th>
            <th style="text-align:center;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentSales)): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2rem;">No recent sales memos.</td></tr>
          <?php else: ?>
            <?php foreach ($recentSales as $sale): ?>
              <tr>
                <td style="font-weight:700;">
                  <a href="<?= BASE_URL ?>/modules/sales/memo-print.php?id=<?= $sale['id'] ?>" style="color:var(--primary);text-decoration:none;">
                    <?= e($sale['memo_number']) ?>
                  </a>
                </td>
                <td>
                  <div style="font-weight:600;"><?= e($sale['customer_name']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($sale['customer_mobile']) ?></div>
                </td>
                <td style="font-weight:700;"><?= formatMoney($sale['grand_total']) ?></td>
                <td style="color:var(--success);"><?= formatMoney($sale['paid_amount'] + $sale['advance_used']) ?></td>
                <td style="color:var(--danger);font-weight:600;"><?= formatMoney($sale['due_amount']) ?></td>
                <td>
                  <?php if ($sale['payment_status'] === 'paid'): ?>
                    <span class="badge badge-success">Paid</span>
                  <?php elseif ($sale['payment_status'] === 'partial'): ?>
                    <span class="badge badge-warning">Partial</span>
                  <?php elseif ($sale['payment_status'] === 'advance_settled'): ?>
                    <span class="badge badge-info">Advance</span>
                  <?php else: ?>
                    <span class="badge badge-danger">Due</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center;">
                  <a href="<?= BASE_URL ?>/modules/sales/memo-print.php?id=<?= $sale['id'] ?>" class="btn btn-secondary btn-sm" title="Print Two-Copy Memo">
                    <i class="fa fa-print"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top 10 Selling Products & Low Stock Alerts -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa fa-fire"></i> Top Selling Materials (30 Days)</div>
      <a href="<?= BASE_URL ?>/modules/sales/new-memo.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> New Sale</a>
    </div>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Category / Brand</th>
            <th>Rate</th>
            <th>Sold Qty</th>
            <th>Stock</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($topProducts)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:2rem;">No sales activity recorded yet.</td></tr>
          <?php else: ?>
            <?php foreach (array_slice($topProducts, 0, 8) as $prod): ?>
              <tr>
                <td style="font-weight:700;"><?= e($prod['name']) ?></td>
                <td style="font-size:0.8rem;color:var(--text-secondary);"><?= e($prod['category_name']) ?> (<?= e($prod['brand_name'] ?? 'Local') ?>)</td>
                <td style="font-weight:600;"><?= formatMoney($prod['selling_price']) ?> / <?= e($prod['unit_symbol']) ?></td>
                <td style="font-weight:700;color:var(--primary);"><?= formatQuantity($prod['total_sold_qty']) ?> <?= e($prod['unit_symbol']) ?></td>
                <td>
                  <span class="badge <?= (float)$prod['current_stock'] <= (float)$prod['min_stock'] ? 'badge-danger' : 'badge-success' ?>">
                    <?= formatQuantity($prod['current_stock']) ?> <?= e($prod['unit_symbol']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
