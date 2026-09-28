<?php
/**
 * Merchandise Page — Dedicated page for merchandise showcase and upcoming releases
 * Converted from the single-page index.php with ZIP design elements
 */
require_once __DIR__ . '/includes/functions.php';

$user = current_user();
$uid = $user ? (int)$user['user_id'] : null;
$bookmarks = user_bookmark_keys($uid);
$cats = categories();

// Merchandise data
$merch = q_all("SELECT m.*, cat.name AS category FROM merchandise m JOIN categories cat USING(category_id) ORDER BY m.item_id LIMIT 12");

// Upcoming releases
$releases = q_all("SELECT r.*, cat.name AS category FROM releases r JOIN categories cat USING(category_id)
                   ORDER BY r.release_date LIMIT 6");

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

  <?php
    $heroItems = array_map(function ($m) {
      return [
        'content_id'   => $m['item_id'],
        'title'        => $m['name'],
        'genre'        => $m['tag'] ?: $m['item_type'],
        'release_date' => null,
        'image'        => $m['image'],
        'type'         => 'merch',
        'category'     => $m['category'],
        'meta'         => trim(($m['price'] ?: '') . ($m['price'] && $m['item_type'] ? ' · ' : '') . $m['item_type']),
      ];
    }, array_slice($merch, 0, 6));
    $heroKicker = 'merchandise'; $heroId = 'merch-hero'; $heroOpenType = 'merch'; $heroBookmarkPrefix = 'merch';
    $heroPrimaryLabel = 'View item'; $heroPrimaryIcon = 'shopping-bag';
    $heroSecondaryLabel = 'Releases'; $heroSecondaryHref = '#releases';
    include __DIR__ . '/includes/partials/content_hero.php';
  ?>

  <main id="main-content" class="transition-opacity duration-700 opacity-0">
    <!-- Merchandise Section -->
    <section id="merch" class="relative bg-abyss py-24 sm:py-32">
      <div class="mx-auto mb-14 flex max-w-7xl flex-wrap items-end justify-between gap-4 px-5 sm:px-8">
        <div>
          <p class="mb-3 flex items-center gap-2 text-xs uppercase tracking-[0.35em] text-amber">
            <?= icon('shopping-bag', 14) ?> Merchandise Showcase
          </p>
          <h2 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">Collector-grade pieces</h2>
        </div>
        <p class="max-w-xs text-sm text-white/50">A curated showcase — view details on any piece. Purchasing is not enabled in this preview.</p>
      </div>

      <div class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-5 sm:grid-cols-2 sm:px-8 lg:grid-cols-4">
        <?php foreach ($merch as $m): ?>
          <button data-open="merch" data-id="<?= (int)$m['item_id'] ?>" data-cursor-hover class="card-glow group relative overflow-hidden rounded-2xl border border-white/10 bg-white/[0.02] text-left">
            <div class="relative aspect-square overflow-hidden">
              <img src="<?= e(media($m['image'])) ?>" alt="<?= e($m['name']) ?>" loading="lazy" class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" />
              <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
              <span class="absolute left-3 top-3 rounded-full bg-black/50 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-wide text-white backdrop-blur-sm"><?= e($m['item_type']) ?></span>
              <?php if ($m['tag'] !== ''): ?>
                <span class="absolute right-3 top-3 rounded-full bg-amber px-2.5 py-1 text-[9px] font-bold uppercase tracking-wide text-black"><?= e($m['tag']) ?></span>
              <?php endif; ?>
            </div>
            <div class="p-4">
              <span class="text-[10px] font-semibold uppercase tracking-wide text-cyan"><?= e($m['fandom_label']) ?></span>
              <h3 class="mt-1 font-display text-lg leading-tight text-white"><?= e($m['name']) ?></h3>
              <div class="mt-2 flex items-center justify-between">
                <span class="text-sm font-semibold text-white/80"><?= e($m['price']) ?></span>
                <span class="text-[11px] uppercase tracking-wide text-white/40 group-hover:text-white">View details</span>
              </div>
            </div>
          </button>
        <?php endforeach; ?>
      </div>

      <p class="mx-auto mt-14 max-w-7xl px-5 text-xs text-white/40 sm:px-8">Looking for release dates? See <button data-go="#releases" class="underline-anim text-cyan hover:text-white" data-cursor-hover>Upcoming Releases</button>.</p>
    </section>

    <!-- Upcoming Releases Section -->
    <section id="releases" class="relative bg-void py-24 sm:py-32">
      <div class="mx-auto mb-14 max-w-7xl px-5 sm:px-8">
        <p class="mb-3 text-xs uppercase tracking-[0.35em] text-magenta">Upcoming Releases</p>
        <h2 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">What's coming soon</h2>
      </div>

      <div class="mx-auto max-w-7xl px-5 sm:px-8">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <?php foreach ($releases as $r): ?>
            <div class="group relative rounded-2xl border border-white/10 bg-white/[0.03] p-6 hover:border-white/25 transition-all duration-300">
              <div class="mb-4 flex items-center gap-3">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan group-hover:text-white"><?= e($r['kind']) ?></span>
                <span class="text-[11px] uppercase tracking-wide text-white/40 group-hover:text-white"><?= e($r['tag'] ?? '') ?></span>
              </div>
              <h3 class="font-display text-xl leading-tight text-white group-hover:text-violet transition-colors"><?= e($r['title']) ?></h3>
              <p class="mt-2 text-white/60 line-clamp-2"><?= e($r['description']) ?></p>
              <div class="mt-4 flex items-center justify-between">
                <span class="text-sm text-white/50"><?= fmt_date($r['release_date']) ?></span>
                <?php if ($r['tag'] === 'Limited Edition' || $r['tag'] === 'Pre-Order'): ?>
                  <span class="text-xs px-2 py-1 rounded-full bg-amber/20 text-amber text-[8px] uppercase tracking-wider"><?= e($r['tag']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
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