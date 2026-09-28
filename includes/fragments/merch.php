<?php /* Merch detail modal (converted from Merch.tsx modal). Expects $item, $isBookmarked */ ?>
<div data-modal class="fixed inset-0 z-[80] flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/85 backdrop-blur-sm animate-[fade-in_0.3s_ease]" data-close="modal"></div>
  <div class="relative grid w-full max-w-3xl grid-cols-1 overflow-hidden rounded-3xl glass sm:grid-cols-2 animate-[fade-in_0.4s_cubic-bezier(.22,1,.36,1)]">
    <button data-close="modal" class="absolute right-4 top-4 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70" data-cursor-hover aria-label="Close"><?= icon('x', 16) ?></button>
    <div class="aspect-square sm:aspect-auto">
      <img src="<?= e(media($item['image'])) ?>" alt="<?= e($item['name']) ?>" class="h-full w-full object-cover" />
    </div>
    <div class="p-7 sm:p-8">
      <span class="text-[11px] font-semibold uppercase tracking-[0.3em] text-cyan"><?= e($item['fandom_label']) ?></span>
      <h3 class="mt-2 font-display text-3xl text-white"><?= e($item['name']) ?></h3>
      <?php if ($item['tag'] !== ''): ?>
        <span class="mt-3 inline-block rounded-full bg-amber px-2.5 py-1 text-[9px] font-bold uppercase tracking-wide text-black"><?= e($item['tag']) ?></span>
      <?php endif; ?>
      <p class="mt-4 text-sm leading-relaxed text-white/70"><?= e($item['description']) ?></p>
      <div class="mt-6 flex items-center justify-between">
        <span class="font-display text-2xl text-white"><?= e($item['price']) ?></span>
        <span class="flex items-center gap-1.5 text-xs uppercase tracking-wide text-white/40"><?= icon('sparkle', 12) ?> Showcase only</span>
      </div>
      <button data-bookmark="merch:<?= (int)$item['item_id'] ?>" data-bookmark-label class="mt-6 flex w-full items-center justify-center gap-2 rounded-full border border-white/20 py-3 text-xs font-semibold uppercase tracking-widest text-white transition hover:border-white/50" data-cursor-hover>
        <?= icon('bookmark', 14, $isBookmarked ? 'fill-amber text-amber' : '') ?> <span data-part="label"><?= $isBookmarked ? 'Saved' : 'Save to wishlist' ?></span>
      </button>
      <button disabled class="mt-3 w-full cursor-not-allowed rounded-full border border-white/15 bg-white/5 py-3 text-xs font-semibold uppercase tracking-widest text-white/40">Purchasing disabled in preview</button>
    </div>
  </div>
</div>
