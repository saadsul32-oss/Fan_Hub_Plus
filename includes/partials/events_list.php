<?php
/* Timeline list of events. Expects $eventsList (optionally with distance_km) and $savedEvents */
?>
<?php if (!$eventsList): ?>
  <p class="py-10 text-center text-sm text-white/40">No upcoming events for this filter yet.</p>
<?php else: ?>
<div class="relative border-l border-white/10 pl-8 sm:pl-10">
  <?php foreach ($eventsList as $i => $ev): $isAdded = in_array($ev['event_id'], $savedEvents); ?>
    <div class="relative pb-14 last:pb-0" style="animation-delay:<?= $i * 80 ?>ms">
      <span class="absolute -left-[41px] top-1 flex h-5 w-5 items-center justify-center rounded-full border border-violet bg-void sm:-left-[49px]">
        <span class="h-2 w-2 rounded-full bg-violet" style="box-shadow:0 0 10px rgba(139,92,246,0.9)"></span>
      </span>

      <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 transition hover:border-white/25 hover:bg-white/[0.05]">
        <div class="mb-2 flex flex-wrap items-center gap-3">
          <span class="rounded-full bg-violet/15 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide text-violet"><?= e($ev['category']) ?></span>
          <span class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= e(fmt_date($ev['event_date'])) ?></span>
          <?php if (isset($ev['distance_km']) && $ev['distance_km'] !== null): ?>
            <span class="text-[10px] font-semibold uppercase tracking-wide text-cyan"><?= number_format($ev['distance_km']) ?> km away</span>
          <?php endif; ?>
        </div>
        <h3 class="font-display text-2xl text-white sm:text-3xl"><?= e($ev['title']) ?></h3>
        <p class="mt-2 flex items-center gap-1.5 text-sm text-white/50"><?= icon('map-pin', 13) ?> <?= e($ev['venue']) ?><?= $ev['venue'] !== $ev['city'] && $ev['city'] !== 'Online' ? ', ' . e($ev['city']) : '' ?></p>
        <p class="mt-3 max-w-xl text-sm leading-relaxed text-white/70"><?= e($ev['description']) ?></p>
        <div class="flex flex-wrap items-center gap-3">
          <button data-calendar="<?= (int)$ev['event_id'] ?>" data-cursor-hover
            class="magnetic-btn mt-5 inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-widest transition <?= $isAdded ? 'bg-emerald-500/20 text-emerald-300' : 'border border-white/20 text-white hover:border-white/50' ?>">
            <?= $isAdded ? icon('circle-check', 14) : icon('calendar-plus', 14) ?>
            <?= $isAdded ? 'Added to calendar' : 'Add to calendar' ?>
          </button>
          <?php if (!empty($ev['ticket_url'])): ?>
            <a href="<?= e($ev['ticket_url']) ?>" target="_blank" rel="noopener noreferrer" data-cursor-hover
              class="magnetic-btn mt-5 inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white/60 transition hover:text-white">
              <?= icon('ticket', 14) ?> Tickets
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
