<?php
/**
 * ORA chatbot API
 *   GET  ?action=history            last messages of this visitor / member (rich cards restored)
 *   POST action=send  message=...   reply with text + cards + quick actions (stored in chatbot_queries)
 *   POST action=feedback id= value=1|0   thumbs up / down on an answer
 *   POST action=clear               start a fresh conversation (history kept for admin analytics)
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/chatbot_engine.php';
ensure_schema();

$user = current_user();
$uid  = $user ? (int)$user['user_id'] : null;
if (empty($_SESSION['chat_sid'])) $_SESSION['chat_sid'] = bin2hex(random_bytes(16));
$sid  = $_SESSION['chat_sid'];
$after = (int)($_SESSION['chat_cleared_after'] ?? 0);

$action = $_REQUEST['action'] ?? 'send';

if ($action === 'history') {
    $sql = 'SELECT query_id, message, response, meta, helpful FROM (SELECT * FROM chatbot_queries WHERE %s AND query_id > ? ORDER BY query_id DESC LIMIT 20) t ORDER BY query_id';
    $rows = $uid ? q_all(sprintf($sql, 'user_id = ?'), [$uid, $after]) : q_all(sprintf($sql, 'session_id = ?'), [$sid, $after]);
    $out = [];
    foreach ($rows as $r) {
        $meta = json_decode((string)$r['meta'], true) ?: [];
        $out[] = ['id' => (int)$r['query_id'], 'message' => $r['message'], 'reply' => $r['response'],
                  'cards' => $meta['cards'] ?? [], 'helpful' => $r['helpful'] === null ? null : (int)$r['helpful']];
    }
    json_out(['ok' => true, 'history' => $out, 'ai' => ora_ai_enabled()]);
}

require_post_csrf_json();

if ($action === 'clear') {
    $_SESSION['chat_cleared_after'] = (int)q_val('SELECT COALESCE(MAX(query_id),0) FROM chatbot_queries');
    unset($_SESSION['chat_tour']);
    json_out(['ok' => true]);
}

if ($action === 'feedback') {
    $id = (int)($_POST['id'] ?? 0);
    $val = ($_POST['value'] ?? '') === '1' ? 1 : 0;
    // only the owner of the exchange can rate it
    $owner = $uid ? 'user_id = ?' : 'session_id = ?';
    q("UPDATE chatbot_queries SET helpful = ? WHERE query_id = ? AND $owner", [$val, $id, $uid ?: $sid]);
    json_out(['ok' => true, 'message' => $val ? 'Thanks for the feedback!' : 'Thanks — we\'ll use this to improve ORA.']);
}

/* ---- send ---- */
$msg = trim(preg_replace('/\s+/u', ' ', (string)($_POST['message'] ?? '')));
$msg = mb_substr($msg, 0, 500);
if ($msg === '') json_out(['ok' => false, 'error' => 'Type a message first.'], 422);

// simple per-visitor rate limit (sliding one-minute window)
$now = time();
$_SESSION['chat_times'] = array_values(array_filter($_SESSION['chat_times'] ?? [], fn($t) => $t > $now - 60));
if (count($_SESSION['chat_times']) >= CHAT_RATE_LIMIT) {
    json_out(['ok' => false, 'error' => 'Whoa, that was fast! Give me a few seconds and try again.'], 429);
}
$_SESSION['chat_times'][] = $now;

try {
    $r = ora_reply($msg, $user);
} catch (Throwable $ex) {
    error_log('ORA error: ' . $ex->getMessage());
    $r = ['reply' => 'Something went wrong on my side — please try again in a moment.', 'cards' => [], 'actions' => [], 'intent' => 'error', 'answered' => false];
}

$meta = json_encode(['cards' => $r['cards']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
q('INSERT INTO chatbot_queries (user_id, session_id, message, response, intent, answered, meta) VALUES (?,?,?,?,?,?,?)',
  [$uid, $sid, $msg, $r['reply'], $r['intent'], $r['answered'] ? 1 : 0, $meta]);
$id = (int)db()->lastInsertId();

json_out(['ok' => true, 'id' => $id, 'reply' => $r['reply'], 'cards' => $r['cards'], 'actions' => $r['actions'], 'intent' => $r['intent']]);
