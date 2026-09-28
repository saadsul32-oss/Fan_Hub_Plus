<?php
/** Dynamic feedback form (bug / suggestion / query) — open to visitors and members */
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf_json();
$user = current_user();

$type = $_POST['type'] ?? '';
$name = trim($_POST['name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$message = trim($_POST['message'] ?? '');
$page = trim($_POST['page'] ?? '');
$rating = (int)($_POST['rating'] ?? 0);

if (!in_array($type, ['bug', 'suggestion', 'query'], true)) json_out(['ok' => false, 'error' => 'Choose a feedback type.'], 422);
if (mb_strlen($name) < 2) json_out(['ok' => false, 'error' => 'Please enter your name.'], 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['ok' => false, 'error' => 'Please enter a valid email.'], 422);
if (mb_strlen($message) < 5) json_out(['ok' => false, 'error' => 'Please write a short message (at least 5 characters).'], 422);
if ($type === 'bug' && $page === '') json_out(['ok' => false, 'error' => 'Tell us where the bug happened.'], 422);

q('INSERT INTO feedback (user_id, name, email, type, page, rating, message) VALUES (?,?,?,?,?,?,?)', [
    $user['user_id'] ?? null, mb_substr($name, 0, 100), mb_substr($email, 0, 150), $type,
    $type === 'bug' ? mb_substr($page, 0, 120) : null,
    $type === 'suggestion' && $rating >= 1 && $rating <= 5 ? $rating : null,
    mb_substr($message, 0, 2000),
]);
if ($user) log_activity((int)$user['user_id'], 'Feedback', ucfirst($type) . ' sent to the team');
json_out(['ok' => true, 'message' => 'Thank you! Your ' . $type . ' has been sent to the team.']);
