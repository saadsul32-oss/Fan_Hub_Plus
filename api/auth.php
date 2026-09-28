<?php
/**
 * Authentication API: login | register | forgot | resend | logout
 * Passwords: bcrypt (password_hash). Tokens: random 32 bytes, stored as SHA-256 hash.
 */
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf_json();

$action = $_POST['action'] ?? '';
$email  = strtolower(trim($_POST['email'] ?? ''));

function demo_link(string $link): array { return MAIL_DEMO ? ['demo_link' => $link] : []; }

function issue_verify(array $user): string
{
    $token = bin2hex(random_bytes(32));
    q('UPDATE users SET verify_token=? WHERE user_id=?', [hash('sha256', $token), $user['user_id']]);
    $link = absolute_url('verify.php?token=' . $token);
    send_mail($user['email'], 'Verify your Fan Hub Plus account',
        "Hi {$user['name']},\n\nWelcome to the Fandom Universe! Confirm your email address by opening this link:\n$link\n\n— Fan Hub Plus");
    return $link;
}

switch ($action) {
    case 'login':
        $_SESSION['login_fails'] = $_SESSION['login_fails'] ?? 0;
        if ($_SESSION['login_fails'] >= 5 && time() - ($_SESSION['login_last_fail'] ?? 0) < 60) {
            json_out(['ok' => false, 'error' => 'Too many attempts. Please wait a minute and try again.'], 429);
        }
        $user = q_one('SELECT * FROM users WHERE email=?', [$email]);
        if (!$user || !password_verify($_POST['password'] ?? '', $user['password_hash'])) {
            $_SESSION['login_fails']++;
            $_SESSION['login_last_fail'] = time();
            json_out(['ok' => false, 'error' => 'Incorrect email or password.'], 422);
        }
        if ($user['status'] !== 'active') json_out(['ok' => false, 'error' => 'This account has been suspended. Contact support.'], 403);
        if (!$user['email_verified']) json_out(['ok' => false, 'unverified' => true, 'error' => 'Please verify your email first. Check your inbox for the link.'], 403);
        $_SESSION['login_fails'] = 0;
        login_user($user);
        log_activity((int)$user['user_id'], 'Signed in', 'Welcome back to the Fandom Universe');
        json_out(['ok' => true, 'admin' => $user['role'] === 'admin']);

    case 'register':
        $name = trim($_POST['name'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (mb_strlen($name) < 2) json_out(['ok' => false, 'error' => 'Please enter your full name.'], 422);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
        if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass))
            json_out(['ok' => false, 'error' => 'Password must be at least 8 characters and include a letter and a number.'], 422);
        if (q_val('SELECT 1 FROM users WHERE email=?', [$email])) json_out(['ok' => false, 'error' => 'An account with this email already exists.'], 422);

        $base = preg_replace('/[^a-z0-9._]/', '', strtolower(strstr($email, '@', true))) ?: 'fan';
        $username = $base; $n = 1;
        while (q_val('SELECT 1 FROM users WHERE username=?', [$username])) $username = $base . (++$n);

        q('INSERT INTO users (name, username, email, password_hash) VALUES (?,?,?,?)', [$name, $username, $email, password_hash($pass, PASSWORD_BCRYPT)]);
        $user = q_one('SELECT * FROM users WHERE user_id=?', [db()->lastInsertId()]);
        $link = issue_verify($user);
        json_out(['ok' => true, 'message' => 'Account created! We sent a verification link to ' . $email . '.'] + demo_link($link));

    case 'resend':
        $user = q_one('SELECT * FROM users WHERE email=? AND email_verified=0', [$email]);
        $extra = [];
        if ($user) $extra = demo_link(issue_verify($user));
        json_out(['ok' => true, 'message' => 'If that account needs verification, a new link is on its way.'] + $extra);

    case 'forgot':
        $user = q_one('SELECT * FROM users WHERE email=?', [$email]);
        $extra = [];
        if ($user) {
            $token = bin2hex(random_bytes(32));
            q('UPDATE users SET reset_token=?, reset_expires=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE user_id=?', [hash('sha256', $token), $user['user_id']]);
            $link = absolute_url('reset_password.php?token=' . $token);
            send_mail($user['email'], 'Reset your Fan Hub Plus password',
                "Hi {$user['name']},\n\nUse this link within 1 hour to choose a new password:\n$link\n\nIf you didn't request this, ignore this email.");
            $extra = demo_link($link);
        }
        // Same message either way (does not reveal which emails are registered)
        json_out(['ok' => true, 'message' => 'If an account exists for that email, a reset link has been sent.'] + $extra);

    case 'logout':
        logout_user();
        json_out(['ok' => true]);

    default:
        json_out(['ok' => false, 'error' => 'Unknown action'], 400);
}
