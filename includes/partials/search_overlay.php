<?php /* Advanced Search & Discovery (converted from SearchOverlay.tsx). Results rendered by api/search.php from MySQL. */ ?>
<div id="search-overlay" class="fixed inset-0 z-[95] hidden flex-col bg-void/97 backdrop-blur-xl animate-[fade-in_0.35s_ease]" data-lenis-prevent>
  <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col px-5 pt-20 pb-10 sm:px-8 min-h-0">
    <div class="flex items-center justify-between">
      <p class="text-xs uppercase tracking-[0.3em] text-cyan">Advanced Search &amp; Discovery</p>
      <button data-action="search-close" class="text-white/60 hover:text-white" data-cursor-hover aria-label="Close search"><?= icon('x', 20) ?></button>
    </div>

    <div class="mt-6 flex items-center gap-3 border-b border-white/15 pb-4">
      <?= icon('search', 22, 'text-white/50') ?>
      <input id="search-input" autocomplete="off" placeholder="Search characters, articles, trailers, fandoms..."
        class="w-full bg-transparent font-display text-2xl text-white outline-none placeholder-white/30 sm:text-3xl" />
    </div>

    <div id="search-body" class="mt-5 flex min-h-0 flex-1 flex-col"></div>
  </div>
</div>
