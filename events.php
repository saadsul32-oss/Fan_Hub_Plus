<?php
/**
 * Events Page — Dedicated page for upcoming events with city filter and map
 * Converted from the single-page index.php with ZIP design elements
 */
require_once __DIR__ . '/includes/functions.php';

$user = current_user();
$uid = $user ? (int)$user['user_id'] : null;
$bookmarks = user_bookmark_keys($uid);
$cats = categories();
$savedEvents = $uid ? array_column(q_all('SELECT event_id FROM event_saves WHERE user_id=?', [$uid]), 'event_id') : [];

// Events data
$eventsList = q_all("SELECT e.*, cat.name AS category FROM events e JOIN categories cat USING(category_id)
                     WHERE e.event_date >= CURDATE() ORDER BY e.event_date");
$mapPoints = array_values(array_filter(array_map(fn($e) => $e['latitude'] !== null ? [
  'id' => (int)$e['event_id'], 'title' => $e['title'], 'lat' => (float)$e['latitude'], 'lng' => (float)$e['longitude'],
  'venue' => $e['venue'], 'city' => $e['city'], 'date' => fmt_date($e['event_date'])] : null, $eventsList)));

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
    <!-- Events Section -->
    <section id="events" class="relative bg-void py-24 sm:py-32">
      <div class="mx-auto mb-16 max-w-7xl px-5 sm:px-8">
        <p class="mb-3 text-xs uppercase tracking-[0.35em] text-magenta">Upcoming Events</p>
        <h2 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">Mark your universe calendar</h2>
      </div>

      <div class="mx-auto max-w-7xl px-5 sm:px-8">
        <div class="mb-6 flex flex-wrap items-center gap-2">
          <?= icon('map-pin', 14, 'mr-1 text-white/40') ?>
          <button data-event-city="" class="city-chip rounded-full border px-3.5 py-1.5 text-xs font-medium transition border-white bg-white text-black" data-cursor-hover>All cities</button>
          <?php
          $eventCities = q_all("SELECT DISTINCT city FROM events WHERE event_date >= CURDATE() ORDER BY city");
          foreach ($eventCities as $c): ?>
            <button data-event-city="<?= e($c['city']) ?>" class="city-chip rounded-full border px-3.5 py-1.5 text-xs font-medium transition border-white/15 text-white/60 hover:border-white/40" data-cursor-hover><?= e($c['city']) ?></button>
          <?php endforeach; ?>
          <span class="mx-2 hidden h-4 w-px bg-white/15 sm:block"></span>
          <button data-action="near-me" class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-medium text-cyan transition hover:text-white" data-cursor-hover>
            <?= icon('navigation', 12) ?> <span data-part="label">Near me</span>
          </button>
        </div>

        <div id="events-map" class="relative mb-12 h-72 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]" data-points='<?= e(json_encode($mapPoints)) ?>'>
          <div class="absolute inset-0 flex items-center justify-center text-xs uppercase tracking-[0.3em] text-white/30">Loading map…</div>
        </div>

        <div id="events-list">
          <?php include __DIR__ . '/includes/partials/events_list.php'; ?>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/partials/chatbot.php'; ?>
  <?php include __DIR__ . '/includes/partials/search_overlay.php'; ?>
  <?php include __DIR__ . '/includes/partials/auth_modal.php'; ?>
  <?php include __DIR__ . '/includes/partials/feedback_modal.php'; ?>
  <div id="modal-root"></div>
  <template id="tpl-icon-circle-check"><?= icon('circle-check', 14) ?></template>
  <template id="tpl-icon-calendar-plus"><?= icon('calendar-plus', 14) ?></template>
  <div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-[120] -translate-x-1/2 rounded-full bg-white px-5 py-2.5 text-xs font-semibold text-black opacity-0 shadow-2xl transition-all duration-300 translate-y-4"></div>
</div>
<?php include __DIR__ . '/includes/partials/foot.php'; ?>
</body></html>