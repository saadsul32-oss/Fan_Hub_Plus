<?php
/**
 * Movies Page — Separate page for movies/franchise content
 * Converted from the single-page index.php with ZIP design elements
 */
require_once __DIR__ . '/includes/functions.php';

$user = current_user();
$uid = $user ? (int)$user['user_id'] : null;
$bookmarks = user_bookmark_keys($uid);
$cats = categories();

// Movies & Media content
$movies = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id)
                 WHERE c.status='published' AND cat.slug='movies' ORDER BY c.popularity DESC LIMIT 12");

$trending = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id)
                   WHERE c.status='published' AND c.is_trending=1 ORDER BY c.popularity DESC LIMIT 12");
if (!$movies) $movies = $trending; // fall back to general trending if nothing is tagged 'movies' yet

$featuredMedia = q_one("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id)
                        WHERE c.status='published' AND c.type IN('video','trailer') AND c.is_featured=1 ORDER BY c.content_id DESC LIMIT 1");
$gallery = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id)
                  WHERE c.status='published' AND c.type IN('video','trailer') AND c.in_gallery=1 ORDER BY c.release_date DESC LIMIT 4");
$audio = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id)
                WHERE c.status='published' AND c.type='audio' AND c.in_gallery=1 ORDER BY c.release_date DESC LIMIT 3");

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

  <?php $heroItems = $movies; $heroKicker = 'movies'; $heroId = 'movies-hero'; include __DIR__ . '/includes/partials/content_hero.php'; ?>

  <main id="main-content" class="transition-opacity duration-700 opacity-0">
    <!-- Movies Section -->
    <section id="explore" class="relative overflow-hidden bg-void py-24 sm:py-32">
      <div class="mx-auto mb-10 flex max-w-7xl items-end justify-between px-5 sm:px-8">
        <div>
          <p class="mb-3 flex items-center gap-2 text-xs uppercase tracking-[0.35em] text-magenta">
            <?= icon('flame', 14) ?> Trending Now
          </p>
          <h2 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">Box Office & New Releases</h2>
        </div>
        <div class="hidden gap-2 sm:flex">
          <button data-scroll-trending="-1" class="flex h-11 w-11 items-center justify-center rounded-full border border-white/15 text-white transition hover:border-white/40" data-cursor-hover aria-label="Scroll left">
            <?= icon('chevron-left', 18) ?>
          </button>
          <button data-scroll-trending="1" class="flex h-11 w-11 items-center justify-center rounded-full border border-white/15 text-white transition hover:border-white/40" data-cursor-hover aria-label="Scroll right">
            <?= icon('chevron-right', 18) ?>
          </button>
        </div>
      </div>

      <div id="trending-scroller" class="no-scrollbar flex gap-5 overflow-x-auto scroll-smooth px-5 pb-6 sm:px-8" style="scroll-snap-type:x mandatory">
        <?php foreach ($movies as $item): ?>
          <article data-cursor-hover data-open="<?= $item['type'] === 'article' ? 'article' : 'media' ?>" data-id="<?= (int)$item['content_id'] ?>"
            class="trending-card group relative h-[420px] flex-none cursor-pointer overflow-hidden rounded-2xl transition-all duration-500 ease-out"
            style="width:300px;scroll-snap-align:start;opacity:1;transform:scale(1)">
            <img src="<?= e(media($item['image'])) ?>" alt="<?= e($item['title']) ?>" loading="lazy" data-part="img"
              class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out" style="transform:scale(1)" />
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-black/10"></div>
            <div class="absolute inset-0 opacity-0 transition-opacity duration-500 group-hover:opacity-100" style="box-shadow:inset 0 0 100px rgba(139,92,246,0.35)"></div>

            <div class="absolute left-4 right-4 top-4 flex items-center justify-between">
              <span class="flex items-center gap-1.5 rounded-full bg-black/50 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-white backdrop-blur-sm">
                <?= icon(content_type_icon($item['type']), 11) ?> <?= e(content_type_label($item['type'])) ?>
              </span>
              <span class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur-sm">🔥 <?= (int)$item['popularity'] ?></span>
            </div>

            <div class="absolute inset-x-0 bottom-0 p-5">
              <span class="mb-2 inline-block text-[10px] font-semibold uppercase tracking-[0.25em] text-cyan"><?= e($item['category']) ?></span>
              <h3 class="font-display text-2xl leading-tight text-white"><?= e($item['title']) ?></h3>
              <div data-part="preview" class="mt-3 flex items-center gap-2 overflow-hidden text-xs text-white/70 transition-all duration-500" style="max-height:0;opacity:0">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white text-black"><?= icon('play', 11, '', ['fill' => 'black']) ?></span>
                <span>Tap to preview</span>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Movies Media Section -->
    <section id="media" class="relative bg-void py-24 sm:py-32">
      <div class="mx-auto mb-14 max-w-7xl px-5 sm:px-8">
        <p class="mb-3 flex items-center gap-2 text-xs uppercase tracking-[0.35em] text-cyan">
          <?= icon('film', 14) ?> Watch & Listen
        </p>
        <h2 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">A streaming stage for the fandom</h2>
      </div>

      <div class="mx-auto max-w-7xl px-5 sm:px-8">
        <?php if ($featuredMedia): ?>
          <!-- Featured player -->
          <div id="featured-player" data-id="<?= (int)$featuredMedia['content_id'] ?>" data-src="<?= e(media_src($featuredMedia['media_url'])) ?>" data-embed="<?= e(embed_url($featuredMedia['media_url']) ?? '') ?>"
            class="group relative mb-8 aspect-video w-full overflow-hidden rounded-3xl noise-border">
            <img src="<?= e(media($featuredMedia['image'])) ?>" alt="Featured trailer" class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1200ms] group-hover:scale-105" />
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-black/10"></div>
            <div data-part="stage" class="absolute inset-0 hidden bg-black"></div>
            <div data-part="overlay" class="absolute inset-0 flex flex-col items-center justify-center gap-5">
              <button data-action="play-featured" data-cursor-hover class="magnetic-btn flex h-20 w-20 items-center justify-center rounded-full bg-white/15 backdrop-blur-md transition hover:scale-110 hover:bg-white/25" aria-label="Play featured trailer">
                <span data-part="play"><?= icon('play', 28, 'ml-1 text-white', ['fill' => 'white']) ?></span>
                <span data-part="pause" class="hidden"><?= icon('pause', 28, 'text-white') ?></span>
              </button>
              <span data-part="now" class="hidden rounded-full bg-black/50 px-4 py-1.5 text-xs text-white backdrop-blur-sm animate-[fade-in_0.4s_ease]">Now playing</span>
            </div>
            <div data-part="caption" class="absolute left-6 bottom-6 sm:left-8 sm:bottom-8">
              <span class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-white backdrop-blur-sm">
                <?= icon('sparkles', 11) ?> Featured Trailer
              </span>
              <h3 class="font-display text-3xl text-white sm:text-4xl"><?= e($featuredMedia['title']) ?></h3>
            </div>
            <div data-part="rating" class="absolute right-6 bottom-6 hidden items-center gap-1 rounded-full bg-black/50 px-3 py-1.5 backdrop-blur-sm sm:right-8 sm:bottom-8 sm:flex">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <button data-rate="<?= $s ?>" data-content="<?= (int)$featuredMedia['content_id'] ?>" class="rate-star <?= $s <= round($fr['avg']) ? 'text-amber' : 'text-white/40' ?> hover:text-amber" aria-label="Rate <?= $s ?> stars" data-cursor-hover><?= icon('star', 13, '', ['fill' => 'currentColor']) ?></button>
              <?php endfor; ?>
              <span data-part="rating-text" class="ml-1 text-[11px] text-white/70"><?= $fr['count'] ? e($fr['avg'] . ' · ' . $fr['count']) : 'Rate' ?></span>
            </div>
          </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
          <!-- Video gallery -->
          <div class="lg:col-span-2">
            <h4 class="mb-4 text-xs font-semibold uppercase tracking-[0.25em] text-white/50">Video Gallery</h4>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <?php foreach ($gallery as $g): ?>
                <button data-open="media" data-id="<?= (int)$g['content_id'] ?>" data-cursor-hover class="group relative aspect-video overflow-hidden rounded-xl text-left">
                  <img src="<?= e(media($g['image'])) ?>" alt="<?= e($g['title']) ?>" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-110" />
                  <div class="absolute inset-0 bg-black/40 transition group-hover:bg-black/20"></div>
                  <span class="absolute left-3 top-3 rounded-full bg-black/50 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-wide text-white"><?= e(content_type_label($g['type'])) ?></span>
                  <span class="absolute inset-0 flex items-center justify-center">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-white/20 backdrop-blur-md transition group-hover:scale-110 group-hover:bg-white/30">
                      <?= icon('play', 16, 'ml-0.5 text-white', ['fill' => 'white']) ?>
                    </span>
                  </span>
                  <p class="absolute inset-x-0 bottom-0 p-3 text-xs font-medium text-white"><?= e($g['title']) ?></p>
                </button>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Audio -->
          <div>
            <h4 class="mb-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/50">
              <?= icon('music-2', 13) ?> Audio
            </h4>
            <div class="space-y-3">
              <?php foreach ($audio as $a): ?>
                <button data-audio="<?= (int)$a['content_id'] ?>" data-src="<?= e(media_src($a['media_url'])) ?>" data-title="<?= e($a['title']) ?>" data-cursor-hover
                  class="audio-row flex w-full items-center gap-3 rounded-xl border px-4 py-3 text-left transition-all border-white/10 bg-white/[0.03] hover:border-white/25">
                  <span data-part="btn" class="flex h-9 w-9 flex-none items-center justify-center rounded-full transition bg-white/10 text-white/80">
                    <span data-part="play"><?= icon('play', 13, 'ml-0.5', ['fill' => 'currentColor']) ?></span>
                    <span data-part="pause" class="hidden"><?= icon('pause', 13) ?></span>
                  </span>
                  <span class="min-w-0">
                    <span class="block truncate text-sm font-medium text-white"><?= e($a['title']) ?></span>
                    <span class="block text-xs text-white/50"><?= e($a['meta']) ?></span>
                  </span>
                  <span data-part="bars" class="ml-auto hidden items-end gap-0.5">
                    <?php for ($i = 0; $i < 3; $i++): ?>
                      <span class="w-0.5 animate-pulse rounded-full bg-cyan" style="height:<?= 6 + $i * 4 ?>px;animation-delay:<?= $i * 150 ?>ms"></span>
                    <?php endfor; ?>
                  </span>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Trending Section (below media) -->
    <?php include __DIR__ . '/includes/partials/trending.php'; ?>

    <!-- Articles Section -->
    <?php include __DIR__ . '/includes/partials/articles.php'; ?>
  </main>

  <?php include __DIR__ . '/includes/partials/chatbot.php'; ?>
  <?php include __DIR__ . '/includes/partials/search_overlay.php'; ?>
  <?php include __DIR__ . '/includes/partials/auth_modal.php'; ?>
  <?php include __DIR__ . '/includes/partials/feedback_modal.php'; ?>
  <!-- Server-rendered modals (article reader, character profile, merch, media player, dashboard) are injected here -->
  <div id="modal-root"></div>
  <template id="tpl-icon-circle-check"><?= icon('circle-check', 14) ?></template>
  <template id="tpl-icon-calendar-plus"><?= icon('calendar-plus', 14) ?></template>
  <div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-[120] -translate-x-1/2 rounded-full bg-white px-5 py-2.5 text-xs font-semibold text-black opacity-0 shadow-2xl transition-all duration-300 translate-y-4"></div>
</div>
<?php include __DIR__ . '/includes/partials/foot.php'; ?>
</body></html>