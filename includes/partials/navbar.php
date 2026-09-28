<?php
/* Navbar (converted from Navbar.tsx) */
$navItems = [
  ['Explore', url('index.php') . '#explore'],
  ['Fandoms', url('index.php') . '#fandoms'],
  ['Characters', url('characters.php')],
  ['Movies', url('movies.php')],
  ['TV Shows', url('tvshows.php')],
  ['Articles', url('articles.php')],
  ['Watch & Listen', url('media.php')],
  ['Events', url('events.php')],
  ['Merchandise', url('merch.php')],
  ['Releases', url('merch.php') . '#releases'],
];
$authed = is_logged_in();
$announceOn = setting('announcement_enabled', '0') === '1' && trim(setting('announcement_text')) !== '';
?>
<?php if ($announceOn): ?>
  <div id="site-announcement" class="fixed inset-x-0 top-0 z-[60] flex items-center justify-center gap-2 bg-gradient-to-r from-violet via-magenta to-violet px-4 py-2 text-center text-[11px] font-semibold uppercase tracking-wide text-white sm:text-xs">
    <?php if (trim(setting('announcement_link')) !== ''): ?>
      <a href="<?= e(setting('announcement_link')) ?>" class="hover:underline"><?= e(setting('announcement_text')) ?></a>
    <?php else: ?>
      <span><?= e(setting('announcement_text')) ?></span>
    <?php endif; ?>
  </div>
  <script>document.addEventListener('DOMContentLoaded', function () {
    var b = document.getElementById('site-announcement'), h = document.getElementById('site-header');
    if (b && h) h.style.top = b.offsetHeight + 'px';
  });</script>
<?php endif; ?>
<header id="site-header" class="fixed top-0 left-0 right-0 z-50 flex justify-center transition-all duration-500 pt-5">
  <nav id="site-nav" class="flex w-[94%] max-w-7xl items-center justify-between rounded-2xl px-4 sm:px-6 transition-all duration-500 h-20 bg-transparent">
    <a data-go href="<?= e(url('index.php') . '#top') ?>" id="nav-logo" class="font-display tracking-widest text-white transition-all duration-500 text-2xl" data-cursor-hover>
      <img src="<?= e(url('images/brand/logo-horizontal.png')) ?>" alt="FAN HUB PLUS" class="brand-logo is-dark" width="1020" height="160" />
      <img src="<?= e(url('images/brand/logo-horizontal-light.png')) ?>" alt="FAN HUB PLUS" class="brand-logo is-light" width="1020" height="160" />
    </a>

    <div class="flex items-center gap-2">
      <button aria-label="Adjust font size" data-action="font" class="h-9 w-9 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white" data-cursor-hover title="Adjust font size">
        <?= icon('type', 14) ?>
      </button>
      <button aria-label="Toggle theme" data-action="theme" class="hidden h-9 w-9 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white sm:flex" data-cursor-hover title="Toggle theme">
        <span class="theme-icon-dark"><?= icon('sun', 16) ?></span><span class="theme-icon-light"><?= icon('moon', 16) ?></span>
      </button>
      <button aria-label="Search" data-action="search" class="flex h-9 w-9 items-center justify-center rounded-full text-white/80 transition hover:bg-white/10 hover:text-white" data-cursor-hover>
        <?= icon('search', 17) ?>
      </button>
      <?php if ($authed): ?>
        <button aria-label="Bookmarks" data-action="dashboard" data-section="dash-bookmarks" class="hidden h-9 w-9 items-center justify-center rounded-full text-white/70 transition hover:bg-white/10 hover:text-white sm:flex" data-cursor-hover title="Bookmarks">
          <?= icon('bookmark', 15) ?>
        </button>
      <?php endif; ?>
      <button data-action="<?= $authed ? 'dashboard' : 'auth' ?>" class="magnetic-btn hidden items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-[12px] font-semibold uppercase tracking-wide text-white hover:border-violet/60 hover:bg-violet/10 sm:flex" data-cursor-hover>
        <?= icon('user', 14) ?>
        <?= $authed ? 'Dashboard' : 'Login' ?>
      </button>
      <button id="menu-toggle" class="flex h-9 w-9 items-center justify-center rounded-full text-white" aria-label="Menu">
        <span class="menu-icon-open"><?= icon('menu', 20) ?></span><span class="menu-icon-close hidden"><?= icon('x', 20) ?></span>
      </button>
    </div>
  </nav>
</header>

<div id="mobile-menu" class="fixed inset-0 z-40 flex flex-col justify-center bg-void/98 backdrop-blur-xl transition-all duration-500 pointer-events-none opacity-0">
  <div class="flex flex-col items-center gap-6">
    <?php foreach ($navItems as $i => [$label, $href]): ?>
      <a data-go href="<?= e($href) ?>" data-i="<?= $i ?>" class="mobile-link font-display text-3xl tracking-wide text-white/90 transition-all duration-500" style="transition-delay:0ms;opacity:0;transform:translateY(16px)"><?= e($label) ?></a>
    <?php endforeach; ?>
    <?php if ($authed): ?>
      <button data-action="dashboard" data-section="dash-bookmarks" data-i="<?= count($navItems) ?>" class="mobile-link font-display text-2xl tracking-wide text-white/70 transition-all duration-500" style="transition-delay:0ms;opacity:0;transform:translateY(16px)">Bookmarks</button>
    <?php endif; ?>
    <button data-action="<?= $authed ? 'dashboard' : 'auth' ?>" class="mt-4 rounded-full border border-white/20 px-6 py-3 text-sm uppercase tracking-widest text-white">
      <?= $authed ? 'Dashboard' : 'Login' ?>
    </button>
  </div>
</div>
