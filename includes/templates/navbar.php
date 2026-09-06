<?php
/**
 * Master Top Navigation Template
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

use App\Auth\Auth;

$currentUser = Auth::user();
$shop = getShop();
?>
<main class="app-main">
  <header class="app-navbar no-print">
    <div class="navbar-left">
      <button class="btn btn-secondary btn-sm sidebar-toggle-btn" id="sidebar-toggle-btn" style="display:none;" title="Toggle Menu">
        <i class="fa fa-bars"></i>
      </button>

      <div class="global-search-container">
        <i class="fa fa-search global-search-icon"></i>
        <input type="text" class="global-search-input" id="global-search-input" placeholder="Search Customer, Product, Memo...">
      </div>
    </div>

    <div class="navbar-right">
      <!-- Quick Sale Shortcut Button -->
      <?php if (Auth::hasPermission('sales.create')): ?>
        <a href="<?= BASE_URL ?>/modules/sales/new-memo.php" class="btn btn-primary btn-sm">
          <i class="fa fa-plus"></i> New Memo
        </a>
      <?php endif; ?>

      <!-- Theme Switcher -->
      <button class="btn btn-secondary btn-sm" id="theme-toggle-btn" title="Toggle Light / Dark Mode">
        <i class="fa fa-moon"></i>
      </button>

      <!-- User Profile Badge -->
      <div style="display:flex;align-items:center;gap:0.6rem;padding-left:0.5rem;border-left:1px solid var(--border-color);">
        <div style="width:32px;height:32px;border-radius:var(--radius-full);background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.85rem;">
          <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
        </div>
        <div style="font-size:0.85rem;line-height:1.2;">
          <div style="font-weight:700;"><?= e($currentUser['full_name'] ?? 'User') ?></div>
          <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($currentUser['username'] ?? '') ?></div>
        </div>
        <a href="<?= BASE_URL ?>/modules/auth/logout.php" class="btn btn-secondary btn-sm" title="Log Out" style="margin-left:0.5rem;color:var(--danger);">
          <i class="fa fa-right-from-bracket"></i>
        </a>
      </div>
    </div>
  </header>

  <div class="app-content">
    <?php
    // Render Flash Messages
    $flashes = getFlashes();
    if (!empty($flashes)):
      foreach ($flashes as $f):
    ?>
      <div class="toast <?= e($f['type']) ?>" style="position:static;width:100%;margin-bottom:1rem;box-shadow:var(--shadow-sm);">
        <span><?= e($f['message']) ?></span>
      </div>
    <?php
      endforeach;
    endif;
    ?>
