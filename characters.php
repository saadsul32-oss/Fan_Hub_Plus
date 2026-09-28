<?php
/**
 * Characters Page — Dedicated page for character showcase with cylinder carousel
 * Converted from the single-page index.php with ZIP design elements
 */
require_once __DIR__ . '/includes/functions.php';

$user = current_user();
$uid = $user ? (int)$user['user_id'] : null;
$bookmarks = user_bookmark_keys($uid);
$cats = categories();

// Characters data
$carousel = q_all("SELECT ch.*, cat.name AS category, cat.color AS category_color FROM characters ch JOIN categories cat USING(category_id) ORDER BY ch.character_id LIMIT 6");
$characters = q_all("SELECT ch.*, cat.name AS category, cat.color AS category_color FROM characters ch JOIN categories cat USING(category_id) ORDER BY ch.character_id");
$count = max(1, count($carousel));
$angleStep = 360 / $count;
$radius = (int)round($count * 62);
$charCats = array_values(array_unique(array_column($characters, 'category')));
$charFandoms = array_values(array_unique(array_map(fn($c) => trim(explode('·', $c['fandom_label'])[1] ?? $c['fandom_label']), $characters)));

include __DIR__ . '/includes/partials/head.php';
?>
<div class="relative min-h-screen bg-void">
  <?php include __DIR__ . '/includes/partials/loader.php'; ?>

  <div class="grain"></div>
  <?php include __DIR__ . '/includes/partials/cursor.php'; ?>
  <div class="fixed top-0 left-0 right-0 z-[70] h-[2px] bg-transparent">
    <div id="scroll-progress" class="h-full bg-gradient-to-r from-violet via-magenta to-cyan transition-[width] duration-100 ease-linear" style="width:0%;box-shadow:0 0 12px rgba(139,92,246,0.8)"></div>
  </div>
  <a href="#top" class="fixed left-2 top-2 z-[100] -translate-y-20 rounded-full bg-white px-4 py-2 text-xs font-semibold text-black transition-transform focus:translate-y-0">Skip to content</a>

  <?php include __DIR__ . '/includes/partials/navbar.php'; ?>

  <main id="main-content" class="transition-opacity duration-700 opacity-0">
    <!-- Characters Section -->
    <section id="characters" class="relative overflow-hidden bg-abyss py-24 sm:py-32">
      <div class="mx-auto mb-14 max-w-7xl px-5 sm:px-8">
        <p class="mb-3 text-xs uppercase tracking-[0.35em] text-violet">Character Showcase</p>
        <h2 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">Icons of the universe</h2>
        <p class="mt-3 max-w-xl text-white/60">Spin the carousel to browse featured icons, then open a full cinematic profile.</p>
      </div>

      <!-- Cylinder carousel -->
      <div id="cylinder" class="relative select-none" data-count="<?= $count ?>" data-radius="<?= $radius ?>">
        <div id="cylinder-stage" class="relative mx-auto h-[420px] max-w-full touch-pan-y sm:h-[480px]" style="perspective:1400px">
          <div class="absolute left-1/2 top-1/2 h-[320px] w-[220px] -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing sm:h-[380px] sm:w-[260px]" style="transform-style:preserve-3d">
            <div id="cylinder-track" class="absolute inset-0" style="transform-style:preserve-3d;transform:translateZ(-<?= $radius ?>px)">
              <?php foreach ($carousel as $i => $c): $front = $i === 0; ?>
                <button data-cursor-hover data-open="character" data-card="<?= $i ?>" data-id="<?= (int)$c['character_id'] ?>"
                  class="cyl-card absolute inset-0 overflow-hidden rounded-2xl text-left noise-border"
                  style="transform:rotateY(<?= $i * $angleStep ?>deg) translateZ(<?= $radius ?>px);transform-style:preserve-3d;backface-visibility:hidden">
                  <img src="<?= e(media($c['image'])) ?>" alt="<?= e($c['name']) ?>" loading="lazy" draggable="false" data-part="img"
                    class="absolute inset-0 h-full w-full object-cover transition-all duration-500"
                    style="filter:<?= $front ? 'grayscale(0) brightness(1)' : 'grayscale(0.65) brightness(0.55)' ?>;transform:scale(1)" />
                  <div class="absolute inset-0 bg-gradient-to-t from-black via-black/25 to-transparent"></div>
                  <div data-part="glow" class="absolute inset-0 transition-opacity duration-500" style="box-shadow:inset 0 0 90px rgba(139,92,246,0.55);opacity:0"></div>
                  <div class="absolute inset-x-0 bottom-0 p-4">
                    <span data-part="fandom" class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.2em] text-cyan transition-opacity duration-500" style="opacity:<?= $front ? 1 : 0 ?>"><?= e($c['fandom_label']) ?></span>
                    <h3 class="font-display text-xl leading-none text-white sm:text-2xl"><?= e($c['name']) ?></h3>
                  </div>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="pointer-events-none absolute inset-x-0 bottom-6 h-24" style="background:radial-gradient(ellipse at center, rgba(139,92,246,0.18), transparent 70%)"></div>
        </div>

        <div class="mt-8 flex items-center justify-center gap-2">
          <?php foreach ($carousel as $i => $c): ?>
            <button data-cursor-hover data-dot="<?= $i ?>" aria-label="Show <?= e($c['name']) ?>" class="cyl-dot h-1.5 rounded-full transition-all duration-300"
              style="width:<?= $i === 0 ? 28 : 8 ?>px;background:<?= $i === 0 ? 'linear-gradient(90deg, var(--color-violet), var(--color-cyan))' : 'rgba(255,255,255,0.2)' ?>"></button>
          <?php endforeach; ?>
        </div>
        <p class="mt-3 text-center text-xs uppercase tracking-[0.3em] text-white/35">Drag to rotate · click front card to open</p>
      </div>
      <script>
      (function () {
        var wrap = document.getElementById('cylinder');
        if (!wrap || wrap.dataset.bound) return;
        wrap.dataset.bound = '1';
        var track = document.getElementById('cylinder-track');
        var cards = Array.prototype.slice.call(wrap.querySelectorAll('.cyl-card'));
        var dots = Array.prototype.slice.call(wrap.querySelectorAll('.cyl-dot'));
        var count = +wrap.dataset.count || cards.length || 1;
        var radius = +wrap.dataset.radius || 0;
        var angleStep = 360 / count;
        var angle = 0, current = 0, dragging = false, startX = 0, startAngle = 0, moved = false;

        function apply() {
          track.style.transform = 'translateZ(-' + radius + 'px) rotateY(' + angle + 'deg)';
          var norm = ((-angle % 360) + 360) % 360;
          current = Math.round(norm / angleStep) % count;
          cards.forEach(function (c, i) {
            var front = i === current;
            var img = c.querySelector('[data-part="img"]');
            if (img) img.style.filter = front ? 'grayscale(0) brightness(1)' : 'grayscale(0.65) brightness(0.55)';
            var glow = c.querySelector('[data-part="glow"]');
            if (glow) glow.style.opacity = front ? '1' : '0';
            var fandom = c.querySelector('[data-part="fandom"]');
            if (fandom) fandom.style.opacity = front ? '1' : '0';
          });
          dots.forEach(function (d, i) {
            var on = i === current;
            d.style.width = on ? '28px' : '8px';
            d.style.background = on ? 'linear-gradient(90deg, var(--color-violet), var(--color-cyan))' : 'rgba(255,255,255,0.2)';
          });
        }
        function goTo(i) { angle = -i * angleStep; apply(); }

        wrap.addEventListener('pointerdown', function (e) {
          dragging = true; moved = false; startX = e.clientX; startAngle = angle;
          try { wrap.setPointerCapture(e.pointerId); } catch (err) {}
        });
        wrap.addEventListener('pointermove', function (e) {
          if (!dragging) return;
          var dx = e.clientX - startX;
          if (Math.abs(dx) > 4) moved = true;
          angle = startAngle + dx * 0.35;
          apply();
        });
        function endDrag() {
          if (!dragging) return;
          dragging = false;
          angle = Math.round(angle / angleStep) * angleStep;
          apply();
        }
        wrap.addEventListener('pointerup', endDrag);
        wrap.addEventListener('pointerleave', endDrag);
        cards.forEach(function (c) {
          c.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } });
        });
        dots.forEach(function (d, i) { d.addEventListener('click', function () { goTo(i); }); });

        apply();
      })();
      </script>

      <!-- All characters grid -->
      <div class="mx-auto mt-24 max-w-7xl px-5 sm:px-8">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
          <p class="text-xs uppercase tracking-[0.3em] text-white/40">All characters</p>
          <!-- Category / fandom filter -->
          <div id="char-filters" class="flex flex-wrap items-center gap-2">
            <?= icon('sliders-horizontal', 14, 'mr-1 text-white/40') ?>
            <button data-char-filter="all" class="char-chip rounded-full border px-3.5 py-1.5 text-xs font-medium transition border-white bg-white text-black" data-cursor-hover>All</button>
            <?php foreach ($charCats as $cc): ?>
              <button data-char-filter="cat:<?= e($cc) ?>" class="char-chip rounded-full border px-3.5 py-1.5 text-xs font-medium transition border-white/15 text-white/60 hover:border-white/40" data-cursor-hover><?= e($cc) ?></button>
            <?php endforeach; ?>
            <span class="mx-2 hidden h-4 w-px bg-white/15 sm:block"></span>
            <?php foreach ($charFandoms as $fd): ?>
              <button data-char-filter="fandom:<?= e($fd) ?>" class="char-chip rounded-full border px-3.5 py-1.5 text-xs font-medium transition border-white/15 text-white/60 hover:border-white/40" data-cursor-hover><?= e($fd) ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div id="char-grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
          <?php foreach ($characters as $c): $fd = trim(explode('·', $c['fandom_label'])[1] ?? $c['fandom_label']); ?>
            <button data-cursor-hover data-open="character" data-id="<?= (int)$c['character_id'] ?>" data-cat="<?= e($c['category']) ?>" data-fandom="<?= e($fd) ?>"
              class="char-card group relative aspect-[3/4.3] overflow-hidden rounded-2xl text-left noise-border">
              <img src="<?= e(media($c['image'])) ?>" alt="<?= e($c['name']) ?>" loading="lazy" data-part="img"
                class="absolute inset-0 h-full w-full object-cover transition-all duration-700 ease-out" style="transform:scale(1);filter:grayscale(0.55) brightness(0.75)" />
              <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent transition-opacity duration-500"></div>
              <div data-part="glow" class="absolute inset-0 transition-opacity duration-500" style="box-shadow:inset 0 0 90px rgba(139,92,246,0.5);opacity:0"></div>
              <div class="absolute inset-x-0 bottom-0 p-4">
                <span data-part="fandom" class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.2em] text-cyan transition-all duration-500" style="opacity:0;transform:translateY(6px)"><?= e($c['fandom_label']) ?></span>
                <h3 class="font-display text-xl leading-none text-white sm:text-2xl"><?= e($c['name']) ?></h3>
                <p data-part="desc" class="mt-2 overflow-hidden text-xs leading-snug text-white/70 transition-all duration-500" style="max-height:0;opacity:0"><?= e($c['short_desc']) ?></p>
              </div>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </main>

  <?php include __DIR__ . '/includes/partials/chatbot.php'; ?>
  <?php include __DIR__ . '/includes/partials/search_overlay.php'; ?>
  <?php include __DIR__ . '/includes/partials/auth_modal.php'; ?>
  <?php include __DIR__ . '/includes/partials/feedback_modal.php'; ?>
  <div id="modal-root"></div>
  <template id="tpl-icon-circle-check"><?= icon('circle-check', 14) ?></template>
  <template id="tpl-icon-calendar-plus"><?= icon('calendar-plus', 14) ?></template>
  <div id="toast" class="pointer-events-none fixed bottom-6 left-1/2 z-[120] -translate-x-1/2 rounded-full bg-white px-5 py-2.5 text-xs font-semibold text-black opacity-0 shadow-2xl transition-all duration-300 translate-y-4"></div>
</div>
<?php include __DIR__ . '/includes/partials/foot.php'; ?>
</body></html>