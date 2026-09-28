<?php
/**
 * Fan Hub Plus — core helpers (DB, session, security, formatting)
 */
require_once __DIR__ . '/config.php';

/* ------------------------------------------------------------------ *
 *  Session (secure cookie flags)
 * ------------------------------------------------------------------ */
if (session_status() === PHP_SESSION_NONE) {
    session_name('FANHUBSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => BASE_URL,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/* ------------------------------------------------------------------ *
 *  Database (PDO singleton, prepared statements everywhere)
 * ------------------------------------------------------------------ */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            // keep MySQL NOW() in sync with PHP's timezone (Asia/Karachi)
            $pdo->exec("SET time_zone = '" . date('P') . "'");
        } catch (PDOException $ex) {
            http_response_code(500);
            echo '<div style="font-family:sans-serif;padding:40px;background:#050508;color:#fff;min-height:100vh">'
               . '<h2>Database connection failed</h2><p>Please import <code>database/fanhub_plus.sql</code> '
               . 'and check credentials in <code>includes/config.php</code>.</p></div>';
            exit;
        }
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function q_all(string $sql, array $params = []): array { return q($sql, $params)->fetchAll(); }
function q_one(string $sql, array $params = []): ?array { $r = q($sql, $params)->fetch(); return $r ?: null; }
function q_val(string $sql, array $params = []) { return q($sql, $params)->fetchColumn(); }

/* ------------------------------------------------------------------ *
 *  Output helpers
 * ------------------------------------------------------------------ */
function e($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

function url(string $path = ''): string { return BASE_URL . ltrim($path, '/'); }

/** Images/media stored either as absolute URL or as path relative to app root. */
function media(?string $path): string
{
    $path = (string)$path;
    if ($path === '') return '';
    if (preg_match('#^(https?:)?//#i', $path) || strpos($path, 'data:') === 0) return $path;
    return url($path);
}

function icon(string $name, int $size = 24, string $class = '', array $attrs = []): string
{
    static $set = null;
    if ($set === null) $set = require __DIR__ . '/icon_set.php';
    $inner = $set[$name] ?? '';
    $fill = $attrs['fill'] ?? 'none';
    unset($attrs['fill']);
    $extra = '';
    foreach ($attrs as $k => $v) $extra .= ' ' . $k . '="' . e($v) . '"';
    $cls = trim('lucide lucide-' . $name . ' ' . $class);
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="' . e($fill)
        . '" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . e($cls) . '" aria-hidden="true"' . $extra . '>'
        . $inner . '</svg>';
}

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fmt_date(?string $d, string $format = 'M j, Y'): string
{
    if (!$d) return '';
    $t = strtotime($d);
    return $t ? date($format, $t) : '';
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 86400 * 30) return floor($diff / 86400) . 'd ago';
    return date('M j', strtotime($datetime));
}

/** "Label | Event" lines -> [['year'=>..,'event'=>..]] */
function parse_timeline(?string $text): array
{
    $out = [];
    foreach (preg_split('/\r\n|\r|\n/', (string)$text) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = array_map('trim', explode('|', $line, 2));
        $out[] = ['year' => $parts[0], 'event' => $parts[1] ?? ''];
    }
    return $out;
}

function parse_lines(?string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$text))));
}

function paragraphs(?string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', str_replace("\r", '', (string)$text)))));
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $i = strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
    return $i !== '' ? $i : 'FH';
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (q_all('SELECT setting_key, setting_value FROM settings') as $r) $cache[$r['setting_key']] = $r['setting_value'];
    }
    return $cache[$key] ?? $default;
}

/** Old demo media links that no longer work -> files bundled in assets/media/ */
function legacy_media_map(): array
{
    return [
        'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4'   => 'assets/media/video/big-buck-bunny.mp4',
        'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4'   => 'assets/media/video/here-we-are.mp4',
        'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4'         => 'assets/media/video/sintel-part-1.mp4',
        'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4' => 'assets/media/video/sintel-part-2.mp4',
        'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4'             => 'assets/media/video/flower.mp4',
        'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3'                        => 'assets/media/audio/chasing-corporate.mp3',
        'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3'                        => 'assets/media/audio/outfoxing.mp3',
        'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3'                        => 'assets/media/audio/sintel-score.mp3',
        'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-4.mp3'                        => 'assets/media/audio/viper.mp3',
    ];
}

/** Playable URL for a video / audio file (local path, full URL, or an old demo link) */
function media_src(?string $u): string
{
    $u = trim((string)$u);
    $map = legacy_media_map();
    $key = preg_replace('#^http://#i', 'https://', $u);
    if (isset($map[$key])) $u = $map[$key];
    return media($u);
}

/** YouTube / Vimeo link -> embed URL, otherwise null (direct file). */
function embed_url(?string $u): ?string
{
    $u = (string)$u;
    if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $u, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
    }
    if (preg_match('~vimeo\.com/(\d+)~', $u, $m)) return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1';
    return null;
}

