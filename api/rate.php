<?php
/** 5-star rating on media (one rating per user per item, can be updated) */
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf_json();
$user = require_login_json();
$cid = (int)($_POST['content_id'] ?? 0);
$stars = (int)($_POST['stars'] ?? 0);
if ($stars < 1 || $stars > 5) json_out(['ok' => false, 'error' => 'Rating must be 1–5 stars'], 422);
$title = q_val("SELECT title FROM contents WHERE content_id=? AND status='published'", [$cid]);
if ($title === false) json_out(['ok' => false, 'error' => 'Item not found'], 404);

q('INSERT INTO ratings (user_id, content_id, stars) VALUES (?,?,?) ON DUPLICATE KEY UPDATE stars=VALUES(stars), created_at=NOW()', [$user['user_id'], $cid, $stars]);
// popularity nudges towards highly-rated media
q('UPDATE contents SET popularity = LEAST(100, GREATEST(1, ROUND((popularity * 4 + ?*20) / 5))) WHERE content_id=?', [$stars, $cid]);
log_activity((int)$user['user_id'], 'Rated', $title . ' — ' . $stars . '★');
$sum = rating_summary($cid);
json_out(['ok' => true, 'stars' => $stars, 'avg' => $sum['avg'], 'count' => $sum['count'], 'message' => 'Thanks for rating!']);
