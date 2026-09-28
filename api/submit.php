<?php
/** Fan content submission — stored as `pending`, goes live only after admin approval */
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf_json();
$user = require_login_json();

$title = trim($_POST['title'] ?? '');
$body = trim($_POST['body'] ?? '');
$excerpt = trim($_POST['excerpt'] ?? '');
$genre = trim($_POST['genre'] ?? '');
$cat = (int)($_POST['category_id'] ?? 0);

if (mb_strlen($title) < 5) json_out(['ok' => false, 'error' => 'Title must be at least 5 characters.'], 422);
if (mb_strlen($body) < 50) json_out(['ok' => false, 'error' => 'Your article needs at least 50 characters.'], 422);
if (!q_val('SELECT 1 FROM categories WHERE category_id=?', [$cat])) json_out(['ok' => false, 'error' => 'Choose a category.'], 422);
if (q_val("SELECT COUNT(*) FROM contents WHERE submitted_by=? AND status='pending'", [$user['user_id']]) >= 5)
    json_out(['ok' => false, 'error' => 'You already have 5 submissions waiting for review.'], 429);

try {
    $image = !empty($_FILES['image']) ? save_upload($_FILES['image'], 'media') : null;
} catch (RuntimeException $ex) {
    json_out(['ok' => false, 'error' => $ex->getMessage()], 422);
}
$image = $image ?: q_val('SELECT image FROM categories WHERE category_id=?', [$cat]);
$words = str_word_count(strip_tags($body));
q("INSERT INTO contents (category_id, title, type, genre, image, excerpt, body, read_time, release_date, popularity, status, submitted_by)
   VALUES (?,?,?,?,?,?,?,?,CURDATE(),40,'pending',?)", [
    $cat, mb_substr($title, 0, 200), 'article', mb_substr($genre, 0, 60), $image,
    mb_substr($excerpt ?: mb_substr($body, 0, 140) . '…', 0, 255), $body, max(1, (int)ceil($words / 200)) . ' min read', $user['user_id'],
]);
log_activity((int)$user['user_id'], 'Submitted', $title);
json_out(['ok' => true, 'message' => 'Submitted! An admin will review your article soon.']);
