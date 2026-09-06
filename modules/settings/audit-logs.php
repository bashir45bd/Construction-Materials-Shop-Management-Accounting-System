<?php
/**
 * System Audit Trail & Security Logs
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;

Auth::requireLogin();
Auth::requirePermission('settings.manage');

$shopId = Auth::shopId();
$pdo = DB::pdo();

$sql = "SELECT al.*, u.full_name as user_name 
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.shop_id = :s
        ORDER BY al.id DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId]);
$logs = $stmt->fetchAll();

$pageTitle = 'Security & Audit Logs';
$activeMenu = 'settings';
$activeSub = 'audit-logs';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-shield-halved"></i> Immutable System Audit Trail (অডিট লগ)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Chronological activity log recording operator actions, IP addresses, and data changes.</p>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>User</th>
          <th>Action</th>
          <th>Module</th>
          <th>Record ID</th>
          <th>IP Address</th>
          <th>Remarks</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:2.5rem;">No audit logs registered yet.</td></tr>
        <?php else: ?>
          <?php foreach ($logs as $l): ?>
            <tr>
              <td style="font-size:0.8rem;color:var(--text-muted);"><?= date('d-m-Y H:i:s', strtotime($l['created_at'])) ?></td>
              <td style="font-weight:600;"><?= e($l['user_name'] ?: 'System') ?></td>
              <td><code><?= e($l['action']) ?></code></td>
              <td><span class="badge badge-info"><?= e($l['module']) ?></span></td>
              <td>#<?= $l['record_id'] ?: '-' ?></td>
              <td><small><?= e($l['ip_address']) ?></small></td>
              <td style="font-size:0.8rem;color:var(--text-secondary);"><?= e($l['notes'] ?: '-') ?></td>
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
