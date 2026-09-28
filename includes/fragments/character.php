<?php
/* Character profile modal. Expects $item, $isBookmarked, $prevId, $nextId, $position, $total, $related */
$timeline = parse_timeline($item['timeline']);
$relatedMedia = parse_lines($item['related']);
$accent = $item['category_color'] ?: '#8b5cf6';
?>
<div data-modal data-character="<?= (int)$item['character_id'] ?>" class="fixed inset-0 z-[80] flex items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="char-title-<?= (int)$item['character_id'] ?>">
  <div class="absolute inset-0 bg-black/85 backdrop-blur-sm animate-[fade-in_0.3s_ease]" data-close="modal"></div>
  <div class="relative max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-3xl glass shadow-2xl animate-[fade-in_0.45s_cubic-bezier(.22,1,.36,1)] no-scrollbar" data-lenis-prevent>

    <div class="absolute right-4 top-4 z-10 flex items-center gap-2">
      <button type="button" data-share="character" data-id="<?= (int)$item['character_id'] ?>" data-title="<?= e($item['name']) ?>" class="flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white transition hover:bg-black/70" data-cursor-hover aria-label="Share profile"><?= icon('share-2', 16) ?></button>
      <button type="button" data-close="modal" class="flex h-10 w-10 items-center justify-center rounded-full bg-black/50 text-white transition hover:bg-black/70" data-cursor-hover aria-label="Close"><?= icon('x', 18) ?></button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-[0.9fr_1.1fr]">
      <!-- Portrait -->
      <div class="relative h-80 md:sticky md:top-0 md:h-[92vh] md:max-h-[720px]">
        <img src="<?= e(media($item['image'])) ?>" alt="<?= e($item['name']) ?>" class="h-full w-full object-cover" />
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/10 to-transparent md:bg-gradient-to-r md:from-transparent md:via-transparent md:to-black/60"></div>
        <div class="absolute inset-0" style="background:radial-gradient(circle at 20% 90%, <?= e($accent) ?>40, transparent 55%)"></div>
        <?php if ($prevId || $nextId): ?>
          <div class="absolute bottom-4 left-4 flex items-center gap-2">
            <?php if ($prevId): ?><button type="button" data-swap="character" data-id="<?= (int)$prevId ?>" class="flex h-10 w-10 items-center justify-center rounded-full bg-black/55 text-white backdrop-blur transition hover:bg-black/75" data-cursor-hover aria-label="Previous character"><?= icon('chevron-left', 18) ?></button><?php endif; ?>
            <span class="rounded-full bg-black/55 px-3 py-2 text-[10px] font-semibold uppercase tracking-[0.25em] text-white/80 backdrop-blur"><?= pad2($position) ?> / <?= pad2($total) ?></span>
            <?php if ($nextId): ?><button type="button" data-swap="character" data-id="<?= (int)$nextId ?>" class="flex h-10 w-10 items-center justify-center rounded-full bg-black/55 text-white backdrop-blur transition hover:bg-black/75" data-cursor-hover aria-label="Next character"><?= icon('chevron-right', 18) ?></button><?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Details -->
      <div class="p-6 sm:p-9">
        <nav aria-label="Breadcrumb" class="mb-4 flex items-center gap-2 text-[10px] uppercase tracking-[0.2em] text-white/40">
          <span>Home</span><span>/</span><span>Characters</span><span>/</span><span class="text-white/70"><?= e($item['category']) ?></span>
        </nav>
        <span class="text-[11px] font-semibold uppercase tracking-[0.3em] text-cyan"><?= e($item['fandom_label']) ?></span>
        <h2 id="char-title-<?= (int)$item['character_id'] ?>" class="mt-2 font-display text-5xl leading-[0.9] text-white sm:text-6xl"><?= e($item['name']) ?></h2>

        <div class="mt-4 flex flex-wrap items-center gap-2">
          <?php if ($item['role'] !== ''): ?><span class="rounded-full border border-white/20 px-3 py-1 text-[10px] uppercase tracking-widest text-white/75"><?= e($item['role']) ?></span><?php endif; ?>
          <span class="flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[10px] uppercase tracking-widest text-white/60">
            <span class="h-1.5 w-1.5 rounded-full" style="background:<?= e($accent) ?>"></span><?= e($item['category']) ?>
          </span>
          <span class="flex items-center gap-1.5 px-1 text-[11px] text-white/45"><?= icon('eye', 13) ?> <?= number_format((int)$item['views']) ?></span>
          <button type="button" data-bookmark="character:<?= (int)$item['character_id'] ?>" class="ml-auto flex items-center gap-1.5 rounded-full border border-white/15 px-3.5 py-1.5 text-xs text-white/80 transition hover:border-white/40 hover:text-white" data-cursor-hover>
            <?= icon('bookmark', 14, $isBookmarked ? 'fill-amber text-amber' : '') ?> <span data-part="label"><?= $isBookmarked ? 'Saved' : 'Save' ?></span>
          </button>
        </div>

        <?php if ($item['short_desc'] !== ''): ?>
          <p class="mt-6 border-l-2 pl-4 text-base italic leading-relaxed text-white/85" style="border-color:<?= e($accent) ?>"><?= e($item['short_desc']) ?></p>
        <?php endif; ?>
        <h4 class="mt-7 mb-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/50">Biography</h4>
        <div class="space-y-3 text-sm leading-relaxed text-white/75">
          <?php foreach (paragraphs($item['bio']) as $p): ?><p><?= nl2br(e($p)) ?></p><?php endforeach; ?>
        </div>

        <?php if ($timeline): ?>
          <h4 class="mt-8 mb-4 text-xs font-semibold uppercase tracking-[0.25em] text-white/50">Timeline</h4>
          <ol class="relative space-y-4 border-l border-white/10 pl-5">
            <?php foreach ($timeline as $t): ?>
              <li class="relative">
                <span class="absolute -left-[25px] top-1 h-2.5 w-2.5 rounded-full ring-4 ring-violet/15" style="background:linear-gradient(135deg, var(--color-violet), var(--color-cyan))"></span>
                <p class="text-xs font-semibold uppercase tracking-wide text-violet"><?= e($t['year']) ?></p>
                <p class="text-sm text-white/75"><?= e($t['event']) ?></p>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>

        <?php if ($relatedMedia): ?>
          <h4 class="mt-8 mb-3 text-xs font-semibold uppercase tracking-[0.25em] text-white/50">Related media</h4>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($relatedMedia as $r): ?>
              <button type="button" data-search-query="<?= e($r) ?>" class="flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-white/80 transition hover:border-cyan/50 hover:text-white" data-cursor-hover><?= icon('search', 11, 'text-white/40') ?><?= e($r) ?></button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($related): ?>
          <h4 class="mt-9 mb-3 text-xs font-semibold uppercase tracking-[0.25em] text-white/50">You may also like</h4>
          <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <?php foreach ($related as $r): ?>
              <button type="button" data-swap="character" data-id="<?= (int)$r['character_id'] ?>" class="group relative aspect-[3/4] overflow-hidden rounded-xl text-left noise-border" data-cursor-hover>
                <img src="<?= e(media($r['image'])) ?>" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
                <span class="absolute inset-0 bg-gradient-to-t from-black via-black/10 to-transparent"></span>
                <span class="absolute inset-x-0 bottom-0 p-2.5">
                  <span class="block font-display text-lg leading-none text-white"><?= e($r['name']) ?></span>
                  <span class="mt-0.5 block truncate text-[9px] uppercase tracking-[0.15em] text-cyan"><?= e(character_fandom($r)) ?></span>
                </span>
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
