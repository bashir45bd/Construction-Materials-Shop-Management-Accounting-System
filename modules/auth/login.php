<?php
/**
 * User Login Page
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

require_once dirname(dirname(__DIR__)) . '/config/app.php';

use App\Auth\Auth;
use App\Helpers\Csrf;

// If already logged in, redirect to dashboard
if (Auth::check()) {
    redirect(BASE_URL . '/modules/dashboard/');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!Csrf::validate($token)) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $username = cleanInput($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } elseif (Auth::attempt($username, $password)) {
            redirect(BASE_URL . '/modules/dashboard/');
        } else {
            $error = 'Invalid username, password, or account inactive.';
        }
    }
}

$shop = getShop();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | <?= e($shop['name']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dark-mode.css">
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: radial-gradient(circle at top right, rgba(37,99,235,0.08), transparent), var(--bg-app);
      padding: 1rem;
    }
    .login-box {
      width: 100%;
      max-width: 420px;
      background: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-lg);
      padding: 2.25rem 2rem;
    }
    .login-logo {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      border-radius: var(--radius-md);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin: 0 auto 1rem;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <div style="text-align:center;margin-bottom:1.75rem;">
      <div class="login-logo"><i class="fa fa-cubes"></i></div>
      <h2 style="font-size:1.35rem;font-weight:800;letter-spacing:-0.025em;"><?= e($shop['name']) ?></h2>
      <p style="font-size:0.85rem;color:var(--text-secondary);margin-top:4px;">Construction Materials & Accounting ERP</p>
    </div>

    <?php if ($error): ?>
      <div style="background:var(--danger-light);color:var(--danger);padding:0.75rem 1rem;border-radius:var(--radius);font-size:0.85rem;font-weight:600;margin-bottom:1.25rem;border-left:4px solid var(--danger);">
        <i class="fa fa-circle-exclamation"></i> <?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <?= Csrf::field() ?>

      <div class="form-group">
        <label class="form-label" for="username"><i class="fa fa-user"></i> Username or Email</label>
        <input type="text" name="username" id="username" class="form-control" placeholder="admin" required autofocus>
      </div>

      <div class="form-group" style="margin-bottom:1.5rem;">
        <label class="form-label" for="password"><i class="fa fa-lock"></i> Password</label>
        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;font-size:0.95rem;padding:0.75rem;">
        <i class="fa fa-right-to-bracket"></i> Sign In to Account
      </button>
    </form>

    <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border-color);font-size:0.8rem;color:var(--text-muted);text-align:center;">
      <div style="font-weight:600;color:var(--text-secondary);margin-bottom:4px;">Default Credentials:</div>
      <div>Admin: <code>admin</code> / <code>Password123!</code></div>
      <div>Sales: <code>salesman</code> / <code>Password123!</code></div>
    </div>
  </div>
</body>
</html>
