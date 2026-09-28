<?php /* Trending (converted from Trending.tsx). Items = contents.is_trending = 1 */ ?>
<section id="explore" class="relative overflow-hidden bg-void py-24 sm:py-32">
  <div class="mx-auto mb-10 flex max-w-7xl items-end justify-between px-5 sm:px-8">
    <div>
      <p class="mb-3 flex items-center gap-2 text-xs uppercase tracking-[0.35em] text-magenta">
        <?= icon('flame', 14) ?> Trending Now
      </p>
      <h2 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">What the universe is watching</h2>
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
    <?php foreach ($trending as $item): ?>
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
