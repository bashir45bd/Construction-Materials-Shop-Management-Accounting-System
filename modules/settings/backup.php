<?php
/**
 * Database Backup & Export Subsystem
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Helpers\Csrf;
use App\Services\BackupService;

Auth::requireLogin();
Auth::requirePermission('backup.manage');

$shopId = Auth::shopId();

// Handle Direct SQL Backup Download
if (isset($_GET['action']) && $_GET['action'] === 'download_sql') {
    $res = BackupService::createSqlBackup($shopId);
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $res['filename'] . '"');
    header('Content-Length: ' . strlen($res['content']));
    echo $res['content'];
    exit;
}

$pageTitle = 'Database Backup & System Recovery';
$activeMenu = 'settings';
$activeSub = 'backup';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card" style="max-width:800px;margin:0 auto;">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-database"></i> Database Backup & Data Protection</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Safeguard sales transactions, inventory records, customer ledgers and financial logs.</p>
    </div>
  </div>

  <div style="padding:1.5rem 0;text-align:center;">
    <div style="width:80px;height:80px;border-radius:var(--radius-full);background:var(--primary-light);color:var(--primary);display:inline-flex;align-items:center;justify-content:center;font-size:2.5rem;margin-bottom:1.25rem;">
      <i class="fa fa-shield-halved"></i>
    </div>
    <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:0.5rem;">Export Complete MySQL Database Snapshot</h2>
    <p style="color:var(--text-secondary);max-width:520px;margin:0 auto 1.75rem;font-size:0.9rem;">
      Generates an unencrypted, standardized SQL dump containing all schema structures and database records ready for disaster recovery or offline archiving.
    </p>

    <a href="?action=download_sql" class="btn btn-primary btn-lg" style="font-size:1.05rem;padding:0.85rem 2rem;">
      <i class="fa fa-download"></i> Download Full SQL Backup (.sql)
    </a>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
