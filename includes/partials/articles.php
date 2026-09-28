<?php
/* Featured Articles (converted from Articles.tsx) */
if (!function_exists('bm_icon_class')) { function bm_icon_class(array $bookmarks, string $key): string { return isset($bookmarks[$key]) ? 'fill-amber text-amber' : ''; } }
?>
<section id="articles" class="relative bg-abyss py-24 sm:py-32">
  <div class="mx-auto mb-14 max-w-7xl px-5 sm:px-8">
    <p class="mb-3 text-xs uppercase tracking-[0.35em] text-amber">Featured Articles</p>
    <h2 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">Editorial from the front lines</h2>
  </div>

  <?php if ($featuredArticle): $fa = $featuredArticle; ?>
  <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-5 sm:px-8 lg:grid-cols-5">
    <!-- Hero article -->
    <div role="button" tabindex="0" data-open="article" data-id="<?= (int)$fa['content_id'] ?>" data-cursor-hover
      class="group relative col-span-1 flex min-h-[440px] cursor-pointer flex-col justify-end overflow-hidden rounded-3xl text-left lg:col-span-3">
      <img src="<?= e(media($fa['image'])) ?>" alt="<?= e($fa['title']) ?>" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1200ms] group-hover:scale-105" />
      <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
      <div class="relative p-7 sm:p-10">
        <span class="mb-3 inline-block rounded-full bg-amber px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-black"><?= e($fa['category']) ?></span>
        <h3 class="max-w-lg font-display text-3xl leading-[1.02] text-white sm:text-4xl"><?= e($fa['title']) ?></h3>
        <p class="mt-3 max-w-md text-sm text-white/70"><?= e($fa['excerpt']) ?></p>
        <div class="mt-5 flex items-center gap-5 text-xs text-white/60">
          <span class="flex items-center gap-1.5"><?= icon('calendar', 12) ?> <?= e(fmt_date($fa['release_date'])) ?></span>
          <span class="flex items-center gap-1.5"><?= icon('clock', 12) ?> <?= e($fa['read_time']) ?></span>
          <button data-bookmark="content:<?= (int)$fa['content_id'] ?>" class="ml-auto flex items-center gap-1.5 text-white/70 hover:text-white" data-cursor-hover aria-label="Bookmark">
            <?= icon('bookmark', 14, bm_icon_class($bookmarks, 'content:' . $fa['content_id'])) ?>
          </button>
        </div>
      </div>
    </div>

    <!-- Supporting articles -->
    <div class="col-span-1 flex flex-col gap-4 lg:col-span-2">
      <?php foreach ($restArticles as $a): ?>
        <div role="button" tabindex="0" data-open="article" data-id="<?= (int)$a['content_id'] ?>" data-cursor-hover
          class="group flex cursor-pointer items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.02] p-3 text-left transition hover:border-white/25 hover:bg-white/[0.05]">
          <div class="relative h-20 w-24 flex-none overflow-hidden rounded-xl">
            <img src="<?= e(media($a['image'])) ?>" alt="<?= e($a['title']) ?>" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
          </div>
          <div class="min-w-0 flex-1">
            <span class="text-[10px] font-semibold uppercase tracking-wide text-cyan"><?= e($a['category']) ?></span>
            <h4 class="mt-1 truncate font-display text-lg leading-tight text-white"><?= e($a['title']) ?></h4>
            <div class="mt-1 flex items-center gap-3 text-[11px] text-white/45">
              <span><?= e(fmt_date($a['release_date'])) ?></span>
              <span><?= e($a['read_time']) ?></span>
            </div>
          </div>
          <button data-bookmark="content:<?= (int)$a['content_id'] ?>" class="flex-none text-white/50 hover:text-white" data-cursor-hover aria-label="Bookmark">
            <?= icon('bookmark', 15, bm_icon_class($bookmarks, 'content:' . $a['content_id'])) ?>
          </button>
        </div>
      <?php endforeach; ?>
      <a href="#articles" data-search-type="article" class="mt-1 flex items-center justify-center gap-2 rounded-2xl border border-dashed border-white/15 py-4 text-xs font-semibold uppercase tracking-widest text-white/60 hover:border-white/40 hover:text-white" data-cursor-hover>
        View all articles <?= icon('arrow-up-right', 14) ?>
      </a>
    </div>
  </div>
  <?php endif; ?>
</section>
