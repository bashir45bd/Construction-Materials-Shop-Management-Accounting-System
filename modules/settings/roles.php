<?php
/**
 * Role-Based Access Control (RBAC) Matrix
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Database\DB;
use App\Helpers\Csrf;

Auth::requireLogin();
Auth::requirePermission('users.manage');

$shopId = Auth::shopId();
$pdo = DB::pdo();

// Handle Permission Matrix Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $roleId = (int)($_POST['role_id'] ?? 0);
    $selectedPerms = (array)($_POST['permissions'] ?? []);

    if ($roleId > 0) {
        $pdo->beginTransaction();
        try {
            // Clear existing permissions for this role
            $del = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = :rid");
            $del->execute(['rid' => $roleId]);

            // Re-insert selected
            $ins = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (:rid, :pid)");
            foreach ($selectedPerms as $permId) {
                $ins->execute(['rid' => $roleId, 'pid' => (int)$permId]);
            }
            $pdo->commit();
            flash('success', "Role permissions matrix updated successfully.");
        } catch (\Throwable $e) {
            $pdo->rollBack();
            flash('error', "Failed to update permissions: " . $e->getMessage());
        }
        redirect(BASE_URL . '/modules/settings/roles.php?role_id=' . $roleId);
    }
}

$roles = $pdo->query("SELECT * FROM roles WHERE shop_id = {$shopId} ORDER BY id ASC")->fetchAll();
$selectedRoleId = (int)($_GET['role_id'] ?? ($roles[0]['id'] ?? 1));

// Permissions grouped by module
$allPerms = $pdo->query("SELECT * FROM permissions ORDER BY module ASC, id ASC")->fetchAll();
$groupedPerms = [];
foreach ($allPerms as $p) {
    $groupedPerms[$p['module']][] = $p;
}

// Current Role's Active Permissions
$stmtRolePerms = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = :rid");
$stmtRolePerms->execute(['rid' => $selectedRoleId]);
$activePermIds = $stmtRolePerms->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Roles & Permission Matrix';
$activeMenu = 'settings';
$activeSub = 'users';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-user-shield"></i> Role-Based Access Control (RBAC)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Configure granular module permissions for Salesmen, Accountants, and Managers.</p>
    </div>
  </div>

  <!-- Role Selector Tabs -->
  <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1.5rem;border-bottom:1px solid var(--border-color);padding-bottom:0.75rem;">
    <?php foreach ($roles as $r): ?>
      <a href="?role_id=<?= $r['id'] ?>" class="btn btn-sm <?= $selectedRoleId === (int)$r['id'] ? 'btn-primary' : 'btn-secondary' ?>">
        <?= e($r['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <form method="POST" action="">
    <?= Csrf::field() ?>
    <input type="hidden" name="role_id" value="<?= $selectedRoleId ?>">

    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:1.5rem;margin-bottom:1.5rem;">
      <?php foreach ($groupedPerms as $module => $perms): ?>
        <div style="background:var(--bg-surface-secondary);border:1px solid var(--border-color);border-radius:var(--radius);padding:1rem;">
          <div style="font-weight:800;text-transform:uppercase;font-size:0.85rem;margin-bottom:0.75rem;color:var(--primary);border-bottom:1px solid var(--border-color);padding-bottom:4px;">
            <?= ucfirst($module) ?> Module
          </div>

          <?php foreach ($perms as $p): ?>
            <label style="display:flex;align-items:flex-start;gap:0.5rem;margin-bottom:0.5rem;font-size:0.85rem;cursor:pointer;">
              <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" <?= in_array($p['id'], $activePermIds, true) ? 'checked' : '' ?> style="margin-top:2px;">
              <div>
                <div style="font-weight:600;"><?= e($p['name']) ?></div>
                <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($p['slug']) ?></div>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="submit" class="btn btn-primary btn-lg">
      <i class="fa fa-save"></i> Save Role Permissions Matrix
    </button>
  </form>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
