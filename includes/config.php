<?php
/**
 * Fan Hub Plus — configuration
 * Edit the database credentials below to match your XAMPP / server setup.
 */

define('DB_HOST', getenv('FH_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('FH_DB_PORT') ?: '3306');
define('DB_NAME', getenv('FH_DB_NAME') ?: 'fanhub_plus');
define('DB_USER', getenv('FH_DB_USER') ?: 'root');
define('DB_PASS', getenv('FH_DB_PASS') !== false ? getenv('FH_DB_PASS') : '');   // XAMPP default: empty

/*
 * MAIL_DEMO = true  -> verification / reset links are also shown on screen and
 *                      saved in the `mail_outbox` table (XAMPP has no SMTP by default).
 * MAIL_DEMO = false -> links are sent only with PHP mail().
 */
define('MAIL_DEMO', true);
define('MAIL_FROM', 'no-reply@fanhubplus.io');

/*
 * ORA chatbot — optional AI mode (Claude by Anthropic).
 * The chatbot works fully offline from the database (knowledge base, characters, content,
 * events, merch, releases). Add an API key to let ORA also answer free-form questions,
 * then switch "AI answers" on in Admin → Chatbot. Keep the key secret (never commit it).
 */
define('CHAT_AI_KEY', getenv('FH_AI_KEY') ?: '');                      // e.g. 'sk-ant-...'
define('CHAT_AI_MODEL', getenv('FH_AI_MODEL') ?: 'claude-haiku-4-5-20251001');
define('CHAT_RATE_LIMIT', 20);                                        // messages per minute per visitor

define('APP_ROOT', dirname(__DIR__));
define('UPLOAD_MAX_BYTES', 3 * 1024 * 1024);   // 3 MB per image

// Base URL is detected automatically (works in http://localhost/fanhub/ and in a domain root).
// Override it by setting e.g. define('BASE_URL', '/fanhub/'); here if needed.
if (!defined('BASE_URL')) {
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $appRoot = realpath(APP_ROOT);
    $base = '/';
    if ($docRoot !== '' && strpos($appRoot, $docRoot) === 0) {
        $base = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
        $base = '/' . trim($base, '/') . '/';
        $base = preg_replace('#/+#', '/', $base);
    }
    define('BASE_URL', $base);
}

date_default_timezone_set('Asia/Karachi');
error_reporting(E_ALL);
ini_set('display_errors', '0');
