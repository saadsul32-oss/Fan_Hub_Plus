<?php
/** Admin login (role = admin) */
require_once __DIR__ . '/../includes/functions.php';
if (is_admin()) { header('Location: ' . url('admin/')); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['admin_fails'] = $_SESSION['admin_fails'] ?? 0;
    if (!csrf_valid()) $error = 'Session expired — please try again.';
    elseif ($_SESSION['admin_fails'] >= 5 && time() - ($_SESSION['admin_last_fail'] ?? 0) < 60) $error = 'Too many attempts. Wait a minute.';
    else {
        $u = q_one('SELECT * FROM users WHERE email=?', [strtolower(trim($_POST['email'] ?? ''))]);
        if ($u && $u['role'] === 'admin' && $u['status'] === 'active' && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            $_SESSION['admin_fails'] = 0;
            login_user($u);
            header('Location: ' . url('admin/'));
            exit;
        }
        $_SESSION['admin_fails']++; $_SESSION['admin_last_fail'] = time();
        $error = 'Invalid administrator credentials.';
    }
}
$pageTitle = 'Admin Login — FAN HUB PLUS';
$theme = 'dark';
$extraHead = '<link rel="stylesheet" href="' . e(url('admin/assets/admin.css')) . '?v=' . (@filemtime(__DIR__ . '/assets/admin.css') ?: 1) . '" />';
include __DIR__ . '/../includes/partials/head.php';
?>
<main class="adm-body adm-login">
  <section class="adm-login-brand" aria-hidden="true">
    <p>Admin console</p>
    <img src="<?= e(url('images/brand/logo-full.png')) ?>" alt="" width="720" height="385">
    <p>Manage content, characters, media, events and the fan community in one place.</p>
  </section>

  <section class="adm-login-form">
  <div class="adm-login-card">
    <img src="<?= e(url('images/brand/logo-horizontal.png')) ?>" alt="FAN HUB PLUS" class="mb-8 h-8 w-auto" width="1020" height="160">
    <div class="mb-7">
      <h1>Sign in to the admin console</h1>
      <p class="mt-1.5 text-sm text-white/50">Administrator accounts only.</p>
    </div>
    <div>
      <form method="post" class="space-y-5">
        <?= csrf_field() ?>
        <div>
          <label for="email" class="adm-label">Email</label>
          <div class="relative">
            <?= icon('mail', 15, 'pointer-events-none absolute left-3.5 top-[1.35rem] text-white/35') ?>
            <input id="email" name="email" type="email" required autofocus autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>" placeholder="you@fanhubplus.com" class="adm-input pl-10!" />
          </div>
        </div>
        <div>
          <label for="password" class="adm-label">Password</label>
          <div class="relative">
            <?= icon('lock', 15, 'pointer-events-none absolute left-3.5 top-[1.35rem] text-white/35') ?>
            <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="••••••••" class="adm-input pl-10! pr-11!" />
            <button type="button" onclick="var p=document.getElementById('password');p.type=p.type==='password'?'text':'password'" class="absolute right-2 top-[0.85rem] adm-icon-btn" aria-label="Show password"><?= icon('eye', 15) ?></button>
          </div>
        </div>
        <?php if ($error): ?><p class="flex items-center gap-2 rounded-xl border border-red-400/30 bg-red-500/10 px-3.5 py-2.5 text-xs text-red-200"><?= icon('circle-alert', 14) ?> <?= e($error) ?></p><?php endif; ?>
        <button class="adm-btn adm-btn-primary w-full py-3!">Sign in <?= icon('arrow-right', 14) ?></button>
      </form>
    </div>
    <a href="<?= e(url()) ?>" class="mt-6 flex items-center gap-2 text-sm text-white/50 hover:text-white"><?= icon('arrow-left', 13) ?> Back to site</a>
  </div>
  </section>
</main>
</body></html>
