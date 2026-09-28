/*!
 * Fan Hub Plus — front-end behaviour
 * Vanilla-JS port of the original React components (state, effects, animations).
 * All data comes from the PHP/MySQL backend (server-rendered HTML + JSON APIs).
 */
(function () {
  'use strict';

  /* ================================================================ *
   *  Utilities
   * ================================================================ */
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var BASE = (window.FH && FH.base) || '/';
  var CSRF = ($('meta[name="csrf-token"]') || {}).content || '';
  var AUTHED = !!(window.FH && FH.authed);
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var isTouch = window.matchMedia('(pointer: coarse)').matches;
  var lenis = null;

  function cls(el, add, remove) {
    if (!el) return;
    if (remove) remove.split(' ').forEach(function (c) { if (c) el.classList.remove(c); });
    if (add) add.split(' ').forEach(function (c) { if (c) el.classList.add(c); });
  }

  function post(path, data) {
    var fd = data instanceof FormData ? data : new FormData();
    if (!(data instanceof FormData) && data) Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(BASE + path, { method: 'POST', headers: { 'X-CSRF-Token': CSRF }, body: fd, credentials: 'same-origin' })
      .then(function (r) {
        return r.json().catch(function () { return { ok: false, error: 'Unexpected server response.' }; }).then(function (j) {
          if (r.status === 401 && j.auth) { openAuth('login'); toast(j.error || 'Please sign in first.'); }
          return j;
        });
      })
      .catch(function () { return { ok: false, error: 'Network error — please try again.' }; });
  }

  function getHTML(path) {
    return fetch(BASE + path, { credentials: 'same-origin' }).then(function (r) { return r.ok ? r.text() : Promise.reject(r.status); });
  }

  var toastTimer;
  function toast(msg) {
    var t = $('#toast');
    if (!t || !msg) return;
    t.textContent = msg;
    cls(t, 'opacity-100 translate-y-0', 'opacity-0 translate-y-4');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { cls(t, 'opacity-0 translate-y-4', 'opacity-100 translate-y-0'); }, 2600);
  }

  function requireAuth(msg) {
    if (AUTHED) return true;
    openAuth('login');
    toast(msg || 'Sign in to use this feature.');
    return false;
  }

  function tplIcon(name) {
    var t = document.getElementById('tpl-icon-' + name);
    return t ? t.innerHTML : '';
  }

  /* Scroll locking shared by all overlays */
  var lockCount = 0;
  function lockScroll() { lockCount++; document.body.style.overflow = 'hidden'; if (lenis) lenis.stop(); }
  function unlockScroll() { lockCount = Math.max(0, lockCount - 1); if (!lockCount) { document.body.style.overflow = ''; if (lenis) lenis.start(); } }

  function scrollToTarget(sel) {
    var el = sel === '#top' ? document.getElementById('top') : $(sel);
    if (!el) return;
    if (lenis) lenis.scrollTo(el, { offset: 0 });
    else el.scrollIntoView({ behavior: 'smooth' });
  }

  /* ================================================================ *
   *  Accessibility: theme + font size (localStorage + DB for members)
   * ================================================================ */
  var FONT_STEPS = [1, 1.075, 1.15, 1.25, 0.875];
  function currentFont() {
    var f = parseFloat(document.documentElement.style.fontSize) / 100;
    return isNaN(f) ? 1 : f;
  }
  function applyFont(f) {
    document.documentElement.style.fontSize = (f * 100) + '%';
    try { localStorage.setItem('fh_font', String(f)); } catch (e) {}
  }
  function applyTheme(t) {
    document.documentElement.classList.toggle('light', t === 'light');
    try { localStorage.setItem('fh_theme', t); } catch (e) {}
    if (window.__fhMap) window.__fhMap.setTheme(t);
  }
  function savePrefsRemote() {
    if (!AUTHED) return;
    post('api/profile.php', { action: 'theme', theme: document.documentElement.classList.contains('light') ? 'light' : 'dark', font: currentFont() });
  }
  function toggleTheme() {
    applyTheme(document.documentElement.classList.contains('light') ? 'dark' : 'light');
    savePrefsRemote();
  }
  function cycleFont() {
    var f = currentFont();
    var idx = 0;
    FONT_STEPS.forEach(function (s, i) { if (Math.abs(s - f) < 0.01) idx = i; });
    var next = FONT_STEPS[(idx + 1) % FONT_STEPS.length];
    applyFont(next);
    toast('Font size ' + Math.round(next * 100) + '%');
    savePrefsRemote();
  }

  /* ================================================================ *
   *  Loader (Loader.tsx) + Lenis smooth scroll (useLenis.ts)
   * ================================================================ */
  function startLenis() {
    if (reducedMotion || typeof window.Lenis !== 'function') return;
    lenis = new window.Lenis({
      duration: 1.1,
      easing: function (t) { return Math.min(1, 1.001 - Math.pow(2, -10 * t)); },
      smoothWheel: true,
      touchMultiplier: 1.2
    });
    (function raf(time) { lenis.raf(time); requestAnimationFrame(raf); })(0);
    if (lockCount) lenis.stop();
  }

  function revealMain() {
    var main = $('#main-content');
    if (main) cls(main, 'opacity-100', 'opacity-0');
    startLenis();
  }

  function initLoader() {
    var loader = $('#loader');
    if (!loader || window.FH_SKIP_LOADER) { revealMain(); return; }
    var stars = $$('.loader-star', loader);
    var chars = $$('.loader-char', loader);
    setTimeout(function () {
      stars.forEach(function (s) { s.style.opacity = s.dataset.o; s.style.transform = 'scale(1)'; });
      chars.forEach(function (c) { c.style.opacity = 1; c.style.transform = 'translateY(0%) rotateX(0deg)'; });
    }, 150);
    setTimeout(function () {
      chars.forEach(function (c) { c.style.textShadow = '0 0 60px rgba(139,92,246,0.7), 0 0 120px rgba(236,72,153,0.4)'; });
    }, 1500);
    setTimeout(function () {
      cls($('#loader-line'), 'w-64 opacity-100', 'w-0 opacity-0');
      cls($('#loader-text'), 'opacity-100 translate-y-0', 'opacity-0 translate-y-2');
    }, 2200);
    setTimeout(function () { cls(loader, 'opacity-0 pointer-events-none', 'opacity-100'); }, 2900);
    setTimeout(function () {
      loader.remove();
      try { sessionStorage.setItem('fh_loaded', '1'); } catch (e) {}
      revealMain();
    }, 3400);
  }

  /* ================================================================ *
   *  Custom cursor (CustomCursor.tsx)
   * ================================================================ */
  function initCursor() {
    var dot = $('#cursor-dot'), ring = $('#cursor-ring');
    if (!dot || !ring || isTouch) return;
    dot.classList.remove('hidden'); ring.classList.remove('hidden');
    var mx = 0, my = 0, rx = 0, ry = 0, active = false;
    window.addEventListener('mousemove', function (e) {
      mx = e.clientX; my = e.clientY;
      dot.style.transform = 'translate3d(' + mx + 'px,' + my + 'px,0) translate(-50%,-50%)';
      var a = !!(e.target.closest && e.target.closest('a, button, [data-cursor-hover]'));
      if (a !== active) {
        active = a;
        if (a) cls(ring, 'h-12 w-12 border-violet/70 bg-violet/10', 'h-7 w-7 border-white/40 bg-transparent');
        else cls(ring, 'h-7 w-7 border-white/40 bg-transparent', 'h-12 w-12 border-violet/70 bg-violet/10');
      }
    });
    (function tick() {
      rx += (mx - rx) * 0.18; ry += (my - ry) * 0.18;
      ring.style.transform = 'translate3d(' + rx + 'px,' + ry + 'px,0) translate(-50%,-50%)';
      requestAnimationFrame(tick);
    })();
  }

  /* ================================================================ *
   *  Scroll-driven: progress bar, navbar, hero parallax
   * ================================================================ */
  function initScrollEffects() {
    var bar = $('#scroll-progress');
    var header = $('#site-header'), nav = $('#site-nav'), logo = $('#nav-logo');
    var hero = $('#hero-content');
    var scrolled = null;
    function onScroll() {
      var h = document.documentElement;
      var top = h.scrollTop || document.body.scrollTop;
      var max = (h.scrollHeight || document.body.scrollHeight) - h.clientHeight;
      if (bar) bar.style.width = (max > 0 ? (top / max) * 100 : 0) + '%';
      var s = window.scrollY > 40;
      if (s !== scrolled && nav) {
        scrolled = s;
        if (s) {
          cls(header, 'pt-2', 'pt-5');
          cls(nav, 'h-14 glass shadow-[0_10px_40px_-15px_rgba(0,0,0,0.6)]', 'h-20 bg-transparent');
          cls(logo, 'text-lg', 'text-2xl');
        } else {
          cls(header, 'pt-5', 'pt-2');
          cls(nav, 'h-20 bg-transparent', 'h-14 glass shadow-[0_10px_40px_-15px_rgba(0,0,0,0.6)]');
          cls(logo, 'text-2xl', 'text-lg');
        }
      }
      if (hero) {
        var y = window.scrollY;
        hero.style.opacity = String(Math.max(0, 1 - y / 700));
        hero.style.transform = 'translateY(' + (y * 0.25) + 'px)';
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ================================================================ *
   *  Navbar mobile menu
   * ================================================================ */
  var menuOpen = false;
  function setMenu(open) {
    menuOpen = open;
    var m = $('#mobile-menu');
    if (!m) return;
    if (open) cls(m, 'opacity-100 pointer-events-auto', 'pointer-events-none opacity-0');
    else cls(m, 'pointer-events-none opacity-0', 'opacity-100 pointer-events-auto');
    $$('.mobile-link', m).forEach(function (b) {
      var i = +b.dataset.i;
      b.style.transitionDelay = open ? (i * 60) + 'ms' : '0ms';
      b.style.opacity = open ? 1 : 0;
      b.style.transform = open ? 'translateY(0)' : 'translateY(16px)';
    });
    $$('.menu-icon-open').forEach(function (x) { x.classList.toggle('hidden', open); });
    $$('.menu-icon-close').forEach(function (x) { x.classList.toggle('hidden', !open); });
  }

  /* ================================================================ *
   *  Hero (Hero.tsx)
   * ================================================================ */
  function initHero() {
    setTimeout(function () {
      $$('[data-ready-on]').forEach(function (el) { cls(el, el.dataset.readyOn, el.dataset.readyOff); });
      $$('.hero-star').forEach(function (s) { s.style.opacity = s.dataset.o; });
    }, 60);

    var WORDS = ['ANIME', 'GAMING', 'MOVIES', 'TV', 'K-POP', 'COMICS', 'MANGA', 'COSPLAY'];
    var idx = 0, word = $('#hero-word');
    if (word) setInterval(function () {
      idx = (idx + 1) % WORDS.length;
      var n = word.cloneNode(false);
      n.textContent = WORDS[idx];
      word.parentNode.replaceChild(n, word);
      word = n;
    }, 1400);

    if (isTouch) return;
    var a = $('#hero-layer-a'), b = $('#hero-layer-b'), c = $('#hero-layer-c');
    window.addEventListener('mousemove', function (e) {
      var x = (e.clientX / window.innerWidth - 0.5) * 2;
      var y = (e.clientY / window.innerHeight - 0.5) * 2;
      if (a) a.style.transform = 'translate3d(' + (x * 18) + 'px,' + (y * 12) + 'px,0)';
      if (b) b.style.transform = 'translate3d(' + (x * -28) + 'px,' + (y * -18) + 'px,0)';
      if (c) c.style.transform = 'translate3d(' + (x * 10) + 'px,' + (y * 8) + 'px,0)';
    });
  }

  /* ================================================================ *
   *  Fandom Explorer panels (FandomExplorer.tsx)
   * ================================================================ */
  function initFandomPanels() {
    var panels = $$('.fandom-panel');
    function setActive(i) {
      panels.forEach(function (p, j) {
        var on = i === j, glow = p.dataset.glow;
        p.style.flex = on ? 6 : 1;
        var img = $('[data-part="img"]', p);
        img.style.transform = on ? 'scale(1.06)' : 'scale(1.25)';
        img.style.filter = on ? 'saturate(1.1)' : 'saturate(0.4) brightness(0.55)';
        $('[data-part="shade"]', p).style.background = on
          ? 'linear-gradient(0deg, rgba(5,5,8,0.95) 0%, rgba(5,5,8,0.3) 55%, rgba(5,5,8,0.15) 100%)'
          : 'linear-gradient(0deg, rgba(5,5,8,0.9) 0%, rgba(5,5,8,0.55) 100%)';
        var g = $('[data-part="glow"]', p);
        g.style.boxShadow = on ? 'inset 0 0 120px ' + glow : 'none';
        g.style.opacity = on ? 1 : 0;
        var col = $('[data-part="collapsed"]', p);
        if (on) cls(col, 'opacity-0', 'opacity-100'); else cls(col, 'opacity-100', 'opacity-0');
        var ex = $('[data-part="expanded"]', p);
        if (on) cls(ex, 'opacity-100 translate-y-0 delay-150', 'pointer-events-none opacity-0 translate-y-6');
        else cls(ex, 'pointer-events-none opacity-0 translate-y-6', 'opacity-100 translate-y-0 delay-150');
      });
    }
    panels.forEach(function (p, i) {
      p.addEventListener('mouseenter', function () { setActive(i); });
      p.addEventListener('focus', function () { setActive(i); });
    });
  }

  /* ================================================================ *
   *  Trending rail (Trending.tsx)
   * ================================================================ */
  function initTrending() {
    var cards = $$('.trending-card');
    function setHover(active) {
      cards.forEach(function (c) {
        var on = c === active, dim = active && !on;
        c.style.width = on ? '380px' : '300px';
        c.style.opacity = dim ? 0.55 : 1;
        c.style.transform = dim ? 'scale(0.97)' : 'scale(1)';
        $('[data-part="img"]', c).style.transform = on ? 'scale(1.1)' : 'scale(1)';
        var pv = $('[data-part="preview"]', c);
        pv.style.maxHeight = on ? '40px' : '0';
        pv.style.opacity = on ? 1 : 0;
      });
    }
    cards.forEach(function (c) {
      c.addEventListener('mouseenter', function () { setHover(c); });
      c.addEventListener('mouseleave', function () { setHover(null); });
    });
    $$('[data-scroll-trending]').forEach(function (b) {
      b.addEventListener('click', function () {
        var s = $('#trending-scroller');
        if (s) s.scrollBy({ left: +b.dataset.scrollTrending * 420, behavior: 'smooth' });
      });
    });
  }

  /* ================================================================ *
   *  Character spotlight — 3D coverflow (drag / swipe / keys / autoplay)
   * ================================================================ */
  function initSpotlight() {
    var root = $('#char-spotlight');
    if (!root) return;
    var stage = $('#cf-stage', root);
    var cards = $$('[data-cf-card]', root), dots = $$('[data-cf-dot]', root), infos = $$('[data-cf-info]', root);
    var bar = $('#cf-progress');
    var n = cards.length;
    if (!n) return;
    var active = 0, dragX = null, dragDX = 0, dragging = false, hovering = false, visible = true;
    var AUTOPLAY = 6000, elapsed = 0, lastT = performance.now();

    function offsetOf(i) {                 // shortest circular distance from the active card
      var d = i - active;
      if (n > 2) { if (d > n / 2) d -= n; if (d < -n / 2) d += n; }
      return d;
    }
    function layout(dragPx) {
      var w = stage.clientWidth;
      var cardW = cards[0].offsetWidth || 240;
      var spread = Math.min(cardW * 0.62, Math.max(90, (w - cardW) / 2.4));  // keeps side cards inside the stage
      var shift = (dragPx || 0) / Math.max(1, spread);                          // drag moves the whole deck
      cards.forEach(function (c, i) {
        var o = offsetOf(i) + shift, a = Math.abs(o);
        var hidden = a > 2.6;
        c.style.transform = 'translate(-50%,-50%) translateX(' + (o * spread) + 'px) translateZ(' + (-a * 140) + 'px) rotateY(' + (Math.max(-1, Math.min(1, o)) * -32) + 'deg) scale(' + (1 - Math.min(a, 2) * 0.08) + ')';
        c.style.opacity = hidden ? 0 : (a < 0.5 ? 1 : Math.max(0.25, 0.85 - a * 0.25));
        c.style.zIndex = String(100 - Math.round(a * 10));
        c.style.pointerEvents = hidden ? 'none' : '';
        c.tabIndex = i === active ? 0 : -1;
        c.setAttribute('aria-current', i === active ? 'true' : 'false');
        var img = $('[data-part="img"]', c);
        img.style.filter = a < 0.5 ? 'none' : 'grayscale(0.6) brightness(0.55)';
        $('[data-part="glow"]', c).style.opacity = a < 0.5 ? 1 : 0;
      });
    }
    function render() {
      layout(0);
      dots.forEach(function (d, i) {
        var on = i === active;
        d.style.width = (on ? 28 : 8) + 'px';
        d.style.background = on ? 'linear-gradient(90deg, var(--color-violet), var(--color-cyan))' : 'rgba(255,255,255,0.2)';
        d.setAttribute('aria-current', on ? 'true' : 'false');
      });
      infos.forEach(function (el, i) {
        var on = i === active;
        el.setAttribute('aria-hidden', on ? 'false' : 'true');
        if (on) cls(el, 'relative opacity-100 translate-y-0', 'pointer-events-none absolute inset-x-0 top-0 opacity-0 translate-y-4');
        else cls(el, 'pointer-events-none absolute inset-x-0 top-0 opacity-0 translate-y-4', 'relative opacity-100 translate-y-0');
      });
    }
    function goTo(i) { active = ((i % n) + n) % n; elapsed = 0; render(); }
    function next() { goTo(active + 1); }
    function prev() { goTo(active - 1); }

    // autoplay with progress bar — pauses on hover, drag, focus inside, hidden tab or off-screen
    function tick(t) {
      var dt = t - lastT; lastT = t;
      var paused = reducedMotion || n < 2 || hovering || dragging || !visible || document.hidden || modalStack.length || root.contains(document.activeElement);
      if (!paused) {
        elapsed += dt;
        if (elapsed >= AUTOPLAY) next();
      }
      if (bar) bar.style.width = (n < 2 || reducedMotion ? 0 : Math.min(100, elapsed / AUTOPLAY * 100)) + '%';
      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
    if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (en) { visible = en[0].isIntersecting; }, { threshold: 0.2 }).observe(root);
    }
    root.addEventListener('mouseenter', function () { hovering = true; });
    root.addEventListener('mouseleave', function () { hovering = false; });

    // pointer drag / swipe
    stage.addEventListener('pointerdown', function (e) {
      if (e.button !== undefined && e.button !== 0) return;
      dragX = e.clientX; dragDX = 0; dragging = true;
      cards.forEach(function (c) { c.style.transition = 'none'; });
    });
    window.addEventListener('pointermove', function (e) {
      if (!dragging) return;
      dragDX = e.clientX - dragX;
      if (Math.abs(dragDX) > 4) layout(dragDX);
    });
    function endDrag() {
      if (!dragging) return;
      dragging = false;
      cards.forEach(function (c) { c.style.transition = ''; });
      var w = cards[0].offsetWidth || 240;
      if (Math.abs(dragDX) > w * 0.18) {
        var steps = Math.max(1, Math.round(Math.abs(dragDX) / (w * 0.6)));
        goTo(active + (dragDX < 0 ? steps : -steps));
      } else render();
      setTimeout(function () { dragDX = 0; }, 0);
    }
    window.addEventListener('pointerup', endDrag);
    window.addEventListener('pointercancel', endDrag);

    cards.forEach(function (c, i) {
      c.addEventListener('click', function (e) {
        e.stopPropagation();
        if (Math.abs(dragDX) > 6) return;            // it was a drag, not a click
        if (i === active) openFragment('character', c.dataset.id); else goTo(i);
      });
    });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { goTo(i); }); });
    var pb = $('[data-cf-prev]', root), nb = $('[data-cf-next]', root);
    if (pb) pb.addEventListener('click', prev);
    if (nb) nb.addEventListener('click', next);
    stage.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); next(); }
      else if (e.key === 'ArrowLeft') { e.preventDefault(); prev(); }
      else if (e.key === 'Enter' && e.target === stage) { e.preventDefault(); openFragment('character', cards[active].dataset.id); }
    });
    window.addEventListener('resize', function () { layout(0); });
    render();
  }

  /* ================================================================ *
   *  Character browser: search + category + fandom + sort
   * ================================================================ */
  function initCharacterGrid() {
    var grid = $('#char-grid');
    if (!grid) return;
    var cards = $$('.char-card', grid);
    var state = { q: '', cat: '', fandom: '', sort: 'featured' };
    var input = $('#char-search'), fsel = $('#char-fandom'), ssel = $('#char-sort');
    var ON = 'border-white bg-white text-black', OFF = 'border-white/15 text-white/60 hover:border-white/40';
    var timer;

    function apply() {
      var q = state.q.trim().toLowerCase(), shown = 0;
      cards.forEach(function (c) {
        var ok = (!state.cat || c.dataset.cat === state.cat) &&
                 (!state.fandom || c.dataset.fandom === state.fandom) &&
                 (!q || q.split(/\s+/).every(function (w) { return c.dataset.search.indexOf(w) > -1; }));
        c.style.display = ok ? '' : 'none';
        if (ok) shown++;
      });
      var sorted = cards.slice().sort(function (a, b) {
        if (state.sort === 'az') return a.dataset.name.localeCompare(b.dataset.name);
        if (state.sort === 'popular') return (+b.dataset.views) - (+a.dataset.views);
        return (+a.dataset.order) - (+b.dataset.order);
      });
      sorted.forEach(function (c) { grid.appendChild(c); });
      $('#char-count').textContent = shown;
      $('#char-empty').classList.toggle('hidden', shown > 0);
      grid.classList.toggle('hidden', shown === 0);
    }
    function setCat(val) {
      state.cat = val;
      $$('.char-chip').forEach(function (x) {
        var on = x.dataset.charCat === val;
        cls(x, on ? ON : OFF, on ? OFF : ON);
        x.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      apply();
    }
    $$('.char-chip').forEach(function (chip) { chip.addEventListener('click', function () { setCat(chip.dataset.charCat); }); });
    if (input) input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { state.q = input.value; apply(); }, 120); });
    if (fsel) fsel.addEventListener('change', function () { state.fandom = fsel.value; apply(); });
    if (ssel) ssel.addEventListener('change', function () { state.sort = ssel.value; apply(); });
    var reset = $('#char-reset');
    if (reset) reset.addEventListener('click', function () {
      state = { q: '', cat: '', fandom: '', sort: 'featured' };
      if (input) input.value = ''; if (fsel) fsel.value = ''; if (ssel) ssel.value = 'featured';
      setCat('');
    });
  }

  /* Share a profile (native share sheet, else copy the deep link) */
  function shareItem(btn) {
    var link = location.origin + BASE + '?' + btn.dataset.share + '=' + encodeURIComponent(btn.dataset.id);
    var title = (btn.dataset.title || 'Fan Hub Plus') + ' — FAN HUB PLUS';
    if (navigator.share) { navigator.share({ title: title, url: link }).catch(function () {}); return; }
    function done() { toast('Link copied to clipboard'); }
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(link).then(done, function () { window.prompt('Copy this link:', link); });
    else window.prompt('Copy this link:', link);
  }

  /* Replace the top-most modal with another fragment (prev / next / related) */
  function swapFragment(view, id) {
    var old = modalStack[modalStack.length - 1];
    if (!old) return openFragment(view, id);
    return getHTML('api/fragment.php?view=' + encodeURIComponent(view) + '&id=' + encodeURIComponent(id)).then(function (html) {
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var el = wrap.firstElementChild;
      if (!el) return;
      el.style.zIndex = old.style.zIndex;
      $$('video, audio', old).forEach(function (m) { m.pause(); });
      old.replaceWith(el);
      modalStack[modalStack.length - 1] = el;
    }).catch(function () { toast('Sorry, that item could not be loaded.'); });
  }

  /* ================================================================ *
   *  Watch & Listen: featured player, audio, ratings
   * ================================================================ */
  function initMedia() {
    var fp = $('#featured-player');
    if (fp) {
      var playing = false, videoEl = null;
      var stage = $('[data-part="stage"]', fp);
      $('[data-action="play-featured"]', fp).addEventListener('click', function () {
        playing = !playing;
        $('[data-part="play"]', fp).classList.toggle('hidden', playing);
        $('[data-part="pause"]', fp).classList.toggle('hidden', !playing);
        $('[data-part="now"]', fp).classList.toggle('hidden', !playing);
        if (playing) {
          stopAudio();
          if (!videoEl) {
            if (fp.dataset.embed) {
              videoEl = document.createElement('iframe');
              videoEl.src = fp.dataset.embed;
              videoEl.allow = 'autoplay; encrypted-media; picture-in-picture';
              videoEl.allowFullscreen = true;
            } else {
              videoEl = document.createElement('video');
              videoEl.src = fp.dataset.src; videoEl.playsInline = true;
              videoEl.addEventListener('ended', function () { $('[data-action="play-featured"]', fp).click(); });
              videoEl.addEventListener('error', function () { toast('Video could not be played.'); });
            }
            videoEl.className = 'absolute inset-0 h-full w-full object-cover';
            stage.appendChild(videoEl);
            post('api/track.php', { type: 'content', id: fp.dataset.id });
          }
          stage.classList.remove('hidden');
          cls($('[data-part="caption"]', fp), 'opacity-0', '');
          if (videoEl.play) videoEl.play().catch(function () {});
        } else {
          if (videoEl && videoEl.pause) videoEl.pause();
          if (videoEl && videoEl.tagName === 'IFRAME') { videoEl.remove(); videoEl = null; stage.classList.add('hidden'); }
          cls($('[data-part="caption"]', fp), '', 'opacity-0');
        }
      });
    }

    // Audio rows — one shared HTML5 audio element
    $$('.audio-row').forEach(function (row) {
      row.addEventListener('click', function () {
        if (currentAudioRow === row && audio && !audio.paused) { stopAudio(); return; }
        stopAudio();
        audio = audio || new Audio();
        audio.src = row.dataset.src;
        audio.play().catch(function () { toast('Audio could not be played.'); });
        audio.onended = stopAudio;
        currentAudioRow = row;
        setAudioRow(row, true);
        post('api/track.php', { type: 'content', id: row.dataset.audio });
      });
    });
  }
  var audio = null, currentAudioRow = null;
  function setAudioRow(row, on) {
    if (on) cls(row, 'border-violet/60 bg-violet/10', 'border-white/10 bg-white/[0.03] hover:border-white/25');
    else cls(row, 'border-white/10 bg-white/[0.03] hover:border-white/25', 'border-violet/60 bg-violet/10');
    var btn = $('[data-part="btn"]', row);
    if (on) cls(btn, 'bg-violet text-white', 'bg-white/10 text-white/80'); else cls(btn, 'bg-white/10 text-white/80', 'bg-violet text-white');
    $('[data-part="play"]', row).classList.toggle('hidden', on);
    $('[data-part="pause"]', row).classList.toggle('hidden', !on);
    var bars = $('[data-part="bars"]', row);
    if (on) cls(bars, 'flex', 'hidden'); else cls(bars, 'hidden', 'flex');
  }
  function stopAudio() {
    if (audio) audio.pause();
    if (currentAudioRow) setAudioRow(currentAudioRow, false);
    currentAudioRow = null;
  }

  function rate(btn) {
    if (!requireAuth('Sign in to rate media.')) return;
    var cid = btn.dataset.content, stars = +btn.dataset.rate;
    post('api/rate.php', { content_id: cid, stars: stars }).then(function (j) {
      if (!j.ok) { if (j.error) toast(j.error); return; }
      $$('[data-rate][data-content="' + cid + '"]').forEach(function (s) {
        var on = +s.dataset.rate <= stars;
        cls(s, on ? 'text-amber' : 'text-white/30', on ? 'text-white/30 text-white/40' : 'text-amber');
      });
      $$('[data-part="rating-text"]').forEach(function (t) {
        var box = t.parentNode;
        if ($('[data-content="' + cid + '"]', box)) t.textContent = j.avg + ' · ' + j.count + (box.dataset.ratingBox ? ' ratings' : '');
      });
      toast(j.message);
    });
  }

  /* ================================================================ *
   *  Bookmarks (any article / video / character / merch)
   * ================================================================ */
  function toggleBookmark(btn) {
    if (!requireAuth('Sign in to bookmark items.')) return;
    var key = btn.dataset.bookmark;
    post('api/bookmark.php', { key: key }).then(function (j) {
      if (!j.ok) { if (j.error) toast(j.error); return; }
      $$('[data-bookmark="' + key + '"]').forEach(function (b) {
        var svg = $('svg', b);
        if (svg && !b.dataset.refresh) {
          if (j.saved) cls(svg, 'fill-amber text-amber', ''); else cls(svg, '', 'fill-amber text-amber scale-110');
        }
        var label = $('[data-part="label"]', b);
        if (label) label.textContent = j.saved ? 'Saved' : (b.closest('[data-modal]') && key.indexOf('merch') === 0 ? 'Save to wishlist' : 'Save');
      });
      toast(j.message);
      if (btn.dataset.refresh === 'dashboard') refreshDashboard();
    });
  }

  /* ================================================================ *
   *  Events: add to calendar, city filter, GPS near-me, map
   * ================================================================ */
  var eventState = { city: '', lat: null, lng: null };
  function toggleCalendar(btn) {
    if (!requireAuth('Sign in to build your event calendar.')) return;
    post('api/calendar.php', { event_id: btn.dataset.calendar }).then(function (j) {
      if (!j.ok) { if (j.error) toast(j.error); return; }
      if (j.added) {
        cls(btn, 'bg-emerald-500/20 text-emerald-300', 'border border-white/20 text-white hover:border-white/50');
        btn.innerHTML = tplIcon('circle-check') + ' Added to calendar';
      } else {
        cls(btn, 'border border-white/20 text-white hover:border-white/50', 'bg-emerald-500/20 text-emerald-300');
        btn.innerHTML = tplIcon('calendar-plus') + ' Add to calendar';
      }
      if (j.message) toast(j.message);
    });
  }

  function loadEvents() {
    var q = 'api/fragment.php?view=events&city=' + encodeURIComponent(eventState.city);
    if (eventState.lat !== null) q += '&lat=' + eventState.lat + '&lng=' + eventState.lng;
    var list = $('#events-list');
    list.style.opacity = 0.4;
    getHTML(q).then(function (html) { list.innerHTML = html; list.style.opacity = 1; })
      .catch(function () { list.style.opacity = 1; toast('Could not load events.'); });
  }

  function initEvents() {
    var ON = 'border-white bg-white text-black', OFF = 'border-white/15 text-white/60 hover:border-white/40';
    $$('.city-chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        $$('.city-chip').forEach(function (x) { cls(x, x === chip ? ON : OFF, x === chip ? OFF : ON); });
        eventState.city = chip.dataset.eventCity;
        loadEvents();
        if (window.__fhMap) window.__fhMap.focusCity(eventState.city);
      });
    });
    var near = $('[data-action="near-me"]');
    if (near) near.addEventListener('click', function () {
      var label = $('[data-part="label"]', near);
      if (!navigator.geolocation) { toast('Location is not available in this browser.'); return; }
      label.textContent = 'Locating…';
      navigator.geolocation.getCurrentPosition(function (pos) {
        eventState.lat = pos.coords.latitude.toFixed(5);
        eventState.lng = pos.coords.longitude.toFixed(5);
        label.textContent = 'Sorted by distance';
        loadEvents();
        if (window.__fhMap) window.__fhMap.showUser(+eventState.lat, +eventState.lng);
      }, function () {
        label.textContent = 'Near me';
        toast('Location permission denied — showing all events.');
      }, { timeout: 10000, maximumAge: 600000 });
    });
    initMap();
  }

  function initMap() {
    var box = $('#events-map');
    if (!box) return;
    var points = [];
    try { points = JSON.parse(box.dataset.points || '[]'); } catch (e) {}
    var started = false;
    function load() {
      if (started) return; started = true;
      var css = document.createElement('link');
      css.rel = 'stylesheet'; css.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
      document.head.appendChild(css);
      var s = document.createElement('script');
      s.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
      s.onload = function () { buildMap(box, points); };
      s.onerror = function () { box.firstElementChild.textContent = 'Map unavailable offline'; };
      document.head.appendChild(s);
    }
    if ('IntersectionObserver' in window) {
      var io = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { io.disconnect(); load(); } }, { rootMargin: '400px' });
      io.observe(box);
    } else load();
  }

  function buildMap(box, points) {
    var L = window.L;
    box.innerHTML = '';
    var map = L.map(box, { scrollWheelZoom: false, zoomControl: true, attributionControl: true }).setView([25, 40], 2);
    // Use OpenStreetMap tiles directly so the map does not require a CARTO API key.
    var tileUrl = function () {
      return 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    };
    var tiles = L.tileLayer(tileUrl(), {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19
    }).addTo(map);
    var markers = [];
    points.forEach(function (p) {
      var m = L.circleMarker([p.lat, p.lng], { radius: 8, color: '#8b5cf6', weight: 2, fillColor: '#ec4899', fillOpacity: 0.85 }).addTo(map);
      var div = document.createElement('div');
      var b = document.createElement('b'); b.textContent = p.title; div.appendChild(b);
      div.appendChild(document.createElement('br'));
      div.appendChild(document.createTextNode(p.venue + ' · ' + p.date));
      m.bindPopup(div);
      m._city = p.city;
      markers.push(m);
    });
    if (markers.length) map.fitBounds(L.featureGroup(markers).getBounds().pad(0.25));
    var userMarker = null;
    window.__fhMap = {
      setTheme: function (t) { tiles.setUrl(tileUrl(t)); },
      focusCity: function (city) {
        var sel = markers.filter(function (m) { return !city || m._city === city; });
        if (sel.length === 1) map.setView(sel[0].getLatLng(), 11);
        else if (sel.length) map.fitBounds(L.featureGroup(sel).getBounds().pad(0.25));
      },
      showUser: function (lat, lng) {
        if (userMarker) userMarker.remove();
        userMarker = L.circleMarker([lat, lng], { radius: 7, color: '#22d3ee', weight: 3, fillColor: '#22d3ee', fillOpacity: 0.5 }).addTo(map).bindPopup('You are here');
        var near = markers.slice().sort(function (a, b) { return a.getLatLng().distanceTo([lat, lng]) - b.getLatLng().distanceTo([lat, lng]); })[0];
        map.fitBounds(L.featureGroup(near ? [userMarker, near] : [userMarker]).getBounds().pad(0.4));
      }
    };
  }

  /* ================================================================ *
   *  Server-rendered modals (article, character, merch, media, dashboard)
   * ================================================================ */
  var modalStack = [];
  function openFragment(view, id) {
    var url = 'api/fragment.php?view=' + encodeURIComponent(view) + (id ? '&id=' + encodeURIComponent(id) : '');
    return getHTML(url).then(function (html) {
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var el = wrap.firstElementChild;
      if (!el) return null;
      el.style.zIndex = String(100 + modalStack.length * 2);
      $('#modal-root').appendChild(el);
      modalStack.push(el);
      lockScroll();
      if (view !== 'media') stopAudio();
      return el;
    }).catch(function (code) {
      if (code === 401) openAuth('login'); else toast('Sorry, that item could not be loaded.');
    });
  }
  function closeTopModal() {
    var el = modalStack.pop();
    if (!el) return false;
    $$('video, audio', el).forEach(function (m) { m.pause(); });
    el.remove();
    unlockScroll();
    return true;
  }

  function openDashboard() {
    if (!requireAuth()) return;
    openFragment('dashboard');
  }
  function refreshDashboard() {
    var old = $('[data-dashboard]');
    if (!old) return;
    getHTML('api/fragment.php?view=dashboard').then(function (html) {
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var el = wrap.firstElementChild;
      el.style.zIndex = old.style.zIndex;
      el.classList.remove('animate-[fade-in_0.4s_ease]');
      var st = old.scrollTop;
      old.replaceWith(el);
      el.scrollTop = st;
      modalStack[modalStack.indexOf(old)] = el;
    });
  }

  /* ================================================================ *
   *  Search overlay (SearchOverlay.tsx) — results from api/search.php
   * ================================================================ */
  var search = { q: '', category: 'All', sort: 'Popular', type: 'All', year: '', genre: '' };
  var searchOpen = false, searchTimer = null, searchReq = 0;
  function runSearch() {
    var id = ++searchReq;
    var qs = Object.keys(search).map(function (k) { return k + '=' + encodeURIComponent(search[k]); }).join('&');
    getHTML('api/search.php?' + qs).then(function (html) { if (id === searchReq) $('#search-body').innerHTML = html; });
  }
  function openSearch(opts) {
    var ov = $('#search-overlay');
    if (!ov) return;
    search = Object.assign({ q: '', category: 'All', sort: 'Popular', type: 'All', year: '', genre: '' }, opts || {});
    $('#search-input').value = search.q;
    if (!searchOpen) { cls(ov, 'flex', 'hidden'); lockScroll(); searchOpen = true; }
    setMenu(false);
    runSearch();
    setTimeout(function () { $('#search-input').focus(); }, 50);
  }
  function closeSearch() {
    if (!searchOpen) return;
    cls($('#search-overlay'), 'hidden', 'flex');
    searchOpen = false;
    unlockScroll();
  }

  /* ================================================================ *
   *  Auth modal (AuthModal.tsx) — login / signup / forgot password
   * ================================================================ */
  var AUTH_MODES = {
    login:  { title: 'Welcome back', sub: 'Sign in to access your Fandom Command Center.', submit: 'Enter Universe', q: 'New here?', link: 'Create an account', to: 'signup', fields: ['email', 'password', 'forgot-link'] },
    signup: { title: 'Join the universe', sub: 'Create an account to save favorites and bookmarks.', submit: 'Create Account', q: 'Already have an account?', link: 'Sign in', to: 'login', fields: ['name', 'email', 'password'] },
    forgot: { title: 'Reset password', sub: "Enter your email and we'll send you a secure reset link.", submit: 'Send reset link', q: 'Remembered it?', link: 'Sign in', to: 'login', fields: ['email'] }
  };
  var authOpen = false;
  function setAuthMode(mode) {
    var m = AUTH_MODES[mode], form = $('#auth-form');
    form.mode.value = mode;
    $('#auth-title').textContent = m.title;
    $('#auth-sub').textContent = m.sub;
    $('#auth-submit').textContent = m.submit;
    $('#auth-switch-text').textContent = m.q;
    var sw = $('#auth-switch'); sw.textContent = m.link; sw.dataset.authMode = m.to;
    $$('[data-field]', form).forEach(function (f) {
      var on = m.fields.indexOf(f.dataset.field) > -1;
      if (on) cls(f, 'flex', 'hidden'); else cls(f, 'hidden', 'flex');
    });
    form.password.autocomplete = mode === 'signup' ? 'new-password' : 'current-password';
    authMsg('');
  }
  function authMsg(text, ok, html) {
    var box = $('#auth-msg');
    if (!text) { cls(box, 'hidden', ''); return; }
    box.className = 'rounded-xl border px-4 py-3 text-xs leading-relaxed ' + (ok ? 'border-emerald-400/30 bg-emerald-500/10 text-emerald-300' : 'border-red-400/30 bg-red-500/10 text-red-300');
    box.textContent = text;
    if (html) box.appendChild(html);
  }
  function demoLinkNode(link) {
    var wrap = document.createElement('div');
    wrap.className = 'mt-2 break-all';
    var lbl = document.createElement('span'); lbl.textContent = 'Demo mode (no mail server): '; wrap.appendChild(lbl);
    var a = document.createElement('a'); a.href = link; a.textContent = 'open link'; a.className = 'font-semibold text-white underline';
    wrap.appendChild(a);
    return wrap;
  }
  function openAuth(mode) {
    var m = $('#auth-modal');
    if (!m) return;
    closeSearch(); setMenu(false);
    setAuthMode(mode || 'login');
    if (!authOpen) { cls(m, 'flex', 'hidden'); lockScroll(); authOpen = true; }
    setTimeout(function () { var f = $('#auth-form input[name="' + (mode === 'signup' ? 'name' : 'email') + '"]'); if (f) f.focus(); }, 50);
  }
  function closeAuth() {
    if (!authOpen) return;
    cls($('#auth-modal'), 'hidden', 'flex');
    authOpen = false; unlockScroll();
  }
  function initAuth() {
    var form = $('#auth-form');
    if (!form) return;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var mode = form.mode.value;
      var email = form.email.value.trim();
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { authMsg('Please enter a valid email address.'); return; }
      if (mode !== 'forgot' && !form.password.value) { authMsg('Please enter your password.'); return; }
      if (mode === 'signup' && form.name.value.trim().length < 2) { authMsg('Please enter your full name.'); return; }
      var btn = $('#auth-submit'); btn.disabled = true; btn.style.opacity = 0.6;
      var data = { action: mode === 'signup' ? 'register' : mode, email: email, password: form.password.value, name: form.name.value };
      post('api/auth.php', data).then(function (j) {
        btn.disabled = false; btn.style.opacity = '';
        if (mode === 'login') {
          if (j.ok) {
            try { sessionStorage.setItem('fh_loaded', '1'); } catch (x) {}
            if (j.admin) { location.href = BASE + 'admin/'; return; }
            toast('Welcome back!'); location.reload(); return;
          }
          if (j.unverified) {
            var r = document.createElement('button');
            r.type = 'button'; r.className = 'mt-2 block font-semibold text-white underline'; r.textContent = 'Resend verification link';
            r.onclick = function () {
              post('api/auth.php', { action: 'resend', email: email }).then(function (k) { authMsg(k.message, true, k.demo_link ? demoLinkNode(k.demo_link) : null); });
            };
            authMsg(j.error, false, r);
          } else authMsg(j.error || 'Login failed.');
          return;
        }
        if (j.ok) { authMsg(j.message, true, j.demo_link ? demoLinkNode(j.demo_link) : null); if (mode === 'signup') form.password.value = ''; }
        else authMsg(j.error || 'Something went wrong.');
      });
    });
  }

  /* ================================================================ *
   *  Feedback modal (dynamic by type)
   * ================================================================ */
  var fbOpen = false;
  function openFeedback() {
    var m = $('#feedback-modal'); if (!m) return;
    closeSearch(); setMenu(false);
    if (!fbOpen) { cls(m, 'flex', 'hidden'); lockScroll(); fbOpen = true; }
  }
  function closeFeedback() {
    if (!fbOpen) return;
    cls($('#feedback-modal'), 'hidden', 'flex'); fbOpen = false; unlockScroll();
  }
  function initFeedback() {
    var form = $('#feedback-form'); if (!form) return;
    var ON = 'border-white bg-white text-black', OFF = 'border-white/15 text-white/60 hover:border-white/40';
    function setType(t) {
      form.type.value = t;
      $$('.fb-chip', form).forEach(function (c) { var on = c.dataset.fbType === t; cls(c, on ? ON : OFF, on ? OFF : ON); });
      $$('[data-fb-field]', form).forEach(function (f) { var on = f.dataset.fbField === t; if (on) cls(f, 'flex', 'hidden'); else cls(f, 'hidden', 'flex'); });
      form.message.placeholder = t === 'bug' ? 'What happened? Steps to reproduce…' : t === 'query' ? 'What would you like to know?' : 'Tell us more…';
    }
    $$('.fb-chip', form).forEach(function (c) { c.addEventListener('click', function () { setType(c.dataset.fbType); }); });
    $$('[data-fb-star]', form).forEach(function (s) {
      s.addEventListener('click', function () {
        var v = +s.dataset.fbStar; form.rating.value = v;
        $$('[data-fb-star]', form).forEach(function (x) { cls(x, +x.dataset.fbStar <= v ? 'text-amber' : 'text-white/30', +x.dataset.fbStar <= v ? 'text-white/30' : 'text-amber'); });
      });
    });
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var box = $('#feedback-msg');
      post('api/feedback.php', new FormData(form)).then(function (j) {
        box.className = 'rounded-xl border px-4 py-3 text-xs ' + (j.ok ? 'border-emerald-400/30 bg-emerald-500/10 text-emerald-300' : 'border-red-400/30 bg-red-500/10 text-red-300');
        box.textContent = j.ok ? j.message : j.error;
        if (j.ok) { form.message.value = ''; form.page.value = ''; setTimeout(closeFeedback, 1600); }
      });
    });
  }

  /* ================================================================ *
   *  ORA chatbot — api/chat.php (text + cards + quick actions + feedback)
   * ================================================================ */
  var chatOpen = false, chatLoaded = false, chatBusy = false, lastUserMsg = '';
  function esc(t) { return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function fmtChat(t) {
    return esc(t)
      .replace(/\*\*(.+?)\*\*/g, '<strong class="font-semibold text-white">$1</strong>')
      .replace(/(^|[\s(])\*([^*\n]+)\*(?=[\s).,!?]|$)/g, '$1<em>$2</em>')
      .replace(/\bhttps?:\/\/[^\s<]+/g, function (u) { return '<a href="' + u + '" target="_blank" rel="noopener" class="underline decoration-violet/60 underline-offset-2">' + u + '</a>'; })
      .replace(/\n/g, '<br>');
  }
  function scrollChat(instant) { var m = $('#chat-messages'); if (m) m.scrollTo({ top: m.scrollHeight, behavior: instant ? 'auto' : 'smooth' }); }
  function chatCard(c) {
    var isEvent = c.type === 'event' || c.type === 'release';
    var attrs = isEvent ? 'data-chat-go="#events"' : 'data-open="' + esc(c.type) + '" data-id="' + (+c.id) + '"';
    var img = c.image ? '<img src="' + esc(c.image) + '" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">'
                      : '<span class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-violet/40 to-magenta/30 text-white">' + tplIcon('calendar') + '</span>';
    return '<button type="button" ' + attrs + ' data-cursor-hover class="chat-card group relative h-36 w-28 flex-none overflow-hidden rounded-xl text-left noise-border">' + img +
      '<span class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent"></span>' +
      '<span class="absolute inset-x-0 bottom-0 p-2"><span class="line-clamp-2 block text-[11px] font-semibold leading-tight text-white">' + esc(c.title) + '</span>' +
      '<span class="mt-0.5 block truncate text-[9px] uppercase tracking-[0.12em] text-cyan">' + esc(c.sub || '') + '</span></span></button>';
  }
  function addMsg(role, text, o) {
    o = o || {};
    var box = $('#chat-messages');
    var row = document.createElement('div');
    row.className = 'chat-row flex gap-2 ' + (role === 'user' ? 'items-end justify-end' : 'items-start');
    if (role === 'user') {
      row.innerHTML = '<div class="chat-bubble chat-user">' + esc(text) + '</div>';
    } else {
      var html = $('#tpl-chat-avatar').innerHTML;
      html += '<div class="min-w-0 flex-1"><div class="chat-bubble chat-bot' + (o.error ? ' chat-error' : '') + '">' + fmtChat(text) + '</div>';
      if (o.cards && o.cards.length) html += '<div class="-mx-1 mt-2 flex gap-2 overflow-x-auto px-1 pb-1 no-scrollbar" data-lenis-prevent>' + o.cards.map(chatCard).join('') + '</div>';
      if (o.id) {
        var up = o.helpful === 1, dn = o.helpful === 0;
        html += '<div class="mt-1.5 flex items-center gap-1 pl-1 text-white/35" data-chat-rate="' + o.id + '">' +
          '<button type="button" data-chat-fb="1" aria-label="Helpful" class="rounded-full p-1 transition hover:text-emerald-400 ' + (up ? 'text-emerald-400' : '') + '">' + tplIcon('thumbs-up') + '</button>' +
          '<button type="button" data-chat-fb="0" aria-label="Not helpful" class="rounded-full p-1 transition hover:text-red-400 ' + (dn ? 'text-red-400' : '') + '">' + tplIcon('thumbs-down') + '</button>' +
          (o.time ? '<span class="ml-1 text-[10px]">' + o.time + '</span>' : '') + '</div>';
      }
      if (o.retry) html += '<button type="button" data-chat-retry class="mt-2 rounded-full border border-white/15 px-3 py-1 text-[11px] text-white/70 hover:border-white/40 hover:text-white">Try again</button>';
      html += '</div>';
      row.innerHTML = html;
    }
    box.appendChild(row);
    if (o.actions) setChatActions(o.actions);
    scrollChat(o.instant);
    return row;
  }
  function setChatActions(actions) {
    var wrap = $('#chat-suggestions');
    if (!actions || !actions.length) { wrap.innerHTML = ''; wrap.classList.add('hidden'); return; }
    wrap.classList.remove('hidden');
    wrap.innerHTML = actions.map(function (a, i) {
      var ico = a.send ? '' : tplIcon('arrow-up-right');
      return '<button type="button" data-chat-action="' + i + '" data-cursor-hover class="flex flex-none items-center gap-1 rounded-full border border-white/15 bg-white/[0.03] px-3 py-1.5 text-[11px] text-white/75 transition hover:border-violet/60 hover:text-white">' + esc(a.label) + ico + '</button>';
    }).join('');
    wrap._actions = actions;
    wrap.scrollLeft = 0;
  }
  function runChatAction(a) {
    if (!a) return;
    var small = window.innerWidth < 640;
    if (a.send) return sendChat(a.send);
    if (a.go) { if (small) toggleChat(false); scrollToTarget(a.go); return; }
    if (a.open) return openFragment(a.open.view, a.open.id);
    if (a.search) return openSearch({ q: a.search });
    if (a.searchCategory) return openSearch({ category: a.searchCategory });
    if (a.searchType) return openSearch({ type: a.searchType, sort: 'Latest' });
    if (a['do'] === 'auth') { toggleChat(false); openAuth('login'); return; }
    if (a['do'] === 'dashboard') { toggleChat(false); openDashboard(); return; }
    if (a['do'] === 'feedback') { toggleChat(false); openFeedback(); }
  }
  function typing(on) {
    var t = $('#chat-typing');
    if (on && !t) {
      t = document.createElement('div'); t.id = 'chat-typing'; t.className = 'flex items-start gap-2';
      t.innerHTML = $('#tpl-chat-avatar').innerHTML + '<div class="flex gap-1 rounded-2xl rounded-bl-md bg-white/8 px-4 py-3">' +
        [0, 1, 2].map(function (i) { return '<span class="h-1.5 w-1.5 animate-bounce rounded-full bg-white/60" style="animation-delay:' + (i * 120) + 'ms"></span>'; }).join('') + '</div>';
      $('#chat-messages').appendChild(t); scrollChat();
    } else if (!on && t) t.remove();
  }
  function nowTime() { var d = new Date(); return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2); }
  function sendChat(text) {
    text = (text || '').trim();
    if (!text || chatBusy) return;
    chatBusy = true; lastUserMsg = text;
    $('#chat-send').disabled = true;
    addMsg('user', text);
    var inp = $('#chat-input'); inp.value = ''; autoGrow(inp);
    setChatActions([]);
    typing(true);
    var started = Date.now();
    post('api/chat.php', { action: 'send', message: text }).then(function (j) {
      setTimeout(function () {
        typing(false);
        chatBusy = false; $('#chat-send').disabled = false;
        if (j.ok) addMsg('bot', j.reply, { id: j.id, cards: j.cards, actions: j.actions, time: nowTime() });
        else addMsg('bot', j.error || 'ORA is offline right now.', { error: true, retry: true });
      }, Math.max(0, 450 + Math.random() * 350 - (Date.now() - started)));
    });
  }
  function autoGrow(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 112) + 'px'; }
  function loadChatHistory() {
    fetch(BASE + 'api/chat.php?action=history', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      if (j.ai) $('#chat-mode').textContent = '· AI powered';
      var h = j.history || [];
      if (!h.length) return;
      var sep = document.createElement('p');
      sep.className = 'text-center text-[10px] uppercase tracking-[0.25em] text-white/30';
      sep.textContent = 'Earlier conversation';
      $('#chat-messages').appendChild(sep);
      h.forEach(function (x) { addMsg('user', x.message, { instant: true }); addMsg('bot', x.reply, { id: x.id, cards: x.cards, helpful: x.helpful, instant: true }); });
      setChatActions([]);
      $('#chat-suggestions').classList.remove('hidden');
      setChatActions([{ label: "What's trending?", send: "What's trending?" }, { label: 'Show me characters', send: 'Show me characters' }, { label: 'Upcoming events', send: 'Upcoming events' }]);
      scrollChat(true);
    }).catch(function () {});
  }
  function toggleChat(force) {
    var want = typeof force === 'boolean' ? force : !chatOpen;
    if (want === chatOpen) return;
    chatOpen = want;
    var p = $('#chat-panel'), t = $('#chat-toggle');
    if (chatOpen) cls(p, 'opacity-100 translate-y-0 scale-100 pointer-events-auto', 'pointer-events-none opacity-0 translate-y-6 scale-95');
    else cls(p, 'pointer-events-none opacity-0 translate-y-6 scale-95', 'opacity-100 translate-y-0 scale-100 pointer-events-auto');
    p.setAttribute('aria-hidden', chatOpen ? 'false' : 'true');
    t.setAttribute('aria-expanded', chatOpen ? 'true' : 'false');
    $('.chat-icon-open').classList.toggle('hidden', chatOpen);
    $('.chat-icon-close').classList.toggle('hidden', !chatOpen);
    var ping = $('.chat-ping'); if (ping) ping.classList.add('hidden');
    hideTeaser();
    try { sessionStorage.setItem('fh_chat_seen', '1'); } catch (x) {}
    if (chatOpen && !chatLoaded) { chatLoaded = true; loadChatHistory(); }
    if (chatOpen && window.innerWidth >= 640) setTimeout(function () { $('#chat-input').focus(); }, 250);
  }
  function hideTeaser() { var tz = $('#chat-teaser'); if (tz) cls(tz, 'pointer-events-none opacity-0 translate-y-2', 'opacity-100 translate-y-0 pointer-events-auto'); }
  function initChat() {
    var t = $('#chat-toggle'); if (!t) return;
    t.addEventListener('click', function () { toggleChat(); });
    $('#chat-close').addEventListener('click', function () { toggleChat(false); });
    $('#chat-form').addEventListener('submit', function (e) { e.preventDefault(); sendChat($('#chat-input').value); });
    var inp = $('#chat-input');
    inp.addEventListener('input', function () { autoGrow(inp); });
    inp.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); sendChat(inp.value); }
    });
    $('#chat-clear').addEventListener('click', function () {
      post('api/chat.php', { action: 'clear' }).then(function () {
        $$('#chat-messages > *').forEach(function (el) { if (!el.hasAttribute('data-welcome')) el.remove(); });
        $('#chat-suggestions').classList.remove('hidden');
        setChatActions([{ label: "What's trending?", send: "What's trending?" }, { label: 'Show me characters', send: 'Show me characters' }, { label: 'Upcoming events', send: 'Upcoming events' }, { label: 'Give me a tour', send: 'Give me a tour' }]);
        toast('Started a new conversation');
      });
    });
    // clicks inside the panel: suggestions, quick actions, cards, feedback, retry
    $('#chat-panel').addEventListener('click', function (e) {
      var s = e.target.closest('[data-chat-suggest]');
      if (s) { sendChat(s.dataset.chatSuggest); return; }
      var a = e.target.closest('[data-chat-action]');
      if (a) { runChatAction(($('#chat-suggestions')._actions || [])[+a.dataset.chatAction]); return; }
      var g = e.target.closest('[data-chat-go]');
      if (g) { e.stopPropagation(); if (window.innerWidth < 640) toggleChat(false); scrollToTarget(g.dataset.chatGo); return; }
      var r = e.target.closest('[data-chat-retry]');
      if (r) { r.closest('.chat-row').remove(); var m = lastUserMsg; $$('#chat-messages .chat-user').slice(-1).forEach(function (u) { u.closest('.chat-row').remove(); }); sendChat(m); return; }
      var fb = e.target.closest('[data-chat-fb]');
      if (fb) {
        var box = fb.parentNode, val = fb.dataset.chatFb;
        post('api/chat.php', { action: 'feedback', id: box.dataset.chatRate, value: val }).then(function (j) {
          if (!j.ok) return;
          $$('[data-chat-fb]', box).forEach(function (b) { b.classList.remove('text-emerald-400', 'text-red-400'); });
          fb.classList.add(val === '1' ? 'text-emerald-400' : 'text-red-400');
          toast(j.message);
        });
      }
    });
    $$('[data-chat-open]').forEach(function (b) { b.addEventListener('click', function () { toggleChat(true); }); });

    // voice input (Web Speech API, where supported)
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition, mic = $('#chat-mic');
    if (SR && mic) {
      mic.classList.remove('hidden'); mic.classList.add('flex');
      var rec = null;
      mic.addEventListener('click', function () {
        if (rec) { rec.stop(); return; }
        rec = new SR(); rec.lang = navigator.language || 'en-US'; rec.interimResults = true;
        mic.classList.add('text-magenta');
        rec.onresult = function (ev) { inp.value = Array.prototype.map.call(ev.results, function (r) { return r[0].transcript; }).join(''); autoGrow(inp); };
        rec.onend = function () { mic.classList.remove('text-magenta'); rec = null; if (inp.value.trim()) sendChat(inp.value); };
        rec.onerror = function () { toast('Voice input is not available right now.'); };
        rec.start();
      });
    }

    // gentle teaser for first-time visitors
    try {
      if (!sessionStorage.getItem('fh_chat_seen')) setTimeout(function () {
        if (chatOpen) return;
        var tz = $('#chat-teaser'); cls(tz, 'opacity-100 translate-y-0 pointer-events-auto', 'pointer-events-none opacity-0 translate-y-2');
        setTimeout(hideTeaser, 9000);
      }, 9000);
    } catch (x) {}
  }

  /* ================================================================ *
   *  Generic AJAX forms (dashboard: profile, prefs, favourites, notes, submissions)
   * ================================================================ */
  function submitApiForm(form, silent) {
    var fd = new FormData(form);
    var msg = $('[data-form-msg]', form);
    var btn = $('button:not([type="button"])', form);
    if (btn && !silent) btn.disabled = true;
    return post(form.dataset.api, fd).then(function (j) {
      if (btn) btn.disabled = false;
      if (form.hasAttribute('data-prefs') && j.ok) {
        applyTheme(j.theme); applyFont(j.font);
      }
      if (msg && !silent) {
        msg.className = 'rounded-xl border px-4 py-3 text-xs ' + (msg.className.indexOf('sm:col-span-2') > -1 ? 'sm:col-span-2 ' : '') +
          (j.ok ? 'border-emerald-400/30 bg-emerald-500/10 text-emerald-300' : 'border-red-400/30 bg-red-500/10 text-red-300');
        msg.textContent = j.ok ? j.message : j.error;
      } else if (j.message || j.error) toast(j.ok ? j.message : j.error);
      if (j.ok && form.dataset.refresh === 'dashboard') setTimeout(refreshDashboard, silent ? 0 : 900);
      return j;
    });
  }
  document.addEventListener('submit', function (e) {
    var f = e.target.closest('form[data-api]');
    if (!f) return;
    e.preventDefault();
    submitApiForm(f, false);
  });
  document.addEventListener('change', function (e) {
    var f = e.target.closest('form[data-autosave]');
    if (f) submitApiForm(f, true);
    if (e.target.type === 'file') {
      var lbl = e.target.closest('label') && $('[data-file-label]', e.target.closest('label'));
      if (lbl && e.target.files[0]) lbl.textContent = e.target.files[0].name;
    }
    var sel = e.target.closest('[data-sf-select]');
    if (sel) { search[sel.dataset.sfSelect] = sel.value; runSearch(); }
  });

  /* ================================================================ *
   *  Delegated click handling
   * ================================================================ */
  document.addEventListener('click', function (e) {
    var t = e.target;

    var bm = t.closest('[data-bookmark]');
    if (bm) { e.preventDefault(); e.stopPropagation(); toggleBookmark(bm); return; }

    var rt = t.closest('[data-rate]');
    if (rt) { e.preventDefault(); e.stopPropagation(); rate(rt); return; }

    var cal = t.closest('[data-calendar]');
    if (cal) { e.preventDefault(); toggleCalendar(cal); return; }

    var close = t.closest('[data-close]');
    if (close) {
      var what = close.dataset.close;
      if (what === 'modal') closeTopModal();
      else if (what === 'auth') closeAuth();
      else if (what === 'feedback') closeFeedback();
      return;
    }

    var sf = t.closest('[data-sf]');
    if (sf) { search[sf.dataset.sf] = sf.dataset.value; runSearch(); return; }

    var am = t.closest('[data-auth-mode]');
    if (am && authOpen) { e.preventDefault(); setAuthMode(am.dataset.authMode); return; }

    var sw = t.closest('[data-swap]');
    if (sw) { e.preventDefault(); swapFragment(sw.dataset.swap, sw.dataset.id); return; }

    var sh = t.closest('[data-share]');
    if (sh) { e.preventDefault(); shareItem(sh); return; }

    var op = t.closest('[data-open]');
    if (op && !op.hasAttribute('data-cf-card')) {
      e.preventDefault();
      if (searchOpen) closeSearch();
      openFragment(op.dataset.open, op.dataset.id);
      return;
    }

    var sc = t.closest('[data-search-category]');
    if (sc) { e.preventDefault(); openSearch({ category: sc.dataset.searchCategory }); return; }
    var st = t.closest('[data-search-type]');
    if (st) { e.preventDefault(); openSearch({ type: st.dataset.searchType, sort: 'Latest' }); return; }
    var sq = t.closest('[data-search-query]');
    if (sq) { e.preventDefault(); while (modalStack.length) closeTopModal(); openSearch({ q: sq.dataset.searchQuery }); return; }

    var go = t.closest('[data-go]');
    if (go) {
      var goHref = go.getAttribute('href');
      var goDest = null;
      if (goHref) { try { goDest = new URL(goHref, location.href); } catch (err) {} }
      if (goDest && goDest.pathname !== location.pathname) {
        // Link points to a different page — let the browser navigate there normally.
        setMenu(false);
        return;
      }
      e.preventDefault();
      setMenu(false);
      var goHash = goDest ? goDest.hash : go.dataset.go;
      if (goHash) scrollToTarget(goHash);
      return;
    }

    var act = t.closest('[data-action]');
    if (act) {
      switch (act.dataset.action) {
        case 'theme': toggleTheme(); return;
        case 'font': cycleFont(); return;
        case 'search': openSearch(); return;
        case 'search-close': closeSearch(); return;
        case 'auth': e.preventDefault(); while (modalStack.length) closeTopModal(); openAuth('login'); return;
        case 'dashboard': setMenu(false); openDashboard(); return;
        case 'feedback': e.preventDefault(); openFeedback(); return;
        case 'logout':
          post('api/auth.php', { action: 'logout' }).then(function () {
            try { sessionStorage.setItem('fh_loaded', '1'); } catch (x) {}
            location.reload();
          });
          return;
      }
    }

    // In-page anchors use Lenis smooth scrolling
    var a = t.closest('a[href^="#"]');
    if (a && a.getAttribute('href').length > 1) {
      var target = $(a.getAttribute('href'));
      if (target) { e.preventDefault(); setMenu(false); scrollToTarget(a.getAttribute('href')); }
    }
  });

  // Enter / Space activate role="button" elements (keyboard accessibility)
  document.addEventListener('keydown', function (e) {
    if ((e.key === 'Enter' || e.key === ' ') && e.target.matches && e.target.matches('[role="button"]')) {
      e.preventDefault(); e.target.click();
    }
    if (e.key === 'Escape') {
      if (fbOpen) closeFeedback();
      else if (authOpen) closeAuth();
      else if (modalStack.length) closeTopModal();
      else if (searchOpen) closeSearch();
      else if (chatOpen) toggleChat();
      else if (menuOpen) setMenu(false);
    }
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openSearch(); }
  });

  /* ================================================================ *
   *  Boot
   * ================================================================ */
  function boot() {
    var mt = $('#menu-toggle');
    if (mt) mt.addEventListener('click', function () { setMenu(!menuOpen); });

    var si = $('#search-input');
    if (si) si.addEventListener('input', function () {
      search.q = si.value;
      clearTimeout(searchTimer);
      searchTimer = setTimeout(runSearch, 220);
    });

    window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', function (ev) {
      reducedMotion = ev.matches;
      document.documentElement.classList.toggle('reduce-motion', reducedMotion);
    });
    document.documentElement.classList.toggle('reduce-motion', reducedMotion);

    initCursor();
    initScrollEffects();
    initHero();
    initFandomPanels();
    initTrending();
    initSpotlight();
    initCharacterGrid();
    initMedia();
    initEvents();
    initAuth();
    initFeedback();
    initChat();

    if ($('#loader') || $('#main-content')) initLoader();
    else startLenis();

    // open a modal from URL, e.g. index.php?open=dashboard  (used after e-mail verification)
    var p = new URLSearchParams(location.search);
    if (p.get('open') === 'dashboard' && AUTHED) openDashboard();
    ['character', 'article', 'merch', 'media'].forEach(function (v) {
      if (p.get(v) && /^\d+$/.test(p.get(v))) openFragment(v, p.get(v));
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
