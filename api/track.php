<?php
/** View counter for inline-played media (featured trailer / audio). Feeds popularity analytics. */
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf_json();
$id = (int)($_POST['id'] ?? 0);
$row = q_one("SELECT title, type FROM contents WHERE content_id=? AND status='published'", [$id]);
if (!$row) json_out(['ok' => false], 404);
q('UPDATE contents SET views = views + 1 WHERE content_id=?', [$id]);
if ($u = current_user()) log_activity((int)$u['user_id'], $row['type'] === 'audio' ? 'Listened' : 'Watched', $row['title']);
json_out(['ok' => true]);
