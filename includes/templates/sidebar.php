<?php
/**
 * Master Sidebar Template
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

use App\Auth\Auth;

$activeMenu = $activeMenu ?? '';
$activeSub = $activeSub ?? '';
$shop = getShop();
?>
<div class="sidebar-backdrop" id="sidebar-backdrop"></div>
<aside class="app-sidebar" id="app-sidebar">
  <a href="<?= BASE_URL ?>/modules/dashboard/" class="sidebar-brand">
    <div class="brand-icon">
      <i class="fa fa-cubes"></i>
    </div>
    <div>
      <div class="brand-name"><?= e(mb_strimwidth($shop['name'] ?? 'Nirman ERP', 0, 18, '...')) ?></div>
      <div style="font-size:0.7rem;color:var(--text-sidebar-muted);">Materials & Accounts</div>
    </div>
  </a>

  <ul class="sidebar-menu">
    <?php if (Auth::hasPermission('dashboard.view')): ?>
      <li class="menu-item <?= $activeMenu === 'dashboard' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/dashboard/">
          <span class="menu-icon"><i class="fa fa-gauge"></i></span>
          <span>Dashboard</span>
        </a>
      </li>
    <?php endif; ?>

    <!-- QUICK SALES -->
    <li class="menu-category">Quick Sales</li>
    <?php if (Auth::hasPermission('sales.create')): ?>
      <li class="menu-item <?= $activeSub === 'new-memo' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/sales/new-memo.php">
          <span class="menu-icon"><i class="fa fa-plus-circle"></i></span>
          <span>New Memo (বিক্রয়)</span>
        </a>
      </li>
    <?php endif; ?>
    <?php if (Auth::hasPermission('sales.view')): ?>
      <li class="menu-item <?= $activeSub === 'memo-history' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/sales/memo-history.php">
          <span class="menu-icon"><i class="fa fa-file-invoice"></i></span>
          <span>Memo History</span>
        </a>
      </li>
    <?php endif; ?>

    <!-- INVENTORY -->
    <li class="menu-category">Inventory</li>
    <?php if (Auth::hasPermission('inventory.view')): ?>
      <li class="menu-item <?= $activeSub === 'products' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/inventory/products.php">
          <span class="menu-icon"><i class="fa fa-box"></i></span>
          <span>Products (মালামাল)</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'stock' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/inventory/stock.php">
          <span class="menu-icon"><i class="fa fa-warehouse"></i></span>
          <span>Current Stock</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'stock-movement' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/inventory/stock-movement.php">
          <span class="menu-icon"><i class="fa fa-arrow-right-arrow-left"></i></span>
          <span>Stock Movement</span>
        </a>
      </li>
    <?php endif; ?>
    <?php if (Auth::hasPermission('inventory.manage')): ?>
      <li class="menu-item <?= $activeSub === 'categories' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/inventory/categories.php">
          <span class="menu-icon"><i class="fa fa-tags"></i></span>
          <span>Categories</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'brands' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/inventory/brands.php">
          <span class="menu-icon"><i class="fa fa-copyright"></i></span>
          <span>Brands</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'units' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/inventory/units.php">
          <span class="menu-icon"><i class="fa fa-scale-balanced"></i></span>
          <span>Units & Conversion</span>
        </a>
      </li>
    <?php endif; ?>
    <?php if (Auth::hasPermission('inventory.adjust')): ?>
      <li class="menu-item <?= $activeSub === 'adjustments' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/inventory/adjustments.php">
          <span class="menu-icon"><i class="fa fa-sliders"></i></span>
          <span>Stock Adjustments</span>
        </a>
      </li>
    <?php endif; ?>

    <!-- PURCHASES -->
    <li class="menu-category">Purchases</li>
    <?php if (Auth::hasPermission('purchase.create')): ?>
      <li class="menu-item <?= $activeSub === 'new-purchase' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/purchases/new-purchase.php">
          <span class="menu-icon"><i class="fa fa-cart-plus"></i></span>
          <span>New Purchase (ক্রয়)</span>
        </a>
      </li>
    <?php endif; ?>
    <?php if (Auth::hasPermission('purchase.view')): ?>
      <li class="menu-item <?= $activeSub === 'purchase-history' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/purchases/purchase-history.php">
          <span class="menu-icon"><i class="fa fa-receipt"></i></span>
          <span>Purchase History</span>
        </a>
      </li>
    <?php endif; ?>
    <?php if (Auth::hasPermission('supplier.view')): ?>
      <li class="menu-item <?= $activeSub === 'suppliers' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/suppliers/list.php">
          <span class="menu-icon"><i class="fa fa-truck-field"></i></span>
          <span>Suppliers (মহাজন)</span>
        </a>
      </li>
    <?php endif; ?>

    <!-- CUSTOMERS -->
    <li class="menu-category">Customers</li>
    <?php if (Auth::hasPermission('customer.view')): ?>
      <li class="menu-item <?= $activeSub === 'customers' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/customers/list.php">
          <span class="menu-icon"><i class="fa fa-users"></i></span>
          <span>Customer List (কাস্টমার)</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'customer-ledger' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/customers/ledger.php">
          <span class="menu-icon"><i class="fa fa-book"></i></span>
          <span>Customer Ledger (খতিয়ান)</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'customer-payments' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/customers/payments.php">
          <span class="menu-icon"><i class="fa fa-money-bill-wave"></i></span>
          <span>Due Collections (বাকি আদায়)</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'customer-advances' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/customers/advances.php">
          <span class="menu-icon"><i class="fa fa-hand-holding-dollar"></i></span>
          <span>Customer Advance</span>
        </a>
      </li>
    <?php endif; ?>

    <!-- FINANCE -->
    <li class="menu-category">Finance & Accounts</li>
    <?php if (Auth::hasPermission('finance.view')): ?>
      <li class="menu-item <?= $activeSub === 'cash' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/finance/cash.php">
          <span class="menu-icon"><i class="fa fa-cash-register"></i></span>
          <span>Cash Drawer (ক্যাশ বাক্স)</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'bank' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/finance/bank.php">
          <span class="menu-icon"><i class="fa fa-building-columns"></i></span>
          <span>Bank & bKash / Nagad</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'expenses' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/finance/expenses.php">
          <span class="menu-icon"><i class="fa fa-arrow-trend-down"></i></span>
          <span>Expenses (খরচ)</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'income' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/finance/income.php">
          <span class="menu-icon"><i class="fa fa-arrow-trend-up"></i></span>
          <span>Other Income (অন্যান্য আয়)</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'profit-loss' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/finance/profit-loss.php">
          <span class="menu-icon"><i class="fa fa-chart-pie"></i></span>
          <span>Profit & Loss (লাভ-ক্ষতি)</span>
        </a>
      </li>
    <?php endif; ?>

    <!-- CALCULATORS -->
    <li class="menu-category">Calculators</li>
    <?php if (Auth::hasPermission('calculators.use')): ?>
      <li class="menu-item <?= $activeSub === 'calculators' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/calculators/">
          <span class="menu-icon"><i class="fa fa-calculator"></i></span>
          <span>Rod / Cement / Sand Calc</span>
        </a>
      </li>
    <?php endif; ?>

    <!-- REPORTS -->
    <li class="menu-category">Reports</li>
    <?php if (Auth::hasPermission('reports.sales') || Auth::hasPermission('reports.profit')): ?>
      <li class="menu-item <?= $activeSub === 'sales-report' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/reports/sales-report.php">
          <span class="menu-icon"><i class="fa fa-chart-line"></i></span>
          <span>Sales & Profit Reports</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'due-report' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/reports/customer-due-report.php">
          <span class="menu-icon"><i class="fa fa-file-invoice-dollar"></i></span>
          <span>Customer & Supplier Due</span>
        </a>
      </li>
    <?php endif; ?>

    <!-- SETTINGS -->
    <li class="menu-category">Settings</li>
    <?php if (Auth::hasPermission('settings.manage')): ?>
      <li class="menu-item <?= $activeSub === 'shop-settings' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/settings/shop.php">
          <span class="menu-icon"><i class="fa fa-gear"></i></span>
          <span>Shop Settings</span>
        </a>
      </li>
      <li class="menu-item <?= $activeSub === 'opening-balances' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/settings/opening-balances.php">
          <span class="menu-icon"><i class="fa fa-hourglass-start"></i></span>
          <span>Opening Balances</span>
        </a>
      </li>
    <?php endif; ?>
    <?php if (Auth::hasPermission('users.manage')): ?>
      <li class="menu-item <?= $activeSub === 'users' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/settings/users.php">
          <span class="menu-icon"><i class="fa fa-user-shield"></i></span>
          <span>Users & Roles</span>
        </a>
      </li>
    <?php endif; ?>
    <?php if (Auth::hasPermission('backup.manage')): ?>
      <li class="menu-item <?= $activeSub === 'backup' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/modules/settings/backup.php">
          <span class="menu-icon"><i class="fa fa-database"></i></span>
          <span>Backup Database</span>
        </a>
      </li>
    <?php endif; ?>
  </ul>
</aside>
