<?php
/* Article reader (converted from ArticleReader.tsx). Expects $item, $isBookmarked, $user */
$paras = paragraphs($item['body']);
$limited = !$user && count($paras) > 1;   // Visitors get a preview only (SRS: limited access)
?>
<div data-modal class="fixed inset-0 z-[80] overflow-y-auto bg-void animate-[fade-in_0.4s_ease]" data-lenis-prevent>
  <div class="sticky top-0 z-10 flex items-center justify-between bg-void/70 px-5 py-4 backdrop-blur-md sm:px-8">
    <div class="flex min-w-0 items-center gap-4">
      <button data-close="modal" class="flex items-center gap-2 text-sm text-white/70 hover:text-white" data-cursor-hover><?= icon('x', 16) ?> Close</button>
      <nav aria-label="Breadcrumb" class="hidden min-w-0 items-center gap-2 truncate text-[11px] uppercase tracking-[0.2em] text-white/40 sm:flex">
        <span>Home</span><span>/</span><span>Articles</span><span>/</span><span class="text-white/70"><?= e($item['category']) ?></span>
      </nav>
    </div>
    <button data-bookmark="content:<?= (int)$item['content_id'] ?>" data-bookmark-label class="flex items-center gap-2 text-sm text-white/70 hover:text-white" data-cursor-hover>
      <?= icon('bookmark', 16, 'transition-all ' . ($isBookmarked ? 'fill-amber text-amber scale-110' : '')) ?>
      <span data-part="label"><?= $isBookmarked ? 'Saved' : 'Save' ?></span>
    </button>
  </div>

  <article class="mx-auto max-w-3xl px-5 pb-24 sm:px-8">
    <span class="text-xs font-semibold uppercase tracking-[0.3em] text-cyan"><?= e($item['category']) ?><?= $item['genre'] ? ' · ' . e($item['genre']) : '' ?></span>
    <h1 class="mt-4 font-display text-4xl leading-[1.02] text-white sm:text-6xl"><?= e($item['title']) ?></h1>
    <div class="mt-5 flex items-center gap-5 text-xs text-white/50">
      <span class="flex items-center gap-1.5"><?= icon('calendar', 13) ?> <?= e(fmt_date($item['release_date'])) ?></span>
      <span class="flex items-center gap-1.5"><?= icon('clock', 13) ?> <?= e($item['read_time']) ?></span>
      <span class="flex items-center gap-1.5"><?= icon('eye', 13) ?> <?= number_format((int)$item['views']) ?></span>
      <?php if (!empty($item['author'])): ?><span class="flex items-center gap-1.5"><?= icon('user', 13) ?> Fan submission by <?= e($item['author']) ?></span><?php endif; ?>
    </div>

    <div class="relative mt-8 aspect-[16/9] overflow-hidden rounded-2xl">
      <img src="<?= e(media($item['image'])) ?>" alt="<?= e($item['title']) ?>" class="h-full w-full object-cover" />
    </div>

    <div class="prose-invert mt-10 space-y-6">
      <?php foreach ($limited ? array_slice($paras, 0, 1) : $paras as $p): ?>
        <p class="text-lg leading-relaxed text-white/80" style="font-family:var(--font-sans)"><?= nl2br(e($p)) ?></p>
      <?php endforeach; ?>
    </div>

    <?php if ($limited): ?>
      <div class="mt-10 rounded-2xl border border-white/10 bg-white/[0.03] p-6 text-center">
        <p class="font-display text-2xl text-white">Keep reading in the Fandom Universe</p>
        <p class="mt-2 text-sm text-white/50">Sign in or create a free account to read the full article, bookmark it and rate media.</p>
        <button data-action="auth" class="magnetic-btn mt-5 rounded-full bg-white px-6 py-3 text-xs font-semibold uppercase tracking-widest text-black hover:bg-violet hover:text-white" data-cursor-hover>Sign in to continue</button>
      </div>
    <?php endif; ?>
  </article>
</div>
