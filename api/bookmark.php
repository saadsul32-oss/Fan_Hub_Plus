<?php
/** Bookmarks: toggle (article / video / character / merch) and personal notes */
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf_json();
$user = require_login_json();
$uid = (int)$user['user_id'];

[$type, $id] = array_pad(explode(':', $_POST['key'] ?? '', 2), 2, 0);
$id = (int)$id;
$tables = ['content' => ['contents', 'content_id', 'title'], 'character' => ['characters', 'character_id', 'name'], 'merch' => ['merchandise', 'item_id', 'name']];
if (!isset($tables[$type]) || $id <= 0) json_out(['ok' => false, 'error' => 'Invalid item'], 422);
[$table, $pk, $col] = $tables[$type];
$title = q_val("SELECT $col FROM $table WHERE $pk = ?", [$id]);
if ($title === false) json_out(['ok' => false, 'error' => 'Item not found'], 404);

if (($_POST['action'] ?? 'toggle') === 'note') {
    q('UPDATE bookmarks SET note=? WHERE user_id=? AND item_type=? AND item_id=?', [mb_substr(trim($_POST['note'] ?? ''), 0, 500) ?: null, $uid, $type, $id]);
    json_out(['ok' => true, 'message' => 'Note saved']);
}

$exists = q_val('SELECT bookmark_id FROM bookmarks WHERE user_id=? AND item_type=? AND item_id=?', [$uid, $type, $id]);
if ($exists) {
    q('DELETE FROM bookmarks WHERE bookmark_id=?', [$exists]);
    json_out(['ok' => true, 'saved' => false, 'message' => 'Removed from bookmarks']);
}
q('INSERT INTO bookmarks (user_id, item_type, item_id) VALUES (?,?,?)', [$uid, $type, $id]);
log_activity($uid, 'Bookmarked', $title);
json_out(['ok' => true, 'saved' => true, 'message' => 'Saved to your dashboard']);
