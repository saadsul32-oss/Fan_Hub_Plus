<?php
/** Email verification via tokenised link */
require_once __DIR__ . '/includes/functions.php';
$token = $_GET['token'] ?? '';
$ok = false;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $user = q_one('SELECT * FROM users WHERE verify_token=?', [hash('sha256', $token)]);
    if ($user) {
        q('UPDATE users SET email_verified=1, verify_token=NULL WHERE user_id=?', [$user['user_id']]);
        login_user($user);
        log_activity((int)$user['user_id'], 'Joined', 'Welcome to the Fandom Universe');
        $ok = true;
    }
}
$pageTitle = 'Verify email — FAN HUB PLUS';
include __DIR__ . '/includes/partials/head.php';
?>
<main class="flex min-h-screen items-center justify-center bg-void p-4">
  <div class="w-full max-w-md rounded-3xl glass p-8 text-center">
    <p class="text-xs uppercase tracking-[0.3em] text-cyan">Fandom Universe Access</p>
    <h1 class="mt-2 font-display text-4xl text-white"><?= $ok ? 'Email verified' : 'Link expired' ?></h1>
    <p class="mt-3 text-sm text-white/60"><?= $ok ? 'Your account is active and you are now signed in.' : 'This verification link is invalid or was already used. Try signing in to request a new one.' ?></p>
    <a href="<?= e(url()) ?>" class="magnetic-btn mt-7 inline-block rounded-full bg-white px-8 py-3 text-sm font-semibold uppercase tracking-widest text-black hover:bg-violet hover:text-white">Enter Universe</a>
  </div>
</main>
</body></html>
