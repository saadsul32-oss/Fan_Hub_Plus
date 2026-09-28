<?php
/** Profile: favourite fandoms, display preferences, name + avatar */
require_once __DIR__ . '/../includes/functions.php';
require_post_csrf_json();
$user = require_login_json();
$uid = (int)$user['user_id'];

switch ($_POST['action'] ?? '') {
    case 'favorites':
        $ids = array_filter(array_map('intval', (array)($_POST['cats'] ?? [])));
        db()->beginTransaction();
        q('DELETE FROM user_categories WHERE user_id=?', [$uid]);
        foreach ($ids as $cid) q('INSERT IGNORE INTO user_categories (user_id, category_id) SELECT ?, category_id FROM categories WHERE category_id=?', [$uid, $cid]);
        db()->commit();
        json_out(['ok' => true, 'message' => 'Favourite fandoms updated']);

    case 'prefs':
        $digest = in_array($_POST['digest'] ?? '', ['Off', 'Daily', 'Weekly', 'Monthly'], true) ? $_POST['digest'] : 'Weekly';
        $notif = ($_POST['notifications'] ?? '1') === '1' ? 1 : 0;
        $theme = ($_POST['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
        $font = max(0.875, min(1.25, (float)($_POST['font'] ?? 1)));
        q('UPDATE users SET pref_digest=?, pref_notifications=?, pref_theme=?, pref_font=? WHERE user_id=?', [$digest, $notif, $theme, $font, $uid]);
        json_out(['ok' => true, 'message' => 'Preferences saved', 'theme' => $theme, 'font' => $font]);

    case 'theme':   // quick save from the navbar toggles
        $theme = ($_POST['theme'] ?? 'dark') === 'light' ? 'light' : 'dark';
        $font = max(0.875, min(1.25, (float)($_POST['font'] ?? 1)));
        q('UPDATE users SET pref_theme=?, pref_font=? WHERE user_id=?', [$theme, $font, $uid]);
        json_out(['ok' => true]);

    case 'profile':
        $name = trim($_POST['name'] ?? '');
        if (mb_strlen($name) < 2) json_out(['ok' => false, 'error' => 'Name is too short.'], 422);
        try {
            $avatar = !empty($_FILES['avatar']) ? save_upload($_FILES['avatar'], 'avatars') : null;
        } catch (RuntimeException $ex) {
            json_out(['ok' => false, 'error' => $ex->getMessage()], 422);
        }
        if ($avatar) {
            if ($user['avatar'] && strpos($user['avatar'], 'uploads/avatars/') === 0) @unlink(APP_ROOT . '/' . $user['avatar']);
            q('UPDATE users SET name=?, avatar=? WHERE user_id=?', [mb_substr($name, 0, 100), $avatar, $uid]);
        } else {
            q('UPDATE users SET name=? WHERE user_id=?', [mb_substr($name, 0, 100), $uid]);
        }
        json_out(['ok' => true, 'message' => 'Profile updated']);

    default:
        json_out(['ok' => false, 'error' => 'Unknown action'], 400);
}
