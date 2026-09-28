<?php
/* Watch & Listen (converted from WatchListen.tsx) — real streaming from contents.media_url */
$fr = $featuredMedia ? rating_summary((int)$featuredMedia['content_id']) : ['avg' => 0, 'count' => 0];
?>
<section id="media" class="relative bg-void py-24 sm:py-32">
  <div class="mx-auto mb-14 max-w-7xl px-5 sm:px-8">
    <p class="mb-3 flex items-center gap-2 text-xs uppercase tracking-[0.35em] text-cyan">
      <?= icon('film', 14) ?> Watch &amp; Listen
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
