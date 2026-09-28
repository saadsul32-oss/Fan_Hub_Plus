<?php /* Auth modal (converted from AuthModal.tsx) — real login / signup / forgot-password against the users table */ ?>
<div id="auth-modal" class="fixed inset-0 z-[95] hidden items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/85 backdrop-blur-sm animate-[fade-in_0.3s_ease]" data-close="auth"></div>
  <div class="relative w-full max-w-md overflow-hidden rounded-3xl glass p-8 animate-[fade-in_0.4s_cubic-bezier(.22,1,.36,1)]">
    <button data-close="auth" class="absolute right-5 top-5 text-white/60 hover:text-white" data-cursor-hover aria-label="Close"><?= icon('x', 18) ?></button>

    <p class="text-xs uppercase tracking-[0.3em] text-cyan">Fandom Universe Access</p>
    <h2 id="auth-title" class="mt-2 font-display text-4xl text-white">Welcome back</h2>
    <p id="auth-sub" class="mt-2 text-sm text-white/50">Sign in to access your Fandom Command Center.</p>

    <form id="auth-form" class="mt-7 space-y-4" novalidate>
      <input type="hidden" name="mode" value="login">
      <div data-field="name" class="hidden items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
        <?= icon('user', 16, 'text-white/40') ?>
        <input name="name" maxlength="100" placeholder="Full name" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
      </div>
      <div data-field="email" class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
        <?= icon('mail', 16, 'text-white/40') ?>
        <input name="email" type="email" maxlength="150" placeholder="Email address" autocomplete="email" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
      </div>
      <div data-field="password" class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
        <?= icon('lock', 16, 'text-white/40') ?>
        <input name="password" type="password" placeholder="Password" autocomplete="current-password" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
      </div>
      <div class="flex justify-end" data-field="forgot-link">
        <button type="button" data-auth-mode="forgot" class="text-xs text-white/50 hover:text-white" data-cursor-hover>Forgot password?</button>
      </div>

      <div id="auth-msg" class="hidden rounded-xl border px-4 py-3 text-xs leading-relaxed"></div>

      <button type="submit" id="auth-submit" class="magnetic-btn mt-2 w-full rounded-full bg-white py-3 text-sm font-semibold uppercase tracking-widest text-black transition hover:bg-violet hover:text-white" data-cursor-hover>
        Enter Universe
      </button>
    </form>

    <p class="mt-5 text-center text-xs text-white/50">
      <span id="auth-switch-text">New here?</span>
      <button id="auth-switch" type="button" data-auth-mode="signup" class="font-semibold text-white underline-offset-2 hover:underline" data-cursor-hover>Create an account</button>
    </p>
  </div>
</div>
