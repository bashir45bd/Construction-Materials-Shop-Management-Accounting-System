<?php
/**
 * Real-Time Profit & Loss (Income Statement)
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Services\FinanceService;

Auth::requireLogin();
Auth::requirePermission('finance.view');

$shopId = Auth::shopId();

$startDate = cleanInput($_GET['start_date'] ?? date('Y-01-01'));
$endDate = cleanInput($_GET['end_date'] ?? date('Y-m-d'));

$pnl = FinanceService::getIncomeStatement($startDate, $endDate, $shopId);

$pageTitle = 'Profit & Loss Statement (লাভ-ক্ষতি বিবরণী)';
$activeMenu = 'finance';
$activeSub = 'profit-loss';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 class="card-title"><i class="fa fa-chart-pie"></i> Profit & Loss Statement (লাভ-ক্ষতি বিবরণী)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Real-time Income Statement calculated from historical COGS lot snapshots, operating revenues, and expenses.</p>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
      <i class="fa fa-print"></i> Print Statement
    </button>
  </div>

  <form method="GET" style="display:flex;gap:0.75rem;margin-bottom:1.5rem;background:var(--bg-surface-secondary);padding:0.75rem;border-radius:var(--radius);flex-wrap:wrap;">
    <input type="date" name="start_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($startDate) ?>">
    <input type="date" name="end_date" class="form-control form-control-sm" style="max-width:160px;" value="<?= e($endDate) ?>">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Calculate Period</button>
  </form>

  <div style="max-width:800px;margin:0 auto;background:var(--bg-surface);border:1px solid var(--border-color);border-radius:var(--radius);padding:1.5rem;">
    <table class="table" style="font-size:0.95rem;">
      <tbody>
        <!-- REVENUE -->
        <tr style="background:var(--bg-surface-secondary);">
          <td colspan="2" style="font-weight:800;font-size:1.05rem;">1. Operating Sales Revenue (বিক্রয় রাজস্ব)</td>
        </tr>
        <tr>
          <td style="padding-left:1.5rem;">Gross Sales Volume (মোট বিক্রয়)</td>
          <td style="text-align:right;font-weight:700;"><?= formatMoney($pnl['total_sales']) ?></td>
        </tr>
        <tr>
          <td style="padding-left:1.5rem;color:var(--danger);">Less: Cost of Goods Sold (COGS - পন্যের ক্রয়মূল্য)</td>
          <td style="text-align:right;font-weight:700;color:var(--danger);">- <?= formatMoney($pnl['total_cogs']) ?></td>
        </tr>
        <tr style="border-top:2px solid var(--border-color);font-weight:800;">
          <td style="font-size:1.05rem;color:var(--primary);">GROSS PROFIT (মোট লাভ):</td>
          <td style="text-align:right;font-size:1.15rem;color:var(--primary);"><?= formatMoney($pnl['gross_profit']) ?></td>
        </tr>

        <!-- OTHER INCOMES -->
        <tr style="background:var(--bg-surface-secondary);">
          <td colspan="2" style="font-weight:800;font-size:1.05rem;padding-top:1.5rem;">2. Other Operating Incomes (অন্যান্য আয়)</td>
        </tr>
        <?php if (empty($pnl['other_income']['breakdown'])): ?>
          <tr><td colspan="2" style="padding-left:1.5rem;color:var(--text-muted);">No other income records in period.</td></tr>
        <?php else: ?>
          <?php foreach ($pnl['other_income']['breakdown'] as $inc): ?>
            <tr>
              <td style="padding-left:1.5rem;"><?= e($inc['category_name']) ?></td>
              <td style="text-align:right;color:var(--success);font-weight:600;">+ <?= formatMoney($inc['amount']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        <tr style="font-weight:700;">
          <td style="padding-left:1.5rem;">Total Other Income:</td>
          <td style="text-align:right;color:var(--success);"><?= formatMoney($pnl['other_income']['total']) ?></td>
        </tr>

        <!-- OPERATING EXPENSES -->
        <tr style="background:var(--bg-surface-secondary);">
          <td colspan="2" style="font-weight:800;font-size:1.05rem;padding-top:1.5rem;">3. Operating Overhead Expenses (দোকানের খরচসমূহ)</td>
        </tr>
        <?php if (empty($pnl['expenses']['breakdown'])): ?>
          <tr><td colspan="2" style="padding-left:1.5rem;color:var(--text-muted);">No expense records in period.</td></tr>
        <?php else: ?>
          <?php foreach ($pnl['expenses']['breakdown'] as $exp): ?>
            <tr>
              <td style="padding-left:1.5rem;"><?= e($exp['category_name']) ?></td>
              <td style="text-align:right;color:var(--danger);font-weight:600;">- <?= formatMoney($exp['amount']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        <tr style="font-weight:700;">
          <td style="padding-left:1.5rem;">Total Operating Expenses:</td>
          <td style="text-align:right;color:var(--danger);">- <?= formatMoney($pnl['expenses']['total']) ?></td>
        </tr>

        <!-- NET PROFIT -->
        <tr style="border-top:3px solid var(--text-primary);border-bottom:3px double var(--text-primary);font-size:1.2rem;font-weight:900;">
          <td style="color:<?= $pnl['net_profit'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">NET PROFIT / LOSS (সর্বমোট নিট লাভ/ক্ষতি):</td>
          <td style="text-align:right;color:<?= $pnl['net_profit'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
            <?= formatMoney($pnl['net_profit']) ?>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
