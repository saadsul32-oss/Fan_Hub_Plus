<?php
/** Password reset via tokenised link (valid for 1 hour) */
require_once __DIR__ . '/includes/functions.php';
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$user = preg_match('/^[a-f0-9]{64}$/', $token)
    ? q_one('SELECT * FROM users WHERE reset_token=? AND reset_expires > NOW()', [hash('sha256', $token)])
    : null;
$error = ''; $done = false;

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $p1 = $_POST['password'] ?? ''; $p2 = $_POST['confirm'] ?? '';
    if (!csrf_valid()) $error = 'Session expired, please try again.';
    elseif (strlen($p1) < 8 || !preg_match('/[A-Za-z]/', $p1) || !preg_match('/\d/', $p1)) $error = 'Password must be at least 8 characters and include a letter and a number.';
    elseif ($p1 !== $p2) $error = 'Passwords do not match.';
    else {
        q('UPDATE users SET password_hash=?, reset_token=NULL, reset_expires=NULL, email_verified=1 WHERE user_id=?', [password_hash($p1, PASSWORD_BCRYPT), $user['user_id']]);
        $done = true;
    }
}
$pageTitle = 'Reset password — FAN HUB PLUS';
include __DIR__ . '/includes/partials/head.php';
?>
<main class="flex min-h-screen items-center justify-center bg-void p-4">
  <div class="w-full max-w-md rounded-3xl glass p-8">
    <p class="text-xs uppercase tracking-[0.3em] text-cyan">Fandom Universe Access</p>
    <?php if ($done): ?>
      <h1 class="mt-2 font-display text-4xl text-white">Password updated</h1>
      <p class="mt-3 text-sm text-white/60">You can now sign in with your new password.</p>
      <a href="<?= e(url()) ?>" class="magnetic-btn mt-7 inline-block rounded-full bg-white px-8 py-3 text-sm font-semibold uppercase tracking-widest text-black hover:bg-violet hover:text-white">Back to Fan Hub</a>
    <?php elseif (!$user): ?>
      <h1 class="mt-2 font-display text-4xl text-white">Link expired</h1>
      <p class="mt-3 text-sm text-white/60">This reset link is invalid or older than one hour. Request a new one from the login screen.</p>
      <a href="<?= e(url()) ?>" class="magnetic-btn mt-7 inline-block rounded-full bg-white px-8 py-3 text-sm font-semibold uppercase tracking-widest text-black hover:bg-violet hover:text-white">Back to Fan Hub</a>
    <?php else: ?>
      <h1 class="mt-2 font-display text-4xl text-white">Choose a new password</h1>
      <p class="mt-2 text-sm text-white/50">For <?= e($user['email']) ?></p>
      <form method="post" class="mt-7 space-y-4">
        <?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
          <?= icon('lock', 16, 'text-white/40') ?>
          <input required type="password" name="password" placeholder="New password" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
          <?= icon('lock', 16, 'text-white/40') ?>
          <input required type="password" name="confirm" placeholder="Confirm password" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
        </div>
        <?php if ($error): ?><p class="rounded-xl border border-red-400/30 bg-red-500/10 px-4 py-3 text-xs text-red-300"><?= e($error) ?></p><?php endif; ?>
        <button class="magnetic-btn mt-2 w-full rounded-full bg-white py-3 text-sm font-semibold uppercase tracking-widest text-black transition hover:bg-violet hover:text-white">Update password</button>
      </form>
    <?php endif; ?>
  </div>
</main>
</body></html>
