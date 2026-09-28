<?php
/* Footer (converted from Footer.tsx) — doubles as the on-page SITEMAP required by the SRS */
$footerCols = [
  ['title' => 'Explore',  'links' => array_map(fn($c) => [$c['name'], url('index.php') . '#fandoms', 'data-search-category="' . e($c['name']) . '"'], $cats)],
  ['title' => 'Platform', 'links' => [
      ['Trending', url('index.php') . '#explore', ''], ['Characters', url('characters.php'), ''], ['Watch & Listen', url('media.php'), ''],
      ['Articles', url('articles.php'), ''], ['Events', url('events.php'), ''], ['Merchandise', url('merch.php'), ''],
      ['Movies', url('movies.php'), ''], ['TV Shows', url('tvshows.php'), '']]],
  ['title' => 'Company',  'links' => [
      ['About', url('sitemap.php#about'), ''], ['Sitemap', url('sitemap.php'), ''], ['Feedback', '#top', 'data-action="feedback"'],
      ['Contact', '#top', 'data-action="feedback"'], ['Accessibility', url('sitemap.php#accessibility'), '']]],
];
/* Icon names limited to what's in includes/icon_set.php — no dedicated brand glyphs available, so the
   closest fitting icon stands in for each platform (the link title still says the real name on hover). */
$socials = array_filter([
  'twitter'   => ['setting' => setting('social_twitter'),   'icon' => 'hash',           'label' => 'Twitter / X'],
  'instagram' => ['setting' => setting('social_instagram'), 'icon' => 'image',          'label' => 'Instagram'],
  'youtube'   => ['setting' => setting('social_youtube'),   'icon' => 'clapperboard',   'label' => 'YouTube'],
  'discord'   => ['setting' => setting('social_discord'),   'icon' => 'message-circle', 'label' => 'Discord'],
  'tiktok'    => ['setting' => setting('social_tiktok'),    'icon' => 'music-2',        'label' => 'TikTok'],
], fn($s) => $s['setting'] !== '');
?>
<footer class="relative border-t border-white/10 bg-void px-5 py-16 sm:px-8">
  <div class="mx-auto max-w-7xl">
    <div class="grid grid-cols-2 gap-10 sm:grid-cols-4">
      <div class="col-span-2 sm:col-span-1">
        <p class="text-2xl"><img src="<?= e(url('images/brand/logo-horizontal.png')) ?>" alt="FAN HUB PLUS" class="brand-logo is-dark" width="1020" height="160" /><img src="<?= e(url('images/brand/logo-horizontal-light.png')) ?>" alt="FAN HUB PLUS" class="brand-logo is-light" width="1020" height="160" /></p>
        <p class="mt-3 max-w-[220px] text-sm text-white/50"><?= e(setting('footer_tagline', 'A premium digital universe built for fandom culture.')) ?></p>
        <?php if ($socials): ?>
          <div class="mt-5 flex flex-wrap gap-2">
            <?php foreach ($socials as $s): ?>
              <a href="<?= e($s['setting']) ?>" target="_blank" rel="noopener" title="<?= e($s['label']) ?>"
                 class="flex h-8 w-8 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/60 transition hover:border-white/30 hover:text-white">
                <?= icon($s['icon'], 14) ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <?php foreach ($footerCols as $col): ?>
        <div>
          <h5 class="mb-4 text-xs font-semibold uppercase tracking-[0.25em] text-white/40"><?= e($col['title']) ?></h5>
          <ul class="space-y-2.5">
            <?php foreach ($col['links'] as [$label, $href, $attr]): ?>
              <li><a href="<?= e($href) ?>" <?= $attr ?> class="underline-anim text-sm text-white/65 hover:text-white"><?= e($label) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="mt-14 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 text-xs text-white/40 sm:flex-row">
      <p>© <?= date('Y') ?> <?= e(site_name()) ?>. All rights reserved. A demo fandom platform.</p>
      <div class="flex gap-5">
        <a href="<?= e(url('sitemap.php#privacy')) ?>" class="hover:text-white">Privacy</a>
        <a href="<?= e(url('sitemap.php#terms')) ?>" class="hover:text-white">Terms</a>
        <a href="<?= e(url('sitemap.php#accessibility')) ?>" class="hover:text-white">Accessibility</a>
        <a href="<?= e(url('sitemap.php')) ?>" class="hover:text-white">Sitemap</a>
      </div>
    </div>
  </div>
</footer>
