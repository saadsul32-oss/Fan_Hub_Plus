/* FAN HUB PLUS — admin console: video / audio player + media URL preview */
(function () {
  'use strict';
  var base = (window.FH && FH.base) || '/';

  /* Turn what an admin typed into a playable URL */
  function resolve(u) {
    u = (u || '').trim();
    if (!u) return '';
    if (/^(https?:)?\/\//i.test(u) || u.indexOf('data:') === 0 || u.charAt(0) === '/') return u;
    return base + u;
  }
  function embedOf(u) {
    var m = /(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/.exec(u || '');
    if (m) return 'https://www.youtube-nocookie.com/embed/' + m[1] + '?autoplay=1&rel=0';
    m = /vimeo\.com\/(\d+)/.exec(u || '');
    if (m) return 'https://player.vimeo.com/video/' + m[1] + '?autoplay=1';
    return '';
  }
  function isAudio(u, type) {
    if (type === 'audio') return true;
    return /\.(mp3|m4a|aac|ogg|oga|wav|flac|opus)(\?|#|$)/i.test(u || '');
  }
  function el(tag, attrs) {
    var n = document.createElement(tag);
    for (var k in attrs) if (attrs[k] !== undefined && attrs[k] !== null) n.setAttribute(k, attrs[k]);
    return n;
  }
  function errorNote(media, where) {
    media.addEventListener('error', function () {
      var p = el('p', { class: 'adm-media-status is-bad', role: 'status' });
      p.style.padding = '10px 14px';
      p.textContent = 'This file could not be played. Check that the link is correct and the file is an MP4 video or MP3 audio.';
      where.appendChild(p);
    });
  }

  /* ---------- modal player ---------- */
  var player = document.getElementById('adm-player');
  if (player) {
    var stage = player.querySelector('[data-player-stage]');
    var title = document.getElementById('adm-player-title');
    var openLink = player.querySelector('[data-player-open]');
    var lastFocus = null;

    function close() {
      stage.innerHTML = '';
      player.classList.remove('is-open');
      document.body.style.overflow = '';
      if (lastFocus) lastFocus.focus();
    }
    function open(btn) {
      lastFocus = btn;
      stopInline();
      stage.innerHTML = '';
      var src = btn.dataset.src, embed = btn.dataset.embed, poster = btn.dataset.poster;
      title.textContent = btn.dataset.title || '';
      openLink.href = embed ? embed.replace(/\?.*$/, '') : src;
      if (embed) {
        stage.appendChild(el('iframe', { src: embed, allow: 'autoplay; encrypted-media; picture-in-picture', allowfullscreen: '', title: btn.dataset.title || 'Player' }));
      } else if (btn.dataset.play === 'audio') {
        if (poster) { var pi = el('img', { src: poster, alt: '' }); pi.onerror = function () { pi.remove(); }; stage.appendChild(pi); }
        var a = el('audio', { src: src, controls: '', autoplay: '', preload: 'auto' });
        stage.appendChild(a); errorNote(a, stage);
        a.play && a.play().catch(function () {});
      } else {
        var v = el('video', { src: src, poster: poster || null, controls: '', autoplay: '', playsinline: '', preload: 'auto' });
        stage.appendChild(v); errorNote(v, stage);
        v.play && v.play().catch(function () {});
      }
      player.classList.add('is-open');
      document.body.style.overflow = 'hidden';
      player.querySelector('[data-player-close].adm-round').focus();
    }
    document.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-play]');
      if (btn) { e.preventDefault(); open(btn); return; }
      if (e.target.closest('[data-player-close]')) close();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && player.classList.contains('is-open')) { e.stopPropagation(); close(); }
    }, true);
  }

  /* Only one inline player sounds at a time */
  function stopInline(except) {
    document.querySelectorAll('.adm-media-card video, .adm-media-card audio, .adm-preview video, .adm-preview audio').forEach(function (m) {
      if (m !== except && !m.paused) m.pause();
    });
  }
  document.addEventListener('play', function (e) {
    if (e.target.closest && e.target.closest('.adm-media-card, .adm-preview')) stopInline(e.target);
  }, true);

  /* Library cards: show whether each file loads */
  document.querySelectorAll('.adm-media-card').forEach(function (card) {
    var m = card.querySelector('video, audio'), s = card.querySelector('.adm-media-status');
    if (!m || !s) return;
    m.addEventListener('loadedmetadata', function () {
      var d = Math.round(m.duration || 0);
      s.className = 'adm-media-status is-ok';
      s.textContent = 'Ready to play' + (d ? ' — ' + Math.floor(d / 60) + ':' + ('0' + d % 60).slice(-2) : '');
    });
    m.addEventListener('error', function () {
      s.className = 'adm-media-status is-bad';
      s.textContent = 'File could not be loaded — edit the item and check its media URL.';
      /* tell a missing file apart from a format this browser cannot decode */
      var src = m.currentSrc || m.getAttribute('src');
      if (src && src.indexOf(location.origin) === 0 && window.fetch) {
        fetch(src, { method: 'HEAD' }).then(function (r) {
          if (r.ok) s.textContent = 'The file is there, but this browser cannot play its format. Use an MP4 (H.264) video or MP3 audio.';
          else s.textContent = 'File not found (' + r.status + ') — edit the item and check its media URL.';
        }).catch(function () {});
      }
    });
  });

  /* ---------- add / edit form: live preview of the media URL ---------- */
  var urlInput = document.querySelector('[data-media-input]');
  var box = document.querySelector('[data-media-preview]');
  if (urlInput && box) {
    var typeSel = document.querySelector('select[name="type"]');
    var timer = null;
    function render(initial) {
      var raw = urlInput.value.trim();
      var src = initial ? (box.dataset.initial || resolve(raw)) : resolve(raw);
      box.innerHTML = '';
      if (!raw) { box.hidden = true; return; }
      box.hidden = false;
      var emb = embedOf(raw);
      var node;
      if (emb) node = el('iframe', { src: emb.replace('autoplay=1', 'autoplay=0'), allow: 'encrypted-media; picture-in-picture', allowfullscreen: '', title: 'Preview' });
      else if (isAudio(raw, typeSel && typeSel.value)) node = el('audio', { src: src, controls: '', preload: 'metadata' });
      else node = el('video', { src: src, controls: '', preload: 'metadata', playsinline: '' });
      box.appendChild(node);
      if (!emb) errorNote(node, box);
    }
    urlInput.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { render(false); }, 450); });
    if (typeSel) typeSel.addEventListener('change', function () { render(false); });
    render(true);
  }
})();