/* ------------------------------------------------------------------ *
 *  CSRF
 * ------------------------------------------------------------------ */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_valid(): bool
{
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}
function require_post_csrf_json(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'error' => 'Method not allowed'], 405);
    if (!csrf_valid()) json_out(['ok' => false, 'error' => 'Your session expired. Please refresh the page.'], 419);
}

/* ------------------------------------------------------------------ *
 *  Auth
 * ------------------------------------------------------------------ */
function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $user = q_one('SELECT * FROM users WHERE user_id = ? AND status = "active"', [$_SESSION['uid']]);
            if (!$user) unset($_SESSION['uid']);
        }
    }
    return $user;
}
function is_logged_in(): bool { return current_user() !== null; }
function is_admin(): bool { $u = current_user(); return $u && $u['role'] === 'admin'; }

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['user_id'];
    q('UPDATE users SET last_login = NOW() WHERE user_id = ?', [$user['user_id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_login_json(): array
{
    $u = current_user();
    if (!$u) json_out(['ok' => false, 'auth' => true, 'error' => 'Please sign in to use this feature.'], 401);
    return $u;
}

function require_admin_page(): array
{
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
    return $u;
}

function log_activity(int $userId, string $action, string $detail): void
{
    q('INSERT INTO activity_log (user_id, action, detail) VALUES (?,?,?)', [$userId, $action, mb_substr($detail, 0, 255)]);
}

/* ------------------------------------------------------------------ *
 *  Site-wide control (admin-editable, driven entirely by the
 *  key/value `settings` table — see admin "Site Control" tab)
 * ------------------------------------------------------------------ */
function site_name(): string { return setting('platform_name', 'Fan Hub Plus'); }

/** Is maintenance mode switched on in the admin Site Control tab? */
function maintenance_active(): bool { return setting('maintenance_mode', '0') === '1'; }

/**
 * Blocks every public page while maintenance mode is on, without
 * touching each page individually: called once, automatically, at
 * the bottom of this file (which every page includes). Admins and
 * the /admin/ and /api/ paths are always let through so the site can
 * still be managed and logged into while it's down for everyone else.
 */
function maintenance_gate(): void
{
    if (!maintenance_active()) return;
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script, '/admin/') !== false || strpos($script, '/api/') !== false) return;
    if (is_admin()) return;
    http_response_code(503);
    header('Retry-After: 3600');
    $name = e(site_name());
    $msg = e(setting('maintenance_message', "We're making some updates behind the scenes. Please check back soon."));
    echo '<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>' . $name . ' — Down for maintenance</title>'
       . '<style>body{margin:0;min-height:100svh;display:flex;align-items:center;justify-content:center;background:#05050a;color:#fff;'
       . 'font-family:Manrope,system-ui,sans-serif;text-align:center;padding:32px}'
       . '.box{max-width:480px}h1{font-family:"Bebas Neue",sans-serif;letter-spacing:.04em;font-size:2.75rem;margin:0 0 12px}'
       . 'p{color:rgba(255,255,255,.6);line-height:1.6;margin:0}</style></head><body>'
       . '<div class="box"><h1>' . $name . '</h1><p>' . nl2br($msg) . '</p></div></body></html>';
    exit;
}

/* ------------------------------------------------------------------ *
 *  Mail (with demo outbox for XAMPP)
 * ------------------------------------------------------------------ */
function absolute_url(string $path): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}

function send_mail(string $to, string $subject, string $body): void
{
    q('INSERT INTO mail_outbox (to_email, subject, body) VALUES (?,?,?)', [$to, $subject, $body]);
    $headers = 'From: ' . MAIL_FROM . "\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($to, $subject, $body, $headers);
}

/* ------------------------------------------------------------------ *
 *  Uploads (images only, validated by MIME + size, random file names)
 * ------------------------------------------------------------------ */
function save_upload(array $file, string $dir): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > UPLOAD_MAX_BYTES) throw new RuntimeException('Image must be smaller than 3 MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$mime] ?? null;
    if (!$ext) throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.');
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    $rel = 'uploads/' . trim($dir, '/') . '/' . $name;
    if (!is_dir(APP_ROOT . '/uploads/' . $dir)) mkdir(APP_ROOT . '/uploads/' . $dir, 0775, true);
    if (!move_uploaded_file($file['tmp_name'], APP_ROOT . '/' . $rel)) throw new RuntimeException('Upload failed.');
    return $rel;
}

/* ------------------------------------------------------------------ *
 *  Domain helpers
 * ------------------------------------------------------------------ */
function categories(): array
{
    static $cats = null;
    if ($cats === null) $cats = q_all('SELECT * FROM categories ORDER BY sort_order, category_id');
    return $cats;
}

