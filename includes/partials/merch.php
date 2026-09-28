<?php /* Merchandise Showcase (converted from Merch.tsx) + Upcoming Releases (SRS resource library) */ ?>
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

  <?php if ($releases): ?>
  <div class="mx-auto mt-20 max-w-7xl px-5 sm:px-8">
    <p class="mb-6 text-xs uppercase tracking-[0.3em] text-white/40">Upcoming releases</p>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($releases as $r): ?>
        <div class="group flex items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.02] p-3 text-left transition hover:border-white/25 hover:bg-white/[0.05]">
          <div class="relative h-20 w-24 flex-none overflow-hidden rounded-xl">
            <img src="<?= e(media($r['image'])) ?>" alt="<?= e($r['title']) ?>" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
          </div>
          <div class="min-w-0 flex-1">
            <span class="text-[10px] font-semibold uppercase tracking-wide text-cyan"><?= e($r['kind']) ?> · <?= e($r['category']) ?></span>
            <h4 class="mt-1 truncate font-display text-lg leading-tight text-white"><?= e($r['title']) ?></h4>
            <div class="mt-1 flex items-center gap-3 text-[11px] text-white/45">
              <span><?= e(fmt_date($r['release_date'])) ?></span>
              <?php if ($r['tag'] !== ''): ?><span class="text-amber"><?= e($r['tag']) ?></span><?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>
