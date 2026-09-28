<?php
/**
 * Fan Hub Plus — Home (server-rendered from MySQL)
 * Every section below was converted from the original React component
 * of the same name and keeps the exact same markup / Tailwind classes.
 */
require_once __DIR__ . '/includes/functions.php';

ensure_schema();
$user      = current_user();
$uid       = $user ? (int)$user['user_id'] : null;
$bookmarks = user_bookmark_keys($uid);
$cats      = categories();

// --- Section data -------------------------------------------------------
$trending = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id)
                   WHERE c.status='published' AND c.is_trending=1 ORDER BY c.popularity DESC LIMIT 12");

$characters = q_all("SELECT ch.*, cat.name AS category, cat.color AS category_color FROM characters ch JOIN categories cat USING(category_id) ORDER BY ch.character_id");
$carousel = array_values(array_filter($characters, fn($c) => (int)$c['in_carousel'] === 1));
if (!$carousel) $carousel = $characters;              // nothing flagged -> feature everyone
$carousel = array_slice($carousel, 0, 8);

$featuredMedia = q_one("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id)
                        WHERE c.status='published' AND c.type IN('video','trailer') AND c.is_featured=1 ORDER BY c.content_id DESC LIMIT 1");
$gallery = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id)
                  WHERE c.status='published' AND c.type IN('video','trailer') AND c.in_gallery=1 ORDER BY c.release_date DESC LIMIT 4");
$audio = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id)
                WHERE c.status='published' AND c.type='audio' AND c.in_gallery=1 ORDER BY c.release_date DESC LIMIT 3");

$articles = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id)
                   WHERE c.status='published' AND c.type='article' AND c.is_trending=0 ORDER BY c.is_featured DESC, c.release_date DESC LIMIT 5");
$featuredArticle = $articles[0] ?? null;
$restArticles = array_slice($articles, 1);

$merch    = q_all("SELECT m.*, cat.name AS category FROM merchandise m JOIN categories cat USING(category_id) ORDER BY m.item_id LIMIT 8");
$releases = q_all("SELECT r.*, cat.name AS category FROM releases r JOIN categories cat USING(category_id)
                   WHERE r.release_date >= CURDATE() ORDER BY r.release_date LIMIT 6");

$eventCities = q_all("SELECT DISTINCT city FROM events WHERE event_date >= CURDATE() ORDER BY city");
$savedEvents = $uid ? array_column(q_all('SELECT event_id FROM event_saves WHERE user_id=?', [$uid]), 'event_id') : [];

$chatSuggestions = q_all("SELECT question FROM chatbot_faq WHERE is_suggestion=1 AND question<>'' ORDER BY sort_order LIMIT 4");

$theme = $user ? $user['pref_theme'] : null;
$font  = $user ? (float)$user['pref_font'] : null;

include __DIR__ . '/includes/partials/head.php';
?>
<div class="relative min-h-screen bg-void">
  <?php include __DIR__ . '/includes/partials/loader.php'; ?>

  <div class="grain"></div>
  <?php include __DIR__ . '/includes/partials/cursor.php'; ?>
  <div class="fixed top-0 left-0 right-0 z-[70] h-[2px] bg-transparent">
    <div id="scroll-progress" class="h-full bg-gradient-to-r from-violet via-magenta to-cyan transition-[width] duration-100 ease-linear" style="width:0%;box-shadow:0 0 12px rgba(139,92,246,0.8)"></div>
  </div>
  <a href="#top" class="fixed left-2 top-2 z-[100] -translate-y-20 rounded-full bg-white px-4 py-2 text-xs font-semibold text-black transition-transform focus:translate-y-0">Skip to content</a>

  <?php include __DIR__ . '/includes/partials/navbar.php'; ?>

  <main id="main-content" class="transition-opacity duration-700 opacity-0">
    <?php
    include __DIR__ . '/includes/partials/hero.php';
    include __DIR__ . '/includes/partials/fandom_explorer.php';
    include __DIR__ . '/includes/partials/trending.php';
    include __DIR__ . '/includes/partials/characters.php';
    include __DIR__ . '/includes/partials/watch_listen.php';
    include __DIR__ . '/includes/partials/articles.php';
    include __DIR__ . '/includes/partials/events.php';
    include __DIR__ . '/includes/partials/merch.php';
    include __DIR__ . '/includes/partials/footer.php';
    ?>
  </main>

  <?php
  include __DIR__ . '/includes/partials/chatbot.php';
  include __DIR__ . '/includes/partials/search_overlay.php';
  include __DIR__ . '/includes/partials/auth_modal.php';
  include __DIR__ . '/includes/partials/feedback_modal.php';
  ?>
  <!-- Server-rendered modals (article reader, character profile, merch, media player, dashboard) are injected here -->
  <div id="modal-root"></div>
  <template id="tpl-icon-circle-check"><?= icon('circle-check', 14) ?></template>
  <template id="tpl-icon-calendar-plus"><?= icon('calendar-plus', 14) ?></template>
  <div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-[120] -translate-x-1/2 rounded-full bg-white px-5 py-2.5 text-xs font-semibold text-black opacity-0 shadow-2xl transition-all duration-300 translate-y-4"></div>
</div>
<?php include __DIR__ . '/includes/partials/foot.php'; ?>