function user_bookmark_keys(?int $uid): array
{
    if (!$uid) return [];
    $keys = [];
    foreach (q_all('SELECT item_type, item_id FROM bookmarks WHERE user_id = ?', [$uid]) as $b) $keys[$b['item_type'] . ':' . $b['item_id']] = true;
    return $keys;
}

function content_type_label(string $type): string
{
    return ['article' => 'Article', 'video' => 'Video', 'trailer' => 'Trailer', 'audio' => 'Audio'][$type] ?? ucfirst($type);
}

function content_type_icon(string $type): string
{
    return ['article' => 'book-open', 'video' => 'play', 'trailer' => 'clapperboard', 'audio' => 'music-2'][$type] ?? 'book-open';
}

/** Rating summary for a content item */
function rating_summary(int $contentId): array
{
    $r = q_one('SELECT COUNT(*) c, COALESCE(AVG(stars),0) a FROM ratings WHERE content_id = ?', [$contentId]);
    return ['count' => (int)$r['c'], 'avg' => round((float)$r['a'], 1)];
}

/* ------------------------------------------------------------------ *
 *  Characters
 * ------------------------------------------------------------------ */
/** "Anime · Ashen Requiem" -> "Ashen Requiem" (falls back to the full label) */
function character_fandom(array $c): string
{
    $label = (string)($c['fandom_label'] ?? '');
    $parts = preg_split('/\s*[·|•]\s*/u', $label, 2);
    $f = trim($parts[1] ?? $parts[0] ?? '');
    return $f !== '' ? $f : trim($label);
}

/** Two-digit counter, e.g. 1 -> "01" */
function pad2(int $n): string { return str_pad((string)$n, 2, '0', STR_PAD_LEFT); }

/* ------------------------------------------------------------------ *
 *  Lightweight schema upgrades (safe to run on every request — cached)
 *  Lets an existing v1 database pick up the columns used by v2
 *  features without re-importing the SQL file.
 * ------------------------------------------------------------------ */
function ensure_schema(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    $version = '5';
    try {
        if (setting('schema_version') === $version) return;
        $has = function (string $table, string $col): bool {
            return (bool)q_val('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$table, $col]);
        };
        if (!$has('chatbot_queries', 'intent'))   db()->exec("ALTER TABLE chatbot_queries ADD COLUMN intent VARCHAR(40) NOT NULL DEFAULT 'faq' AFTER response");
        if (!$has('chatbot_queries', 'answered')) db()->exec('ALTER TABLE chatbot_queries ADD COLUMN answered TINYINT(1) NOT NULL DEFAULT 1 AFTER intent');
        if (!$has('chatbot_queries', 'helpful'))  db()->exec('ALTER TABLE chatbot_queries ADD COLUMN helpful TINYINT(1) NULL AFTER answered');
        if (!$has('chatbot_queries', 'meta'))     db()->exec('ALTER TABLE chatbot_queries ADD COLUMN meta TEXT NULL AFTER helpful');
        foreach (['chatbot_name' => 'ORA', 'chatbot_welcome' => "Hey, I'm ORA — your Fandom Universe guide. Ask me about characters, trending content, events, merch or recommendations.", 'chatbot_ai' => '0'] as $k => $v) {
            q('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?,?)', [$k, $v]);
        }
        // v4: old demo video / audio links stopped working -> point them to the bundled files
        foreach (legacy_media_map() as $old => $new) {
            q('UPDATE contents SET media_url = ? WHERE media_url IN (?, ?)', [$new, $old, preg_replace('#^https://#', 'http://', $old)]);
        }
        // v5: full site control from the admin dashboard (hero, footer, SEO, socials, banner, maintenance)
        foreach ([
            'maintenance_mode' => '0', 'maintenance_message' => "We're making some updates behind the scenes. Please check back soon.",
            'announcement_enabled' => '0', 'announcement_text' => '', 'announcement_link' => '',
            'seo_title' => 'FAN HUB PLUS — Enter the Fandom Universe',
            'seo_description' => 'FAN HUB PLUS — Enter the Fandom Universe. A cinematic, immersive platform for Anime, Gaming, Movies, TV, K-Pop, Comics, Manga and Cosplay.',
            'hero_eyebrow' => 'Fandom Universe Now Open', 'hero_subtitle' => 'ENTER THE FANDOM UNIVERSE', 'hero_image' => '',
            'footer_tagline' => 'A premium digital universe built for fandom culture.',
            'social_twitter' => '', 'social_instagram' => '', 'social_youtube' => '', 'social_discord' => '', 'social_tiktok' => '',
        ] as $k => $v) {
            q('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?,?)', [$k, $v]);
        }
        q('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', ['schema_version', $version]);
    } catch (Throwable $ex) {
        // never break the page because of an upgrade problem
    }
}

/* Every page (public or otherwise) requires this file, so gating here
   — instead of at the top of every individual .php entry point —
   guarantees maintenance mode is enforced everywhere it should be. */
maintenance_gate();
