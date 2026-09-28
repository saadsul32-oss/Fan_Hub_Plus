<?php
/**
 * Characters section — spotlight (3D coverflow) + filterable character grid.
 * Expects: $characters (all, with category + category_color), $carousel (featured subset), $bookmarks
 */
if (empty($characters)) return;

$count     = count($carousel);
$catCounts = [];
foreach ($characters as $c) $catCounts[$c['category']] = ($catCounts[$c['category']] ?? 0) + 1;
$fandoms   = array_values(array_unique(array_map('character_fandom', $characters)));
sort($fandoms, SORT_NATURAL | SORT_FLAG_CASE);
?>
<section id="characters" class="relative overflow-hidden bg-abyss py-24 sm:py-32" aria-labelledby="characters-title">
  <!-- ambient light -->
  <div class="pointer-events-none absolute -left-40 top-24 h-[520px] w-[520px] rounded-full opacity-40 blur-3xl" style="background:radial-gradient(circle, rgba(139,92,246,0.35), transparent 65%)"></div>
  <div class="pointer-events-none absolute -right-40 top-[420px] h-[460px] w-[460px] rounded-full opacity-30 blur-3xl" style="background:radial-gradient(circle, rgba(34,211,238,0.28), transparent 65%)"></div>

  <div class="relative mx-auto max-w-7xl px-5 sm:px-8">
    <!-- Heading -->
    <div class="mb-12 flex flex-col gap-6 sm:mb-16 md:flex-row md:items-end md:justify-between">
      <div>
        <p class="mb-3 text-xs uppercase tracking-[0.35em] text-violet">Character Showcase</p>
        <h2 id="characters-title" class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">Icons of the universe</h2>
        <p class="mt-3 max-w-xl text-white/60">Swipe through the spotlight, then open a full cinematic profile — biography, timeline and related media.</p>
      </div>
      <div class="flex gap-8">
        <div>
          <p class="font-display text-4xl leading-none text-white"><?= pad2(count($characters)) ?></p>
          <p class="mt-1 text-[10px] uppercase tracking-[0.3em] text-white/40">Characters</p>
        </div>
        <div>
          <p class="font-display text-4xl leading-none text-white"><?= pad2(count($fandoms)) ?></p>
          <p class="mt-1 text-[10px] uppercase tracking-[0.3em] text-white/40">Fandoms</p>
        </div>
        <div>
          <p class="font-display text-4xl leading-none text-white"><?= pad2(count($catCounts)) ?></p>
          <p class="mt-1 text-[10px] uppercase tracking-[0.3em] text-white/40">Universes</p>
        </div>
      </div>
    </div>

    <!-- ============ Spotlight (3D coverflow) ============ -->
    <div id="char-spotlight" class="grid grid-cols-1 items-center gap-10 lg:grid-cols-[1.1fr_1fr] lg:gap-14" data-count="<?= $count ?>"
         role="region" aria-roledescription="carousel" aria-label="Featured characters">
      <div class="relative min-w-0">
        <div id="cf-stage" class="relative mx-auto h-[380px] w-full touch-pan-y select-none outline-none sm:h-[470px]" style="perspective:1400px" tabindex="0" aria-label="Featured characters — use the arrow keys to browse">
          <?php foreach ($carousel as $i => $c): ?>
            <button type="button" data-cursor-hover data-cf-card="<?= $i ?>" data-id="<?= (int)$c['character_id'] ?>"
              aria-label="<?= e($c['name']) ?> — <?= e($c['fandom_label']) ?>"
              class="cf-card absolute left-1/2 top-1/2 h-[310px] w-[210px] overflow-hidden rounded-3xl text-left noise-border shadow-2xl sm:h-[390px] sm:w-[270px]"
              style="transform:translate(-50%,-50%) translateX(<?= $i * 60 ?>%) scale(<?= $i ? 0.8 : 1 ?>);opacity:<?= $i ? 0.5 : 1 ?>;z-index:<?= 50 - $i ?>">
              <img src="<?= e(media($c['image'])) ?>" alt="" loading="<?= $i < 3 ? 'eager' : 'lazy' ?>" draggable="false" data-part="img"
                class="absolute inset-0 h-full w-full object-cover transition-[filter,transform] duration-700" />
              <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent"></div>
              <div data-part="glow" class="pointer-events-none absolute inset-0 rounded-3xl transition-opacity duration-500" style="box-shadow:inset 0 0 0 1px rgba(167,139,250,0.55), inset 0 0 90px rgba(139,92,246,0.45);opacity:<?= $i ? 0 : 1 ?>"></div>
              <div class="absolute inset-x-0 bottom-0 p-5">
                <span class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.25em] text-cyan"><?= e($c['category']) ?></span>
                <span class="block font-display text-2xl leading-none text-white sm:text-3xl"><?= e($c['name']) ?></span>
              </div>
            </button>
          <?php endforeach; ?>
          <div class="pointer-events-none absolute inset-x-[10%] -bottom-2 h-16" style="background:radial-gradient(ellipse at center, rgba(139,92,246,0.35), transparent 70%);filter:blur(8px)"></div>
        </div>

        <div class="mt-6 flex items-center justify-center gap-4">
          <button type="button" data-cf-prev data-cursor-hover aria-label="Previous character" class="flex h-11 w-11 items-center justify-center rounded-full border border-white/15 text-white/80 transition hover:border-white/40 hover:text-white"><?= icon('chevron-left', 18) ?></button>
          <div class="flex items-center gap-2">
            <?php foreach ($carousel as $i => $c): ?>
              <button type="button" data-cf-dot="<?= $i ?>" aria-label="Show <?= e($c['name']) ?>" aria-current="<?= $i ? 'false' : 'true' ?>"
                class="cf-dot h-1.5 rounded-full transition-all duration-300" data-cursor-hover
                style="width:<?= $i ? 8 : 28 ?>px;background:<?= $i ? 'rgba(255,255,255,0.2)' : 'linear-gradient(90deg, var(--color-violet), var(--color-cyan))' ?>"></button>
            <?php endforeach; ?>
          </div>
          <button type="button" data-cf-next data-cursor-hover aria-label="Next character" class="flex h-11 w-11 items-center justify-center rounded-full border border-white/15 text-white/80 transition hover:border-white/40 hover:text-white"><?= icon('chevron-right', 18) ?></button>
        </div>
        <p class="mt-3 text-center text-[10px] uppercase tracking-[0.3em] text-white/35">Drag · swipe · arrow keys — tap the centre card to open</p>
      </div>

      <!-- Info panel for the active card -->
      <div class="relative min-w-0" aria-live="polite">
        <div class="relative">
          <?php foreach ($carousel as $i => $c): $bm = isset($bookmarks['character:' . $c['character_id']]); ?>
            <div data-cf-info="<?= $i ?>" class="cf-info transition-all duration-500 <?= $i ? 'pointer-events-none absolute inset-x-0 top-0 opacity-0 translate-y-4' : 'relative opacity-100 translate-y-0' ?>" <?= $i ? 'aria-hidden="true"' : '' ?>>
              <div class="flex items-center gap-3 text-xs text-white/40">
                <span class="font-display text-2xl leading-none text-white"><?= pad2($i + 1) ?></span>
                <span class="h-px w-10 bg-white/20"></span>
                <span class="tracking-[0.25em]"><?= pad2($count) ?></span>
              </div>
              <p class="mt-6 text-[11px] font-semibold uppercase tracking-[0.3em] text-cyan"><?= e($c['fandom_label']) ?></p>
              <h3 class="mt-2 font-display text-6xl leading-[0.9] text-white sm:text-7xl"><?= e($c['name']) ?></h3>
              <div class="mt-4 flex flex-wrap items-center gap-2">
                <?php if ($c['role'] !== ''): ?><span class="rounded-full border border-white/20 px-3 py-1 text-[10px] uppercase tracking-widest text-white/75"><?= e($c['role']) ?></span><?php endif; ?>
                <span class="flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[10px] uppercase tracking-widest text-white/60">
                  <span class="h-1.5 w-1.5 rounded-full" style="background:<?= e($c['category_color'] ?: '#8b5cf6') ?>"></span><?= e($c['category']) ?>
                </span>
                <span class="flex items-center gap-1.5 text-[11px] text-white/45"><?= icon('eye', 13) ?> <?= number_format((int)$c['views']) ?> views</span>
              </div>
              <p class="mt-5 max-w-lg text-base leading-relaxed text-white/80"><?= e($c['short_desc']) ?></p>
              <p class="mt-3 line-clamp-3 max-w-lg text-sm leading-relaxed text-white/50"><?= e($c['bio']) ?></p>
              <div class="mt-8 flex flex-wrap items-center gap-3">
                <button type="button" data-open="character" data-id="<?= (int)$c['character_id'] ?>" data-cursor-hover
                  class="magnetic-btn group flex items-center gap-2 rounded-full bg-white px-6 py-3 text-xs font-bold uppercase tracking-[0.2em] text-black hover:shadow-[0_0_40px_rgba(139,92,246,0.55)]">
                  Full profile <?= icon('arrow-up-right', 15, 'transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5') ?>
                </button>
                <button type="button" data-bookmark="character:<?= (int)$c['character_id'] ?>" data-cursor-hover
                  class="flex items-center gap-2 rounded-full border border-white/15 px-5 py-3 text-xs font-semibold uppercase tracking-[0.2em] text-white/80 transition hover:border-white/40 hover:text-white">
                  <?= icon('bookmark', 14, $bm ? 'fill-amber text-amber' : '') ?> <span data-part="label"><?= $bm ? 'Saved' : 'Save' ?></span>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-8 h-px w-full max-w-lg overflow-hidden bg-white/10">
          <div id="cf-progress" class="h-full bg-gradient-to-r from-violet via-magenta to-cyan" style="width:0%"></div>
        </div>
      </div>
    </div>

    <!-- ============ All characters ============ -->
    <div id="char-browser" class="mt-24 sm:mt-32">
      <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs uppercase tracking-[0.3em] text-white/40">All characters</p>
          <p class="mt-1 text-sm text-white/55">Showing <span id="char-count" class="font-semibold text-white"><?= count($characters) ?></span> of <?= count($characters) ?></p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <label class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2.5 focus-within:border-violet/60">
            <?= icon('search', 14, 'text-white/40') ?>
            <input id="char-search" type="search" placeholder="Search name, role, fandom…" autocomplete="off" aria-label="Search characters"
              class="w-full bg-transparent text-sm text-white outline-none placeholder:text-white/35 sm:w-52" />
          </label>
          <div class="flex gap-2">
            <select id="char-fandom" aria-label="Filter by fandom" class="min-w-0 flex-1 cursor-pointer rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white/80 outline-none focus:border-violet/60">
              <option value="">All fandoms</option>
              <?php foreach ($fandoms as $fd): ?><option value="<?= e($fd) ?>"><?= e($fd) ?></option><?php endforeach; ?>
            </select>
            <select id="char-sort" aria-label="Sort characters" class="min-w-0 flex-1 cursor-pointer rounded-full border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white/80 outline-none focus:border-violet/60">
              <option value="featured">Featured</option>
              <option value="popular">Most viewed</option>
              <option value="az">A – Z</option>
            </select>
          </div>
        </div>
      </div>

      <div id="char-filters" class="mb-8 flex flex-wrap items-center gap-2" role="group" aria-label="Filter by category">
        <?= icon('sliders-horizontal', 14, 'mr-1 text-white/40') ?>
        <button type="button" data-char-cat="" aria-pressed="true" class="char-chip rounded-full border px-3.5 py-1.5 text-xs font-medium transition border-white bg-white text-black" data-cursor-hover>All <span class="opacity-50"><?= count($characters) ?></span></button>
        <?php foreach ($catCounts as $cc => $n): ?>
          <button type="button" data-char-cat="<?= e($cc) ?>" aria-pressed="false" class="char-chip rounded-full border px-3.5 py-1.5 text-xs font-medium transition border-white/15 text-white/60 hover:border-white/40" data-cursor-hover><?= e($cc) ?> <span class="opacity-50"><?= $n ?></span></button>
        <?php endforeach; ?>
      </div>

      <div id="char-grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-5">
        <?php foreach ($characters as $idx => $c): $bm = isset($bookmarks['character:' . $c['character_id']]); ?>
          <article class="char-card group relative aspect-[3/4.3] overflow-hidden rounded-2xl noise-border transition-transform duration-500 hover:-translate-y-1"
            data-order="<?= $idx ?>" data-views="<?= (int)$c['views'] ?>" data-cat="<?= e($c['category']) ?>" data-fandom="<?= e(character_fandom($c)) ?>"
            data-name="<?= e($c['name']) ?>" data-search="<?= e(mb_strtolower($c['name'] . ' ' . $c['role'] . ' ' . $c['fandom_label'] . ' ' . $c['category'] . ' ' . $c['short_desc'])) ?>">
            <img src="<?= e(media($c['image'])) ?>" alt="<?= e($c['name']) ?>" loading="lazy"
              class="char-img absolute inset-0 h-full w-full object-cover transition-all duration-700 ease-out group-hover:scale-[1.08]" />
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/25 to-transparent"></div>
            <div class="pointer-events-none absolute inset-0 rounded-2xl opacity-0 transition-opacity duration-500 group-hover:opacity-100" style="box-shadow:inset 0 0 90px rgba(139,92,246,0.5)"></div>

            <!-- the whole card opens the profile -->
            <button type="button" data-open="character" data-id="<?= (int)$c['character_id'] ?>" data-cursor-hover
              class="absolute inset-0 z-[1] rounded-2xl outline-none focus-visible:ring-2 focus-visible:ring-violet" aria-label="Open <?= e($c['name']) ?> profile"></button>

            <div class="pointer-events-none absolute inset-x-0 top-0 z-[2] flex items-start justify-between p-3">
              <span class="flex items-center gap-1.5 rounded-full bg-black/55 px-2.5 py-1 text-[9px] font-semibold uppercase tracking-[0.18em] text-white/85 backdrop-blur">
                <span class="h-1.5 w-1.5 rounded-full" style="background:<?= e($c['category_color'] ?: '#8b5cf6') ?>"></span><?= e($c['category']) ?>
              </span>
              <button type="button" data-bookmark="character:<?= (int)$c['character_id'] ?>" aria-label="Bookmark <?= e($c['name']) ?>" data-cursor-hover
                class="pointer-events-auto flex h-8 w-8 items-center justify-center rounded-full bg-black/55 text-white/85 backdrop-blur transition hover:bg-black/75">
                <?= icon('bookmark', 13, $bm ? 'fill-amber text-amber' : '') ?>
              </button>
            </div>

            <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[2] p-4">
              <span class="char-reveal mb-1 block text-[9px] font-semibold uppercase tracking-[0.2em] text-cyan"><?= e($c['fandom_label']) ?></span>
              <h3 class="font-display text-xl leading-none text-white sm:text-2xl"><?= e($c['name']) ?></h3>
              <?php if ($c['role'] !== ''): ?><p class="mt-1 text-[10px] uppercase tracking-widest text-white/50"><?= e($c['role']) ?></p><?php endif; ?>
              <p class="char-reveal-desc mt-2 text-xs leading-snug text-white/75"><?= e($c['short_desc']) ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <div id="char-empty" class="hidden rounded-3xl border border-dashed border-white/15 px-6 py-16 text-center">
        <p class="font-display text-3xl text-white">No characters found</p>
        <p class="mt-2 text-sm text-white/50">Try another name, fandom or category.</p>
        <button type="button" id="char-reset" data-cursor-hover class="mt-6 rounded-full border border-white/20 px-5 py-2.5 text-xs font-semibold uppercase tracking-[0.2em] text-white/80 hover:border-white/50 hover:text-white">Reset filters</button>
      </div>
    </div>
  </div>
</section>
