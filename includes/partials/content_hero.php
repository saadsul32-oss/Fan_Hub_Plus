<?php
/**
 * Content Hero — cinematic featured banner for listing pages (Movies, TV Shows, Watch & Listen)
 * Expects (all optional except $heroItems):
 *   $heroItems   array  rows with: content_id, title, genre, release_date, image, type, category, meta
 *   $heroKicker  string small label above the title area (default: "Featured")
 *   $heroId      string section id (default: "hero-banner")
 * Reuses bm_icon_class() from articles.php if already defined, else defines it locally.
 */
if (!function_exists('bm_icon_class')) {
    function bm_icon_class(array $bookmarks, string $key): string { return isset($bookmarks[$key]) ? 'fill-amber text-amber' : ''; }
}
$heroItems  = array_values(array_filter($heroItems ?? [], fn($i) => !empty($i['image'])));
$heroKicker = $heroKicker ?? 'Featured';
$heroId     = $heroId ?? 'hero-banner';
$bookmarks  = $bookmarks ?? [];

// Optional overrides so this partial can serve non-video content (articles, merch) too.
$heroOpenType       = $heroOpenType ?? null;              // force data-open value for every item
$heroBookmarkPrefix = $heroBookmarkPrefix ?? 'content';   // e.g. "merch"
$heroPrimaryLabel   = $heroPrimaryLabel ?? 'Watch now';
$heroPrimaryIcon    = $heroPrimaryIcon ?? 'play';
$heroShowSecondary  = $heroShowSecondary ?? true;
$heroSecondaryLabel = $heroSecondaryLabel ?? 'Trailer';
$heroSecondaryHref  = $heroSecondaryHref ?? '#media';
?>
<?php if ($heroItems): ?>
<section id="<?= e($heroId) ?>" class="relative h-[92svh] max-h-[820px] min-h-[560px] w-full overflow-hidden bg-void" data-hero>
  <?php foreach ($heroItems as $i => $item): $open = $heroOpenType ?? ($item['type'] === 'article' ? 'article' : 'media'); ?>
    <div class="hero-slide absolute inset-0 transition-opacity duration-700 ease-out <?= $i === 0 ? 'z-10 opacity-100' : 'pointer-events-none z-0 opacity-0' ?>"
         data-hero-slide data-index="<?= $i ?>">
      <img src="<?= e(media($item['image'])) ?>" alt="<?= e($item['title']) ?>" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
        class="absolute inset-0 h-full w-full object-cover object-[70%_center]" />
      <div class="absolute inset-0 bg-gradient-to-r from-black via-black/70 to-black/10 sm:via-black/60 sm:to-transparent"></div>
      <div class="absolute inset-0 bg-gradient-to-t from-void via-black/10 to-transparent"></div>

      <div class="relative z-10 flex h-full max-w-2xl flex-col justify-center gap-4 px-5 pt-16 sm:px-10 lg:px-16">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-medium uppercase tracking-[0.2em] text-white/70">
          <?php if (!empty($item['release_date'])): ?><span><?= e(fmt_date($item['release_date'], 'M j')) ?></span><span class="h-1 w-1 rounded-full bg-white/40"></span><?php endif; ?>
          <span class="text-cyan"><?= e($item['genre'] ?: $item['category']) ?></span>
        </div>

        <h1 class="font-display text-[13vw] leading-[0.9] text-white text-glow sm:text-6xl lg:text-7xl"><?= e($item['title']) ?></h1>

        <?php if (!empty($item['meta']) || !empty($item['category'])): ?>
          <p class="text-sm text-white/60 sm:text-base"><?= e($item['meta'] ?: $item['category']) ?></p>
        <?php endif; ?>

        <div class="mt-2 flex flex-wrap items-center gap-3">
          <button type="button" data-open="<?= e($open) ?>" data-id="<?= (int)$item['content_id'] ?>" data-cursor-hover
            class="magnetic-btn flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-semibold text-black transition hover:bg-violet hover:text-white">
            <?= icon($heroPrimaryIcon, 15, '', ['fill' => 'currentColor']) ?> <?= e($heroPrimaryLabel) ?>
          </button>
          <?php if ($heroShowSecondary): ?>
            <a href="<?= e($heroSecondaryHref) ?>" data-cursor-hover
              class="flex items-center gap-2 rounded-full border border-white/25 bg-white/5 px-6 py-3 text-sm font-semibold text-white backdrop-blur-sm transition hover:border-white/60">
              <?= e($heroSecondaryLabel) ?>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Search / filter pill -->
  <div class="absolute right-5 top-24 z-20 hidden items-center gap-2 sm:flex lg:right-10">
    <button type="button" data-action="search" data-cursor-hover
      class="flex h-11 items-center gap-2 rounded-full border border-white/15 bg-black/30 px-4 text-xs text-white/60 backdrop-blur-md transition hover:border-white/35 hover:text-white">
      <?= icon('search', 14) ?> <span>Search <?= e($heroKicker) ?></span>
    </button>
    <button type="button" data-cursor-hover aria-label="Filter"
      class="flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-black/30 text-white/70 backdrop-blur-md transition hover:border-white/35 hover:text-white">
      <?= icon('sliders-horizontal', 15) ?>
    </button>
  </div>

  <!-- Like / bookmark / add -->
  <div class="absolute bottom-8 right-5 z-20 flex gap-2.5 sm:right-10 lg:bottom-10">
    <button type="button" data-hero-like data-cursor-hover aria-label="Like"
      class="flex h-11 w-11 items-center justify-center rounded-full bg-black/40 text-white/80 backdrop-blur-md transition hover:text-white">
      <?= icon('heart', 16) ?>
    </button>
    <button type="button" data-bookmark="<?= e($heroBookmarkPrefix) ?>:<?= (int)$heroItems[0]['content_id'] ?>" data-hero-bookmark data-cursor-hover aria-label="Bookmark"
      class="flex h-11 w-11 items-center justify-center rounded-full bg-black/40 text-white/80 backdrop-blur-md transition hover:text-white">
      <?= icon('bookmark', 16, bm_icon_class($bookmarks, $heroBookmarkPrefix . ':' . $heroItems[0]['content_id'])) ?>
    </button>
    <button type="button" data-cursor-hover aria-label="Add to list"
      class="flex h-11 w-11 items-center justify-center rounded-full bg-black/40 text-white/80 backdrop-blur-md transition hover:text-white">
      <?= icon('plus', 16) ?>
    </button>
  </div>

  <!-- Thumbnail strip -->
  <?php if (count($heroItems) > 1): ?>
    <div class="no-scrollbar absolute inset-x-5 bottom-8 z-20 flex gap-3 overflow-x-auto sm:inset-x-10 lg:bottom-10 lg:left-16" data-hero-thumbs>
      <?php foreach (array_slice($heroItems, 0, 6) as $i => $item): ?>
        <button type="button" data-hero-goto="<?= $i ?>" data-cursor-hover aria-label="<?= e($item['title']) ?>"
          class="hero-thumb relative h-[92px] w-[64px] flex-none overflow-hidden rounded-lg border-2 transition-all duration-300 sm:h-[104px] sm:w-[72px] <?= $i === 0 ? 'border-white opacity-100' : 'border-transparent opacity-50 hover:opacity-80' ?>">
          <img src="<?= e(media($item['image'])) ?>" alt="" class="h-full w-full object-cover" loading="lazy" />
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<script>
(function () {
  var root = document.getElementById(<?= json_encode($heroId) ?>);
  if (!root || root.dataset.heroBound) return;
  root.dataset.heroBound = '1';
  var slides = root.querySelectorAll('[data-hero-slide]');
  var thumbs = root.querySelectorAll('[data-hero-goto]');
  var likeBtn = root.querySelector('[data-hero-like]');

  function goTo(idx) {
    slides.forEach(function (s) {
      var on = +s.dataset.index === idx;
      s.classList.toggle('opacity-100', on);
      s.classList.toggle('z-10', on);
      s.classList.toggle('pointer-events-none', !on);
      s.classList.toggle('opacity-0', !on);
      s.classList.toggle('z-0', !on);
    });
    thumbs.forEach(function (t, i) {
      t.classList.toggle('border-white', i === idx);
      t.classList.toggle('opacity-100', i === idx);
      t.classList.toggle('border-transparent', i !== idx);
      t.classList.toggle('opacity-50', i !== idx);
    });
  }
  thumbs.forEach(function (t) {
    t.addEventListener('click', function () { goTo(+t.dataset.heroGoto); });
  });
  if (likeBtn) {
    likeBtn.addEventListener('click', function () {
      likeBtn.classList.toggle('text-magenta');
      likeBtn.classList.toggle('text-white/80');
    });
  }
})();
</script>
<?php endif; ?>
