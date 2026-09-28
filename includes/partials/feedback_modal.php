<?php /* Dynamic feedback form (SRS: type categorisation bug / suggestion / query). Styled like the auth modal. */
$fu = current_user(); ?>
<div id="feedback-modal" class="fixed inset-0 z-[96] hidden items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/85 backdrop-blur-sm animate-[fade-in_0.3s_ease]" data-close="feedback"></div>
  <div class="relative max-h-[92vh] w-full max-w-md overflow-y-auto rounded-3xl glass p-8 animate-[fade-in_0.4s_cubic-bezier(.22,1,.36,1)]" data-lenis-prevent>
    <button data-close="feedback" class="absolute right-5 top-5 text-white/60 hover:text-white" data-cursor-hover aria-label="Close"><?= icon('x', 18) ?></button>
    <p class="text-xs uppercase tracking-[0.3em] text-cyan">Contact &amp; Feedback</p>
    <h2 class="mt-2 font-display text-4xl text-white">Talk to the team</h2>
    <p class="mt-2 text-sm text-white/50">Report a bug, share a suggestion or ask a query.</p>

    <form id="feedback-form" class="mt-7 space-y-4" novalidate>
      <div class="flex gap-2">
        <?php foreach (['bug' => 'Bug', 'suggestion' => 'Suggestion', 'query' => 'Query'] as $k => $l): ?>
          <button type="button" data-fb-type="<?= $k ?>" class="fb-chip rounded-full border px-3.5 py-1.5 text-xs font-medium transition <?= $k === 'suggestion' ? 'border-white bg-white text-black' : 'border-white/15 text-white/60 hover:border-white/40' ?>" data-cursor-hover><?= $l ?></button>
        <?php endforeach; ?>
      </div>
      <input type="hidden" name="type" value="suggestion">
      <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
        <?= icon('user', 16, 'text-white/40') ?>
        <input name="name" maxlength="100" value="<?= e($fu['name'] ?? '') ?>" placeholder="Your name" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
      </div>
      <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
        <?= icon('mail', 16, 'text-white/40') ?>
        <input name="email" type="email" maxlength="150" value="<?= e($fu['email'] ?? '') ?>" placeholder="Email address" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
      </div>
      <div data-fb-field="bug" class="hidden items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
        <?= icon('circle-alert', 16, 'text-white/40') ?>
        <input name="page" maxlength="120" placeholder="Where did it happen? (page / section)" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
      </div>
      <div data-fb-field="suggestion" class="flex items-center justify-between rounded-xl border border-white/15 bg-white/5 px-4 py-3">
        <span class="text-sm text-white/40">Your rating</span>
        <input type="hidden" name="rating" value="5">
        <span class="flex gap-1">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <button type="button" data-fb-star="<?= $s ?>" class="text-amber" data-cursor-hover aria-label="<?= $s ?> stars"><?= icon('star', 16, '', ['fill' => 'currentColor']) ?></button>
          <?php endfor; ?>
        </span>
      </div>
      <div class="rounded-xl border border-white/15 bg-white/5 px-4 py-3">
        <textarea name="message" rows="4" maxlength="2000" placeholder="Tell us more…" class="w-full resize-none bg-transparent text-sm text-white outline-none placeholder-white/40"></textarea>
      </div>
      <div id="feedback-msg" class="hidden rounded-xl border px-4 py-3 text-xs"></div>
      <button type="submit" class="magnetic-btn mt-2 w-full rounded-full bg-white py-3 text-sm font-semibold uppercase tracking-widest text-black transition hover:bg-violet hover:text-white" data-cursor-hover>Send feedback</button>
    </form>
  </div>
</div>
