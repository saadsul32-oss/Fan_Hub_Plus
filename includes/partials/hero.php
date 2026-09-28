<?php
/* Hero (converted from Hero.tsx) — eyebrow / subtitle / background image are admin-editable (Site Control tab) */
$heroImg = setting('hero_image') !== '' ? media(setting('hero_image')) : url('images/hero-universe.jpg');
?>
<section id="top" class="relative h-[100svh] w-full overflow-hidden bg-void">
  <div id="hero-layer-c" class="absolute inset-0 transition-transform duration-300 ease-out">
    <img src="<?= e($heroImg) ?>" alt=""
      class="h-full w-full object-cover transition-all duration-[2200ms] ease-out opacity-0 scale-110 blur-2xl"
      data-ready-on="opacity-60 scale-100 blur-0" data-ready-off="opacity-0 scale-110 blur-2xl" />
    <div class="absolute inset-0 bg-gradient-to-b from-void/40 via-void/60 to-void"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-void via-transparent to-transparent"></div>
  </div>

  <div id="hero-layer-a" class="pointer-events-none absolute inset-0 transition-transform duration-300 ease-out">
    <div class="absolute left-[8%] top-[18%] h-72 w-72 rounded-full bg-violet/25 blur-[100px]"></div>
    <div class="absolute right-[10%] bottom-[15%] h-96 w-96 rounded-full bg-magenta/20 blur-[120px]"></div>
  </div>

  <div id="hero-layer-b" class="pointer-events-none absolute inset-0 transition-transform duration-300 ease-out">
    <?php for ($i = 0; $i < 60; $i++): $s = $i % 7 === 0 ? 3 : 1.5; ?>
      <span class="hero-star absolute rounded-full bg-white transition-opacity duration-[2500ms]" data-o="<?= 0.15 + (($i % 6) * 0.08) ?>"
        style="width:<?= $s ?>px;height:<?= $s ?>px;top:<?= ($i * 37) % 100 ?>%;left:<?= ($i * 53) % 100 ?>%;opacity:0;box-shadow:0 0 8px rgba(255,255,255,0.6);transition-delay:<?= $i * 12 ?>ms"></span>
    <?php endfor; ?>
  </div>

  <div id="hero-content" class="relative z-10 flex h-full flex-col items-center justify-center px-4 text-center">
    <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 backdrop-blur-sm transition-all duration-1000 opacity-0 translate-y-4"
      data-ready-on="opacity-100 translate-y-0" data-ready-off="opacity-0 translate-y-4">
      <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-cyan"></span>
      <span class="text-[11px] uppercase tracking-[0.3em] text-white/70"><?= e(setting('hero_eyebrow', 'Fandom Universe Now Open')) ?></span>
    </div>

    <h1 class="font-display leading-[0.85] text-white transition-all duration-[1400ms] ease-out opacity-0 translate-y-10 blur-md"
      data-ready-on="opacity-100 translate-y-0 blur-0" data-ready-off="opacity-0 translate-y-10 blur-md">
      <span class="block text-[16vw] tracking-wide sm:text-[10vw] lg:text-[8.5vw] text-glow">
        FAN HUB <span class="text-gradient">PLUS</span>
      </span>
    </h1>

    <p class="mt-3 font-display text-[6vw] tracking-[0.15em] text-white/85 sm:text-[3vw] lg:text-[2.1vw] transition-all delay-200 duration-[1400ms] ease-out opacity-0 translate-y-10"
      data-ready-on="opacity-100 translate-y-0" data-ready-off="opacity-0 translate-y-10">
      <?= e(setting('hero_subtitle', 'ENTER THE FANDOM UNIVERSE')) ?>
    </p>

    <div class="mt-7 flex h-7 items-center justify-center overflow-hidden font-sans text-sm text-white/60 transition-all delay-500 duration-1000 sm:text-base opacity-0"
      data-ready-on="opacity-100" data-ready-off="opacity-0">
      <span>Explore&nbsp;</span>
      <span id="hero-word" class="relative inline-block min-w-[6ch] font-semibold text-cyan animate-[fade-in_0.5s_ease]">ANIME</span>
      <span>&nbsp;and more</span>
    </div>

    <div class="mt-10 flex flex-col gap-4 sm:flex-row transition-all delay-700 duration-1000 opacity-0 translate-y-6"
      data-ready-on="opacity-100 translate-y-0" data-ready-off="opacity-0 translate-y-6">
      <a href="#fandoms" data-cursor-hover class="magnetic-btn rounded-full bg-white px-8 py-3.5 text-sm font-semibold uppercase tracking-widest text-black shadow-[0_0_40px_rgba(255,255,255,0.25)] hover:shadow-[0_0_60px_rgba(139,92,246,0.6)] hover:bg-violet hover:text-white">
        Enter the Universe
      </a>
      <a href="#media" data-cursor-hover class="magnetic-btn rounded-full border border-white/25 bg-white/5 px-8 py-3.5 text-sm font-semibold uppercase tracking-widest text-white backdrop-blur-sm hover:border-white/60">
        Watch Trailer
      </a>
    </div>
  </div>

  <div class="absolute bottom-8 left-1/2 z-10 flex -translate-x-1/2 flex-col items-center gap-2 transition-opacity duration-1000 delay-1000 opacity-0"
    data-ready-on="opacity-100" data-ready-off="opacity-0">
    <span class="text-[10px] uppercase tracking-[0.3em] text-white/45">Scroll</span>
    <div class="flex h-9 w-6 items-start justify-center rounded-full border border-white/25 p-1.5">
      <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-white/70"></span>
    </div>
    <?= icon('chevron-down', 14, 'text-white/30') ?>
  </div>
</section>
