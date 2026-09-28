<?php
/** Add / remove an event from the user's personal calendar */
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf_json();
$user = require_login_json();
$eid = (int)($_POST['event_id'] ?? 0);
$title = q_val('SELECT title FROM events WHERE event_id=?', [$eid]);
if ($title === false) json_out(['ok' => false, 'error' => 'Event not found'], 404);

if (q_val('SELECT 1 FROM event_saves WHERE user_id=? AND event_id=?', [$user['user_id'], $eid])) {
    q('DELETE FROM event_saves WHERE user_id=? AND event_id=?', [$user['user_id'], $eid]);
    json_out(['ok' => true, 'added' => false]);
}
q('INSERT INTO event_saves (user_id, event_id) VALUES (?,?)', [$user['user_id'], $eid]);
log_activity((int)$user['user_id'], 'Saved event', $title);
json_out(['ok' => true, 'added' => true, 'message' => 'Added to your calendar']);
