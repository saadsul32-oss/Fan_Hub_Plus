<?php
/**
 * Admin Control Panel — redesigned to match the public site's cinematic theme
 * (void background, aurora glow, glass panels, Bebas display type, violet → magenta → cyan accents).
 * CRUD for every content type, moderation, users, media library, chatbot KB, feedback, analytics.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/entities.php';
require_once __DIR__ . '/actions.php';
ensure_schema();
$admin = require_admin_page();

if ($_SERVER['REQUEST_METHOD'] === 'POST') handle_admin_post($admin);

$NAV_GROUPS = [
  'Dashboard' => ['overview' => ['Overview', 'layout-dashboard'], 'analytics' => ['Analytics', 'chart-column']],
  'Library'   => ['categories' => ['Categories', 'folder-kanban'], 'content' => ['Content', 'file-text'], 'submissions' => ['Fan Submissions', 'inbox'],
                  'characters' => ['Characters', 'users'], 'media' => ['Media Library', 'image'], 'merch' => ['Merch & Releases', 'shopping-bag'], 'events' => ['Events', 'calendar-days']],
  'Community' => ['users' => ['Users', 'user-cog'], 'chatbot' => ['Chatbot', 'message-square'], 'feedback' => ['Feedback', 'star']],
  'System'    => ['site' => ['Site Control', 'globe'], 'settings' => ['Settings', 'settings']],
];
$NAV = array_merge(...array_values($NAV_GROUPS));
$SUBTITLES = [
  'overview' => 'Pulse of your Fandom Universe at a glance', 'analytics' => 'Engagement, traffic and chatbot activity',
  'categories' => 'The fandom universes shown in the explorer', 'content' => 'Articles, videos, trailers and audio',
  'submissions' => 'Review fan-written articles before they go live', 'characters' => 'Profiles shown in the spotlight and character grid',
  'media' => 'Every video, audio file and image used on the site', 'merch' => 'Merchandise showcase and upcoming releases', 'events' => 'Conventions, screenings and meetups',
  'users' => 'Members and administrators', 'chatbot' => 'ORA knowledge base, settings and conversations', 'feedback' => 'Bugs, suggestions and questions from fans',
  'site' => 'Homepage, SEO, socials, announcement bar and maintenance mode — full control of the live site',
  'settings' => 'Platform details and your admin account',
];
$tab = isset($NAV[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'overview';
$search = trim($_GET['q'] ?? '');
$entities = admin_entities();
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
$catNames = cat_options();

$pendingCount = (int)q_val("SELECT COUNT(*) FROM contents WHERE status='pending'");
$newFeedback = (int)q_val("SELECT COUNT(*) FROM feedback WHERE status='new'");
$unanswered = (int)q_val('SELECT COUNT(*) FROM chatbot_queries WHERE answered = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');

/** Rows of an entity, optionally filtered by the admin search box */
function entity_rows(array $ent, string $search): array
{
    $where = [];
    $params = [];
    if (!empty($ent['where'])) $where[] = $ent['where'];
    if ($search !== '') {
        $likes = [];
        foreach (array_keys($ent['columns']) as $i => $c) { $likes[] = "CAST(`$c` AS CHAR) LIKE :s$i"; $params["s$i"] = "%$search%"; }
        $where[] = '(' . implode(' OR ', $likes) . ')';
    }
    return q_all("SELECT * FROM `{$ent['table']}`" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY {$ent['order']}", $params);
}

function status_pill(string $val): string
{
    $map = [
        'published' => 'bg-emerald-400/10 text-emerald-300', 'active' => 'bg-emerald-400/10 text-emerald-300', 'resolved' => 'bg-emerald-400/10 text-emerald-300',
        'pending' => 'bg-amber/10 text-amber', 'reviewed' => 'bg-amber/10 text-amber',
        'rejected' => 'bg-red-400/10 text-red-300', 'blocked' => 'bg-red-400/10 text-red-300', 'new' => 'bg-magenta/15 text-magenta',
        'admin' => 'bg-violet/15 text-violet', 'user' => 'bg-white/5 text-white/60',
    ];
    return '<span class="adm-pill ' . ($map[$val] ?? 'bg-white/5 text-white/60') . '"><span class="h-1.5 w-1.5 rounded-full bg-current"></span>' . e(ucfirst($val)) . '</span>';
}

function cell(string $col, $val, array $catNames, array $row = []): string
{
    if ($col === 'category_id') return e($catNames[$val] ?? '—');
    if ($col === 'color') return '<span class="flex items-center gap-2"><span class="h-3.5 w-3.5 rounded-full ring-2 ring-white/10" style="background:' . e($val) . '"></span><code class="text-xs text-white/50">' . e($val) . '</code></span>';
    if (in_array($col, ['status', 'role'], true)) return status_pill((string)$val);
    if ($col === 'is_suggestion') return $val ? '<span class="adm-pill bg-cyan/10 text-cyan">Chip</span>' : '<span class="text-white/30">—</span>';
    if (in_array($col, ['release_date', 'event_date'], true)) return e(fmt_date($val));
    if ($col === 'last_login') return $val ? e(time_ago($val)) : '<span class="text-white/30">never</span>';
    if ($col === 'views') return '<span class="tabular-nums">' . number_format((int)$val) . '</span>';
    if ($col === 'type') return e(content_type_label((string)$val));
    if (in_array($col, ['name', 'title'], true) && !empty($row['image'])) {
        return '<span class="flex items-center gap-3"><img src="' . e(media($row['image'])) . '" alt="" loading="lazy" class="h-9 w-9 flex-none rounded-lg bg-white/5 object-cover ring-1 ring-white/10">'
             . '<span class="truncate font-medium text-white">' . e($val) . '</span></span>';
    }
    if (in_array($col, ['name', 'title'], true)) return '<span class="font-medium text-white">' . e($val) . '</span>';
    return e($val);
}

/** Button that opens the console's video / audio player for a content row */
function play_button(array $r, string $cls = 'adm-icon-btn is-play', string $label = ''): string
{
    $isAudio = ($r['type'] ?? '') === 'audio';
    return '<button type="button" class="' . e($cls) . '" title="' . ($isAudio ? 'Play audio' : 'Play video') . '"'
        . ' data-play="' . ($isAudio ? 'audio' : 'video') . '" data-src="' . e(media_src($r['media_url'])) . '"'
        . ' data-embed="' . e(embed_url($r['media_url']) ?? '') . '" data-poster="' . e(media($r['image'] ?? '')) . '"'
        . ' data-title="' . e($r['title'] ?? '') . '">' . icon('play', 14, '', ['fill' => 'currentColor']) . ($label !== '' ? ' ' . e($label) : '') . '</button>';
}

function row_actions(string $key, array $ent, array $r, string $tab): void
{ ?>
  <div class="flex justify-end gap-1">
    <?php if (!empty($r['media_url'])) echo play_button($r); ?>
    <a href="?<?= e(http_build_query(['tab' => $tab, 'edit' => $r[$ent['pk']], 'entity' => $key])) ?>" class="adm-icon-btn" title="Edit"><?= icon('pencil', 14) ?></a>
    <form method="post" onsubmit="return confirm('Delete this <?= e(strtolower($ent['label'])) ?>? This cannot be undone.')">
      <?= csrf_field() ?><input type="hidden" name="do" value="delete"><input type="hidden" name="tab" value="<?= e($tab) ?>">
      <input type="hidden" name="entity" value="<?= e($key) ?>"><input type="hidden" name="id" value="<?= (int)$r[$ent['pk']] ?>">
      <button class="adm-icon-btn is-danger" title="Delete"><?= icon('trash-2', 14) ?></button>
    </form>
  </div>
<?php }

function admin_table(string $key, array $ent, array $rows, array $catNames, string $tab, string $addLabel, string $title = ''): void
{ ?>
  <div class="adm-card overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
      <div>
        <?php if ($title): ?><p class="adm-eyebrow"><?= e($title) ?></p><?php endif; ?>
        <p class="text-sm text-white/60"><span class="font-display text-2xl leading-none text-white"><?= count($rows) ?></span> <?= count($rows) === 1 ? 'item' : 'items' ?></p>
      </div>
      <a href="?<?= e(http_build_query(['tab' => $tab, 'add' => $key])) ?>" class="adm-btn adm-btn-primary"><?= icon('plus', 14) ?> <?= e($addLabel) ?></a>
    </div>
    <div class="overflow-x-auto adm-scroll">
      <table class="adm-table w-full text-left text-sm">
        <thead>
          <tr>
            <?php foreach ($ent['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
            <th class="w-24"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <?php foreach (array_keys($ent['columns']) as $c): ?>
                <td class="max-w-xs truncate"><?= cell($c, $r[$c] ?? '', $catNames, $r) ?></td>
              <?php endforeach; ?>
              <td><?php row_actions($key, $ent, $r, $tab); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="99" class="py-14 text-center text-white/40">Nothing here yet — add the first one.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php }

$inputCls = 'adm-input';
$pageTitle = 'Admin Console — ' . setting('platform_name', 'FAN HUB PLUS');
$theme = 'dark';
$admCss = __DIR__ . '/assets/admin.css';
$extraHead = '<link rel="stylesheet" href="' . e(url('admin/assets/admin.css')) . '?v=' . (@filemtime($admCss) ?: 1) . '" />';
include __DIR__ . '/../includes/partials/head.php';
?>
<div class="adm-body relative min-h-screen">
  <div class="adm-aurora"></div>

  <div class="adm-shell">
    <!-- Sidebar -->
    <aside class="adm-rail" aria-label="Admin navigation">
      <a href="?tab=overview" class="adm-brand" title="Admin overview">
        <img src="<?= e(url('images/brand/logo-horizontal.png')) ?>" alt="FAN HUB PLUS" width="1020" height="160">
        <span class="adm-brand-tag">Admin</span>
      </a>
      <nav class="adm-nav">
        <?php foreach ($NAV_GROUPS as $group => $items): ?>
          <div class="adm-nav-group">
            <p class="adm-nav-head"><?= e($group) ?></p>
            <div class="space-y-0.5">
              <?php foreach ($items as $id => [$label, $ic]): ?>
                <a href="?tab=<?= $id ?>" class="adm-nav-link <?= $tab === $id ? 'is-active' : '' ?>"<?= $tab === $id ? ' aria-current="page"' : '' ?>>
                  <?= icon($ic, 16) ?> <?= e($label) ?>
                  <?php if ($id === 'submissions' && $pendingCount): ?><span class="adm-count is-amber"><?= $pendingCount ?></span><?php endif; ?>
                  <?php if ($id === 'feedback' && $newFeedback): ?><span class="adm-count is-pink"><?= $newFeedback ?></span><?php endif; ?>
                  <?php if ($id === 'chatbot' && $unanswered): ?><span class="adm-count is-cyan" title="Unanswered questions this week"><?= $unanswered ?></span><?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </nav>
      <div class="adm-me">
        <div class="adm-me-row">
          <span class="adm-avatar"><?= e(initials($admin['name'])) ?></span>
          <div class="min-w-0 flex-1">
            <p class="adm-me-name"><?= e($admin['name']) ?></p>
            <p class="adm-me-mail"><?= e($admin['email']) ?></p>
          </div>
          <a href="<?= e(url('admin/logout.php')) ?>" class="adm-icon-btn is-danger" title="Sign out"><?= icon('log-out', 15) ?></a>
        </div>
      </div>
    </aside>

    <div class="adm-main">
      <!-- Top bar -->
      <header class="adm-top">
        <div class="flex min-w-0 items-center gap-4">
          <a href="?tab=overview"><img src="<?= e(url('images/brand/logo-mark.png')) ?>" alt="FAN HUB PLUS" class="adm-top-logo"></a>
          <div class="min-w-0">
            <p class="adm-crumb">Admin <span aria-hidden="true">/</span> <b><?= e($NAV[$tab][0]) ?></b></p>
            <h1 class="adm-page-title truncate"><?= e($NAV[$tab][0]) ?></h1>
          </div>
        </div>
        <div class="adm-tools">
          <?php if (!in_array($tab, ['overview', 'analytics', 'settings', 'submissions'], true)): ?>
            <form method="get" class="adm-search hidden md:flex" role="search">
              <input type="hidden" name="tab" value="<?= e($tab) ?>">
              <?= icon('search', 14, 'text-white/40') ?>
              <input name="q" value="<?= e($search) ?>" placeholder="Search <?= e(strtolower($NAV[$tab][0])) ?>" aria-label="Search <?= e(strtolower($NAV[$tab][0])) ?>" />
              <?php if ($search !== ''): ?><a href="?tab=<?= e($tab) ?>" class="text-white/50 hover:text-white" title="Clear search"><?= icon('x', 13) ?></a><?php endif; ?>
            </form>
          <?php endif; ?>
          <a href="?tab=<?= $pendingCount ? 'submissions' : 'feedback' ?>" class="adm-round" title="<?= $pendingCount ?> pending submissions, <?= $newFeedback ?> new feedback">
            <?= icon('bell', 16) ?><?php if ($pendingCount + $newFeedback): ?><span class="dot"></span><?php endif; ?>
          </a>
          <a href="<?= e(url()) ?>" target="_blank" class="adm-btn adm-btn-ghost hidden sm:inline-flex"><?= icon('external-link', 14) ?> View site</a>
          <details class="adm-mobile relative">
            <summary class="adm-round list-none" aria-label="Menu"><?= icon('menu', 16) ?></summary>
            <div class="adm-mobile-panel">
              <?php foreach ($NAV_GROUPS as $group => $items): ?>
                <p class="adm-nav-head mt-2"><?= e($group) ?></p>
                <?php foreach ($items as $id => [$label, $ic]): ?>
                  <a href="?tab=<?= $id ?>" class="adm-nav-link <?= $tab === $id ? 'is-active' : '' ?>"><?= icon($ic, 15) ?> <?= e($label) ?></a>
                <?php endforeach; ?>
              <?php endforeach; ?>
              <div class="mt-3 border-t border-white/10 pt-3">
                <a href="<?= e(url()) ?>" class="adm-nav-link"><?= icon('external-link', 15) ?> View site</a>
                <a href="<?= e(url('admin/logout.php')) ?>" class="adm-nav-link"><?= icon('log-out', 15) ?> Sign out</a>
              </div>
            </div>
          </details>
        </div>
      </header>

      <main class="adm-content">
        <?php if ($tab === 'overview'):
          $hour = (int)date('G'); $greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening'); ?>
          <div class="adm-hello">
            <div>
              <h2><?= e($greet) ?>, <?= e(explode(' ', $admin['name'])[0]) ?></h2>
              <p><?= e(date('l, F j')) ?> —
                <?php if ($pendingCount || $newFeedback || $unanswered): ?>
                  <?= $pendingCount ?> submission<?= $pendingCount === 1 ? '' : 's' ?> to review, <?= $newFeedback ?> new feedback message<?= $newFeedback === 1 ? '' : 's' ?> and <?= $unanswered ?> chatbot question<?= $unanswered === 1 ? '' : 's' ?> <?= e(setting('chatbot_name', 'ORA')) ?> couldn't answer this week.
                <?php else: ?>everything is up to date.<?php endif; ?>
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <a href="?tab=content&add=content" class="adm-btn adm-btn-primary"><?= icon('plus', 14) ?> New content</a>
              <a href="?tab=characters&add=characters" class="adm-btn adm-btn-ghost"><?= icon('sparkles', 14) ?> Character</a>
              <a href="?tab=events&add=events" class="adm-btn adm-btn-ghost"><?= icon('calendar-plus', 14) ?> Event</a>
            </div>
          </div>
        <?php else: ?>
          <p class="adm-sub"><?= e($SUBTITLES[$tab] ?? '') ?></p>
        <?php endif; ?>
        <?php if ($flash): ?>
          <div class="adm-flash <?= $flash['ok'] ? 'is-ok' : 'is-bad' ?>" role="status">
            <?= icon($flash['ok'] ? 'circle-check' : 'circle-alert', 16) ?> <?= e($flash['msg']) ?>
          </div>
        <?php endif; ?>
        <?php if ($search !== ''): ?>
          <p class="mb-4 text-xs text-white/50">Results for “<span class="text-white"><?= e($search) ?></span>” · <a href="?tab=<?= e($tab) ?>" class="text-violet hover:underline">Clear search</a></p>
        <?php endif; ?>
        <?php include __DIR__ . '/tabs.php'; ?>
      </main>
    </div>
  </div>
</div>

<!-- Video / audio player -->
<div class="adm-player" id="adm-player" role="dialog" aria-modal="true" aria-labelledby="adm-player-title">
  <div class="adm-player-backdrop" data-player-close></div>
  <div class="adm-player-box">
    <div class="adm-player-close"><button type="button" class="adm-round" data-player-close aria-label="Close player"><?= icon('x', 16) ?></button></div>
    <div class="adm-player-stage" data-player-stage></div>
    <div class="adm-player-foot">
      <p id="adm-player-title" class="adm-title truncate text-2xl"></p>
      <a href="#" target="_blank" rel="noopener" class="adm-btn adm-btn-ghost" data-player-open><?= icon('external-link', 13) ?> Open file</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/form_modal.php'; ?>
<script src="<?= e(url('admin/assets/admin.js')) ?>?v=<?= @filemtime(__DIR__ . '/assets/admin.js') ?: 1 ?>"></script>
<script>
  /* live image preview in the add / edit form */
  document.addEventListener('change', function (e) {
    var f = e.target;
    if (f.type === 'file' && f.dataset.preview && f.files[0]) {
      var img = document.getElementById(f.dataset.preview);
      if (img) { img.src = URL.createObjectURL(f.files[0]); img.classList.remove('hidden'); }
    }
  });
  document.addEventListener('input', function (e) {
    var f = e.target;
    if (f.dataset && f.dataset.previewUrl) {
      var img = document.getElementById(f.dataset.previewUrl);
      if (img && /^(https?:\/\/|images\/|uploads\/)/.test(f.value)) { img.src = /^https?:/.test(f.value) ? f.value : <?= json_encode(BASE_URL) ?> + f.value; img.classList.remove('hidden'); }
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (document.getElementById('adm-player').classList.contains('is-open')) return;
      var c = document.querySelector('[data-modal-close]'); if (c) location.href = c.getAttribute('href');
    }
  });
</script>
</body>
</html>
