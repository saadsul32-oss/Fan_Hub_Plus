<?php /* Fandom Explorer (converted from FandomExplorer.tsx). Categories come from the `categories` table. */ ?>
<section id="fandoms" class="relative bg-void py-24 sm:py-32">
  <div class="mx-auto mb-14 max-w-7xl px-5 sm:px-8">
    <p class="mb-3 text-xs uppercase tracking-[0.35em] text-cyan/80">Fandom Universe Explorer</p>
    <h2 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl lg:text-7xl">
      <?= e(count($cats) === 8 ? 'Eight' : count($cats)) ?> worlds. <span class="text-white/40">One universe.</span>
    </h2>
  </div>

  <div class="mx-auto max-w-7xl px-5 sm:px-8">
    <!-- Desktop interactive panel system -->
    <div id="fandom-panels" class="hidden gap-2 overflow-hidden rounded-3xl lg:flex" style="height:70vh">
      <?php foreach ($cats as $i => $f): $active = $i === 0; ?>
        <button data-cursor-hover data-panel="<?= $i ?>" data-glow="<?= e($f['glow']) ?>" data-search-category="<?= e($f['name']) ?>"
          class="fandom-panel group relative h-full overflow-hidden text-left transition-all duration-700 ease-[cubic-bezier(.22,1,.36,1)]"
          style="flex:<?= $active ? 6 : 1 ?>">
          <img src="<?= e(media($f['image'])) ?>" alt="<?= e($f['name']) ?>" loading="lazy" data-part="img"
            class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1200ms] ease-out"
            style="transform:<?= $active ? 'scale(1.06)' : 'scale(1.25)' ?>;filter:<?= $active ? 'saturate(1.1)' : 'saturate(0.4) brightness(0.55)' ?>" />
          <div data-part="shade" class="absolute inset-0 transition-opacity duration-700"
            style="background:<?= $active ? 'linear-gradient(0deg, rgba(5,5,8,0.95) 0%, rgba(5,5,8,0.3) 55%, rgba(5,5,8,0.15) 100%)' : 'linear-gradient(0deg, rgba(5,5,8,0.9) 0%, rgba(5,5,8,0.55) 100%)' ?>"></div>
          <div data-part="glow" class="absolute inset-0 transition-opacity duration-700"
            style="box-shadow:<?= $active ? 'inset 0 0 120px ' . e($f['glow']) : 'none' ?>;opacity:<?= $active ? 1 : 0 ?>"></div>

          <!-- Collapsed label -->
          <div data-part="collapsed" class="absolute inset-0 flex items-end justify-center pb-8 transition-opacity duration-500 <?= $active ? 'opacity-0' : 'opacity-100' ?>">
            <span class="font-display text-lg tracking-[0.2em] text-white/70" style="writing-mode:vertical-rl"><?= e($f['name']) ?></span>
          </div>

          <!-- Expanded content -->
          <div data-part="expanded" class="absolute inset-0 flex flex-col justify-end p-8 transition-all duration-500 <?= $active ? 'opacity-100 translate-y-0 delay-150' : 'pointer-events-none opacity-0 translate-y-6' ?>">
            <span class="mb-3 inline-block w-fit rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.25em]" style="background:<?= e($f['glow']) ?>;color:#0a0a12">
              Universe <?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?>
            </span>
            <h3 class="font-display text-4xl text-white xl:text-5xl"><?= e($f['name']) ?></h3>
            <p class="mt-1 text-sm font-medium" style="color:<?= e($f['color']) ?>"><?= e($f['tagline']) ?></p>
            <p class="mt-3 max-w-sm text-sm leading-relaxed text-white/70"><?= e($f['description']) ?></p>
            <div class="mt-5 flex gap-6">
              <?php foreach ([[$f['stat1_label'], $f['stat1_value']], [$f['stat2_label'], $f['stat2_value']]] as [$l, $v]): if ($l === '') continue; ?>
                <div>
                  <div class="font-display text-2xl text-white"><?= e($v) ?></div>
                  <div class="text-[10px] uppercase tracking-wide text-white/50"><?= e($l) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="mt-6 inline-flex w-fit items-center gap-2 rounded-full border border-white/25 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition-colors hover:bg-white hover:text-black">
              Explore <?= e($f['name']) ?> <?= icon('arrow-up-right', 14) ?>
            </div>
          </div>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- Mobile / tablet stacked cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:hidden">
      <?php foreach ($cats as $f): ?>
        <div class="card-glow group relative h-64 overflow-hidden rounded-2xl noise-border" data-cursor-hover data-search-category="<?= e($f['name']) ?>" role="button" tabindex="0">
          <img src="<?= e(media($f['image'])) ?>" alt="<?= e($f['name']) ?>" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-active:scale-110" />
          <div class="absolute inset-0 bg-gradient-to-t from-void via-void/50 to-transparent"></div>
          <div class="absolute inset-0 flex flex-col justify-end p-5">
            <span class="mb-1 text-[10px] font-semibold uppercase tracking-[0.25em]" style="color:<?= e($f['color']) ?>"><?= e($f['tagline']) ?></span>
            <h3 class="font-display text-3xl text-white"><?= e($f['name']) ?></h3>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
