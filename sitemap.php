<?php
/** Sitemap (SRS: sitemap linked from the home page) */
require_once __DIR__ . '/includes/functions.php';
$user = current_user();
$theme = $user['pref_theme'] ?? null; $font = $user ? (float)$user['pref_font'] : null;
$pageTitle = 'Sitemap — FAN HUB PLUS';
$tree = [
  'Home' => [['Hero', '#top'], ['Fandom Explorer', '#fandoms'], ['Trending Now', '#explore'], ['Character Showcase', '#characters'],
             ['Watch & Listen', '#media'], ['Featured Articles', '#articles'], ['Upcoming Events + Map', '#events'], ['Merchandise & Upcoming Releases', '#merch']],
  'Fandoms' => array_map(fn($c) => [$c['name'], '#fandoms'], categories()),
  'Overlays & Tools' => [['Advanced Search (Ctrl + K)', '#top'], ['ORA Chatbot', '#top'], ['Login / Register / Forgot password', '#top'], ['Feedback form', '#top']],
  'Registered users' => [['Dashboard — favourites, preferences, bookmarks & notes', '#top'], ['Recent activity', '#top'], ['Event calendar', '#top'], ['Submit fan content', '#top']],
  'Administration' => [['Admin login', 'admin/login.php'], ['Overview', 'admin/'], ['Categories', 'admin/?tab=categories'], ['Content & Media', 'admin/?tab=content'],
                       ['Fan Submissions', 'admin/?tab=submissions'], ['Character Profiles', 'admin/?tab=characters'], ['Merchandise & Releases', 'admin/?tab=merch'],
                       ['Events', 'admin/?tab=events'], ['Users', 'admin/?tab=users'], ['Chatbot Knowledge', 'admin/?tab=chatbot'], ['Feedback', 'admin/?tab=feedback'], ['Analytics', 'admin/?tab=analytics']],
];
include __DIR__ . '/includes/partials/head.php';
?>
<main class="min-h-screen bg-void px-5 py-16 sm:px-8">
  <div class="mx-auto max-w-5xl">
    <nav aria-label="Breadcrumb" class="mb-6 flex items-center gap-2 text-[11px] uppercase tracking-[0.2em] text-white/40">
      <a href="<?= e(url()) ?>" class="hover:text-white">Home</a><span>/</span><span class="text-white/70">Sitemap</span>
    </nav>
    <p class="mb-3 text-xs uppercase tracking-[0.35em] text-cyan">Navigate the universe</p>
    <h1 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">Sitemap</h1>

    <div class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($tree as $group => $links): ?>
        <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
          <h2 class="mb-4 text-xs font-semibold uppercase tracking-[0.25em] text-white/50"><?= e($group) ?></h2>
          <ul class="space-y-2.5 border-l border-white/10 pl-4">
            <?php foreach ($links as [$label, $href]): ?>
              <li><a href="<?= e(strpos($href, '#') === 0 ? url() . $href : url($href)) ?>" class="underline-anim text-sm text-white/70 hover:text-white"><?= e($label) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-16 grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div id="about" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
        <h2 class="font-display text-2xl text-white">About</h2>
        <p class="mt-2 text-sm leading-relaxed text-white/60">Fan Hub Plus brings Anime, Gaming, Movies, TV, K-Pop, Comics, Manga and Cosplay together in one immersive, ad-free hub for fans.</p>
      </div>
      <div id="accessibility" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
        <h2 class="font-display text-2xl text-white">Accessibility</h2>
        <p class="mt-2 text-sm leading-relaxed text-white/60">Use the sun/moon button for dark or light mode and the "T" button to cycle font sizes. Reduced-motion settings are respected, all controls are keyboard reachable and a skip link is provided.</p>
      </div>
      <div id="privacy" class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
        <h2 class="font-display text-2xl text-white">Privacy &amp; <span id="terms">Terms</span></h2>
        <p class="mt-2 text-sm leading-relaxed text-white/60">Passwords are stored as secure hashes and location is only used on your device when you tap "Near me". Merchandise is showcase-only; no payments are processed.</p>
      </div>
    </div>
    <a href="<?= e(url()) ?>" class="magnetic-btn mt-12 inline-block rounded-full bg-white px-8 py-3 text-sm font-semibold uppercase tracking-widest text-black hover:bg-violet hover:text-white">Back to Fan Hub</a>
  </div>
</main>
</body></html>
