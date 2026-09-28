<?php /* Media player modal (video / trailer / audio) with 5-star rating. Expects $item, $isBookmarked, $rating, $myRating */
$embed = embed_url($item['media_url']); ?>
<div data-modal class="fixed inset-0 z-[80] flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/85 backdrop-blur-sm animate-[fade-in_0.3s_ease]" data-close="modal"></div>
  <div class="relative w-full max-w-4xl overflow-hidden rounded-3xl glass animate-[fade-in_0.4s_cubic-bezier(.22,1,.36,1)]">
    <button data-close="modal" class="absolute right-4 top-4 z-10 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70" data-cursor-hover aria-label="Close"><?= icon('x', 16) ?></button>
    <div class="relative aspect-video w-full bg-black">
      <?php if ($item['type'] === 'audio'): ?>
        <img src="<?= e(media($item['image'])) ?>" alt="" class="absolute inset-0 h-full w-full object-cover opacity-60" />
        <audio src="<?= e(media_src($item['media_url'])) ?>" controls autoplay class="absolute inset-x-6 bottom-6 w-[calc(100%-3rem)]"></audio>
      <?php elseif ($embed): ?>
        <iframe src="<?= e($embed) ?>" class="absolute inset-0 h-full w-full" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen title="<?= e($item['title']) ?>"></iframe>
      <?php elseif ($item['media_url']): ?>
        <video src="<?= e(media_src($item['media_url'])) ?>" poster="<?= e(media($item['image'])) ?>" controls autoplay playsinline class="absolute inset-0 h-full w-full bg-black object-contain"></video>
      <?php else: ?>
        <img src="<?= e(media($item['image'])) ?>" alt="" class="absolute inset-0 h-full w-full object-cover" />
      <?php endif; ?>
    </div>
    <div class="flex flex-wrap items-end justify-between gap-5 p-6 sm:p-8">
      <div class="min-w-0">
        <span class="text-[11px] font-semibold uppercase tracking-[0.3em] text-cyan"><?= e(content_type_label($item['type'])) ?> · <?= e($item['category']) ?></span>
        <h3 class="mt-2 font-display text-3xl text-white"><?= e($item['title']) ?></h3>
        <p class="mt-1 text-sm text-white/50"><?= e($item['meta'] ?: $item['excerpt']) ?> · <?= number_format((int)$item['views']) ?> views</p>
      </div>
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-1" data-rating-box="<?= (int)$item['content_id'] ?>">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <button data-rate="<?= $s ?>" data-content="<?= (int)$item['content_id'] ?>" class="rate-star <?= $s <= ($myRating ?: round($rating['avg'])) ? 'text-amber' : 'text-white/30' ?> hover:text-amber" aria-label="Rate <?= $s ?> stars" data-cursor-hover><?= icon('star', 18, '', ['fill' => 'currentColor']) ?></button>
          <?php endfor; ?>
          <span data-part="rating-text" class="ml-2 text-xs text-white/50"><?= $rating['count'] ? e($rating['avg'] . ' · ' . $rating['count'] . ' ratings') : 'Be the first to rate' ?></span>
        </div>
        <button data-bookmark="content:<?= (int)$item['content_id'] ?>" class="text-white/60 hover:text-white" data-cursor-hover aria-label="Bookmark">
          <?= icon('bookmark', 18, $isBookmarked ? 'fill-amber text-amber' : '') ?>
        </button>
      </div>
    </div>
  </div>
</div>
