<?php
/**
 * System Users & Role Assignments
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

// Handle User Create
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verifyOrDie();
    $username = cleanInput($_POST['username'] ?? '');
    $fullname = cleanInput($_POST['full_name'] ?? '');
    $email    = cleanInput($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $roleId   = (int)($_POST['role_id'] ?? 4);

    if (!empty($username) && !empty($fullname) && !empty($password)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (shop_id, username, email, password_hash, full_name, status) VALUES (:s, :u, :e, :p, :fn, 'active')");
        $stmt->execute(['s' => $shopId, 'u' => $username, 'e' => $email, 'p' => $hash, 'fn' => $fullname]);
        $newUid = (int)$pdo->lastInsertId();

        $stmtRole = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)");
        $stmtRole->execute(['uid' => $newUid, 'rid' => $roleId]);

        flash('success', "User \"{$fullname}\" created.");
        redirect(BASE_URL . '/modules/settings/users.php');
    }
}

$sql = "SELECT u.*, r.name as role_name 
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        WHERE u.shop_id = :s ORDER BY u.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute(['s' => $shopId]);
$users = $stmt->fetchAll();

$roles = $pdo->query("SELECT * FROM roles WHERE shop_id = {$shopId}")->fetchAll();

$pageTitle = 'Users & RBAC Roles';
$activeMenu = 'settings';
$activeSub = 'users';

require_once INCLUDES_PATH . '/templates/header.php';
require_once INCLUDES_PATH . '/templates/sidebar.php';
require_once INCLUDES_PATH . '/templates/navbar.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><i class="fa fa-user-shield"></i> Users & Role Permissions (ব্যবহারকারী ও রোল)</h1>
      <p style="font-size:0.85rem;color:var(--text-secondary);">Manage system operators, salesmen, accountants, and permission matrices.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openModal('user-modal')">
      <i class="fa fa-user-plus"></i> + Add User
    </button>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Username</th>
          <th>Role</th>
          <th>Email</th>
          <th>Status</th>
          <th>Last Login</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td style="font-weight:700;"><?= e($u['full_name']) ?></td>
            <td><code><?= e($u['username']) ?></code></td>
            <td><span class="badge badge-info"><?= e($u['role_name'] ?: 'Staff') ?></span></td>
            <td><?= e($u['email'] ?: '-') ?></td>
            <td><span class="badge badge-success"><?= ucfirst($u['status']) ?></span></td>
            <td style="font-size:0.8rem;color:var(--text-muted);"><?= $u['last_login_at'] ? date('d-m-Y H:i', strtotime($u['last_login_at'])) : 'Never' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal-backdrop" id="user-modal">
  <div class="modal-dialog">
    <div class="modal-header">
      <div style="font-weight:800;"><i class="fa fa-user-plus"></i> Create System User</div>
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('user-modal')">&times;</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="">
        <?= Csrf::field() ?>
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="full_name" class="form-control" placeholder="e.g. Md. Shakil Ahmed" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Username *</label>
            <input type="text" name="username" class="form-control" placeholder="shakil" required>
          </div>
          <div class="form-group">
            <label class="form-label">Role *</label>
            <select name="role_id" class="form-control" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="shakil@maruftraders.com">
          </div>
        </div>
        <div class="modal-footer" style="padding-right:0;padding-bottom:0;">
          <button type="button" class="btn btn-secondary" onclick="closeModal('user-modal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/templates/footer.php';
?>
