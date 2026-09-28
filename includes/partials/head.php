<?php
/** Shared <head>. Expects optional $theme / $font / $pageTitle */
$pageTitle = $pageTitle ?? setting('seo_title', 'FAN HUB PLUS — Enter the Fandom Universe');
$metaDesc = setting('seo_description', 'FAN HUB PLUS — Enter the Fandom Universe. A cinematic, immersive platform for Anime, Gaming, Movies, TV, K-Pop, Comics, Manga and Cosplay.');
$cssVer = @filemtime(APP_ROOT . '/assets/css/app.css') ?: 1;
?>
<!doctype html>
<html lang="en"<?= (isset($theme) && $theme === 'light') ? ' class="light"' : '' ?>>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="<?= e($metaDesc) ?>" />
  <meta property="og:title" content="<?= e($pageTitle) ?>" />
  <meta property="og:description" content="<?= e($metaDesc) ?>" />
  <meta property="og:type" content="website" />
  <?php if (setting('hero_image') !== ''): ?><meta property="og:image" content="<?= e(media(setting('hero_image'))) ?>" /><?php endif; ?>
  <meta name="theme-color" content="#05050a" />
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Manrope:wght@200;300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>?v=<?= $cssVer ?>" />
  <link rel="icon" type="image/png" sizes="64x64" href="<?= e(url('images/brand/favicon-64.png')) ?>" />
  <link rel="icon" type="image/png" sizes="32x32" href="<?= e(url('images/brand/favicon-32.png')) ?>" />
  <link rel="apple-touch-icon" href="<?= e(url('images/brand/apple-touch-icon.png')) ?>" />
  <style>
    .brand-logo { display: block; height: 1.5em; width: auto; max-width: none; }
    .brand-logo.is-light, html.light .brand-logo.is-dark { display: none; }
    html.light .brand-logo.is-light { display: block; }
  </style>
  <title><?= e($pageTitle) ?></title>
  <?= $extraHead ?? '' ?>
  <script>
    /* Apply saved accessibility prefs before first paint (visitor: localStorage, member: database) */
    window.FH = {
      base: <?= json_encode(BASE_URL) ?>,
      authed: <?= is_logged_in() ? 'true' : 'false' ?>,
      serverTheme: <?= json_encode($theme ?? null) ?>,
      serverFont: <?= json_encode(isset($font) ? (float)$font : null) ?>
    };
    (function () {
      try {
        var t = FH.serverTheme || localStorage.getItem('fh_theme');
        var f = FH.serverFont || parseFloat(localStorage.getItem('fh_font') || '1');
        if (t === 'light') document.documentElement.classList.add('light');
        else document.documentElement.classList.remove('light');
        if (f && f !== 1) document.documentElement.style.fontSize = (f * 100) + '%';
      } catch (e) {}
    })();
    /* Font size control */
    (function () {
      try {
        var adjustFont = function (delta) {
          var html = document.documentElement;
          var current = parseFloat(getComputedStyle(html).fontSize) || 1;
          var newSize = Math.max(0.75, Math.min(1.5, current + delta));
          html.style.fontSize = newSize + '%';
          try { localStorage.setItem('fh_font', newSize.toString()); } catch (e) {}
          window.FH.serverFont = newSize;
        };
        var fontBtn = document.querySelector('[data-action="font"]');
        if (fontBtn) {
          fontBtn.addEventListener('click', function (e) {
            e.preventDefault();
            var current = parseFloat(getComputedStyle(document.documentElement).fontSize) || 1;
            // Toggle between default (100%) and larger (125%)
            var newSize = current === 1 ? 1.25 : 1;
            adjustFont(newSize - current);
          });
        }
      } catch (e) {}
    })();
  </script>
</head>
<body>
