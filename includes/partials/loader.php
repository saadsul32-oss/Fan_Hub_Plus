<?php
/* Loader (converted from Loader.tsx). Plays once per browser session. */
$words = [['text' => 'FAN HUB', 'accent' => false], ['text' => 'PLUS', 'accent' => true]];
?>
<div id="loader" class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-void transition-opacity duration-700 ease-out opacity-100">
  <div class="absolute inset-0 overflow-hidden">
    <?php for ($i = 0; $i < 40; $i++): ?>
      <span class="loader-star absolute rounded-full bg-white transition-all duration-1000" data-o="<?= 0.3 + (($i % 5) * 0.1) ?>"
        style="width:2px;height:2px;top:<?= ($i * 47) % 100 ?>%;left:<?= ($i * 71) % 100 ?>%;opacity:0;transform:scale(0);transition-delay:<?= $i * 18 ?>ms;box-shadow:0 0 6px rgba(255,255,255,0.8)"></span>
    <?php endfor; ?>
  </div>

  <div class="relative flex flex-col items-center gap-4">
    <div class="flex flex-wrap items-baseline justify-center gap-x-4 font-display text-5xl tracking-widest text-white sm:text-7xl">
      <?php $idx = 0; foreach ($words as $w): ?>
        <span class="inline-flex overflow-hidden">
          <?php foreach (str_split($w['text']) as $ch): ?>
            <span class="loader-char inline-block transition-all ease-out <?= $w['accent'] ? 'text-violet' : '' ?>"
              style="transition-duration:700ms;transition-delay:<?= $idx * 45 ?>ms;opacity:0;transform:translateY(110%) rotateX(60deg)"><?= $ch === ' ' ? '&nbsp;' : e($ch) ?></span>
          <?php $idx++; endforeach; ?>
        </span>
      <?php endforeach; ?>
    </div>
    <div id="loader-line" class="h-px w-0 bg-gradient-to-r from-transparent via-white/60 to-transparent transition-all duration-700 opacity-0"></div>
    <p id="loader-text" class="font-sans text-[11px] tracking-[0.4em] uppercase text-white/50 transition-all duration-700 opacity-0 translate-y-2">Entering the Fandom Universe</p>
  </div>
</div>
<script>
  try { if (sessionStorage.getItem('fh_loaded')) { document.getElementById('loader').remove(); document.getElementById('main-content') && 0; window.FH_SKIP_LOADER = true; } } catch (e) {}
</script>
