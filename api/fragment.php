<?php
/**
 * Returns server-rendered HTML for modals: article | character | merch | media | dashboard | events
 * Also records views (popularity analytics) and user activity.
 */
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: text/html; charset=utf-8');

$view = $_GET['view'] ?? '';
$id   = (int)($_GET['id'] ?? 0);
$user = current_user();
$uid  = $user ? (int)$user['user_id'] : null;
$bm   = user_bookmark_keys($uid);

function not_found(): void { http_response_code(404); echo '<div data-modal></div>'; exit; }

switch ($view) {
    case 'article':
        $item = q_one("SELECT c.*, cat.name AS category, u.name AS author FROM contents c JOIN categories cat USING(category_id)
                       LEFT JOIN users u ON u.user_id = c.submitted_by WHERE c.content_id=? AND c.status='published'", [$id]) ?? not_found();
        q('UPDATE contents SET views = views + 1 WHERE content_id=?', [$id]);
        if ($uid) log_activity($uid, 'Read', $item['title']);
        $isBookmarked = isset($bm['content:' . $id]);
        include __DIR__ . '/../includes/fragments/article.php';
        break;

    case 'media':
        $item = q_one("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE c.content_id=? AND c.status='published'", [$id]) ?? not_found();
        if ($item['type'] === 'article') { $isBookmarked = isset($bm['content:' . $id]); include __DIR__ . '/../includes/fragments/article.php'; break; }
        q('UPDATE contents SET views = views + 1 WHERE content_id=?', [$id]);
        if ($uid) log_activity($uid, $item['type'] === 'audio' ? 'Listened' : 'Watched', $item['title']);
        $isBookmarked = isset($bm['content:' . $id]);
        $rating = rating_summary($id);
        $myRating = $uid ? (int)q_val('SELECT stars FROM ratings WHERE user_id=? AND content_id=?', [$uid, $id]) : 0;
        include __DIR__ . '/../includes/fragments/media.php';
        break;

    case 'character':
        $item = q_one("SELECT ch.*, cat.name AS category, cat.color AS category_color FROM characters ch JOIN categories cat USING(category_id) WHERE ch.character_id=?", [$id]) ?? not_found();
        q('UPDATE characters SET views = views + 1 WHERE character_id=?', [$id]);
        $item['views'] = (int)$item['views'] + 1;
        if ($uid) log_activity($uid, 'Viewed', $item['name']);
        $isBookmarked = isset($bm['character:' . $id]);
        // neighbours (wrap around) for the prev / next buttons
        $ids = array_map('intval', array_column(q_all('SELECT character_id FROM characters ORDER BY character_id'), 'character_id'));
        $pos = array_search($id, $ids, true);
        $prevId = count($ids) > 1 ? $ids[($pos - 1 + count($ids)) % count($ids)] : null;
        $nextId = count($ids) > 1 ? $ids[($pos + 1) % count($ids)] : null;
        $position = $pos + 1; $total = count($ids);
        // same universe first, then the most viewed of the rest
        $related = q_all("SELECT ch.character_id, ch.name, ch.image, ch.fandom_label, ch.role FROM characters ch
                          WHERE ch.character_id <> ? ORDER BY (ch.category_id = ?) DESC, ch.views DESC LIMIT 4", [$id, $item['category_id']]);
        include __DIR__ . '/../includes/fragments/character.php';
        break;

    case 'merch':
        $item = q_one("SELECT m.*, cat.name AS category FROM merchandise m JOIN categories cat USING(category_id) WHERE m.item_id=?", [$id]) ?? not_found();
        q('UPDATE merchandise SET views = views + 1 WHERE item_id=?', [$id]);
        if ($uid) log_activity($uid, 'Viewed', $item['name']);
        $isBookmarked = isset($bm['merch:' . $id]);
        include __DIR__ . '/../includes/fragments/merch.php';
        break;

    case 'dashboard':
        if (!$user) { http_response_code(401); exit; }
        include __DIR__ . '/../includes/fragments/dashboard.php';
        break;

    case 'events':
        // city filter and/or distance sorting from the visitor's GPS position (Haversine formula)
        $city = trim($_GET['city'] ?? '');
        $lat  = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float)$_GET['lat'] : null;
        $lng  = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float)$_GET['lng'] : null;
        $params = [];
        $distSql = 'NULL';
        if ($lat !== null && $lng !== null) {
            $distSql = '(6371 * ACOS(LEAST(1, COS(RADIANS(:lat1)) * COS(RADIANS(e.latitude)) * COS(RADIANS(e.longitude) - RADIANS(:lng)) + SIN(RADIANS(:lat2)) * SIN(RADIANS(e.latitude)))))';
            $params += ['lat1' => $lat, 'lat2' => $lat, 'lng' => $lng];
        }
        $sql = "SELECT e.*, cat.name AS category, $distSql AS distance_km FROM events e JOIN categories cat USING(category_id) WHERE e.event_date >= CURDATE()";
        if ($city !== '') { $sql .= ' AND e.city = :city'; $params['city'] = $city; }
        $sql .= $lat !== null ? ' ORDER BY distance_km IS NULL, distance_km, e.event_date' : ' ORDER BY e.event_date';
        $eventsList = q_all($sql, $params);
        $savedEvents = $uid ? array_column(q_all('SELECT event_id FROM event_saves WHERE user_id=?', [$uid]), 'event_id') : [];
        include __DIR__ . '/../includes/partials/events_list.php';
        break;

    default:
        not_found();
}
