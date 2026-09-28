<?php
if (!isset($tab, $entities)) exit;
/* Tab bodies for the admin console (included from admin/index.php) */

// Views per category (content + characters + merch) — used by overview & analytics
$catViews = q_all("
  SELECT c.category_id, c.name, c.color,
    COALESCE((SELECT SUM(views) FROM contents WHERE category_id=c.category_id),0)
  + COALESCE((SELECT SUM(views) FROM characters WHERE category_id=c.category_id),0)
  + COALESCE((SELECT SUM(views) FROM merchandise WHERE category_id=c.category_id),0) AS views,
    (SELECT COUNT(*) FROM user_categories WHERE category_id=c.category_id) AS fans
  FROM categories c ORDER BY c.sort_order");
$maxViews = max(1, max(array_column($catViews, 'views') ?: [1]));

/** Section heading inside a tab */
$h = function (string $eyebrow, string $title, string $extra = ''): void {
    echo '<div class="mb-4 flex flex-wrap items-end justify-between gap-3"><div><p class="adm-eyebrow">' . e($eyebrow) . '</p><h3 class="adm-title mt-1 text-2xl">' . e($title) . '</h3></div>' . $extra . '</div>';
};

if ($tab === 'overview'):
  $stats = [
    ['Total users', (int)q_val('SELECT COUNT(*) FROM users'), '+' . (int)q_val('SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)') . ' this month', 'users', 'from-violet to-magenta'],
    ['Published', (int)q_val("SELECT COUNT(*) FROM contents WHERE status='published'"), '+' . (int)q_val("SELECT COUNT(*) FROM contents WHERE status='published' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)") . ' this week', 'file-text', 'from-cyan to-violet'],
    ['Characters', (int)q_val('SELECT COUNT(*) FROM characters'), number_format((int)q_val('SELECT COALESCE(SUM(views),0) FROM characters')) . ' profile views', 'sparkles', 'from-magenta to-amber'],
    ['Chatbot chats (7d)', (int)q_val('SELECT COUNT(*) FROM chatbot_queries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'), (int)q_val('SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY)') . ' active users', 'message-square', 'from-emerald-400 to-cyan'],
  ];
  $upcoming = q_all('SELECT e.*, cat.name AS category FROM events e JOIN categories cat USING(category_id) WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 4');
  $activity = q_all('SELECT a.*, u.name FROM activity_log a JOIN users u USING(user_id) ORDER BY a.created_at DESC LIMIT 6');
  $topChars = q_all('SELECT character_id, name, image, fandom_label, views FROM characters ORDER BY views DESC LIMIT 4');
?>
  <div class="space-y-6">
    <!-- Stats -->
    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
      <?php foreach ($stats as [$l, $v, $d, $ic, $grad]): ?>
        <div class="adm-card adm-card-hover p-5">
          <div class="flex items-start justify-between">
            <p class="adm-eyebrow"><?= e($l) ?></p>
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br <?= $grad ?> text-white shadow-lg"><?= icon($ic, 16) ?></span>
          </div>
          <p class="mt-3 font-display text-4xl leading-none text-white tabular-nums sm:text-5xl"><?= number_format($v) ?></p>
          <p class="mt-2 flex items-center gap-1 text-xs text-emerald-300"><?= icon('trending-up', 13) ?> <?= e($d) ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pendingCount || $newFeedback || $unanswered): ?>
      <div class="flex flex-wrap gap-3">
        <?php if ($pendingCount): ?><a href="?tab=submissions" class="flex items-center gap-2 rounded-full border border-amber/40 bg-amber/10 px-4 py-2 text-xs font-semibold text-amber transition hover:bg-amber/20"><?= icon('inbox', 14) ?> <?= $pendingCount ?> fan submission(s) awaiting review →</a><?php endif; ?>
        <?php if ($newFeedback): ?><a href="?tab=feedback" class="flex items-center gap-2 rounded-full border border-magenta/40 bg-magenta/10 px-4 py-2 text-xs font-semibold text-magenta transition hover:bg-magenta/20"><?= icon('star', 14) ?> <?= $newFeedback ?> new feedback message(s) →</a><?php endif; ?>
        <?php if ($unanswered): ?><a href="?tab=chatbot" class="flex items-center gap-2 rounded-full border border-cyan/40 bg-cyan/10 px-4 py-2 text-xs font-semibold text-cyan transition hover:bg-cyan/20"><?= icon('message-square', 14) ?> Teach ORA <?= $unanswered ?> new answer(s) →</a><?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <div class="adm-card p-6 xl:col-span-2">
        <?php $h('Engagement', 'Fandom traffic', '<a href="?tab=analytics" class="text-xs text-violet hover:underline">Full analytics →</a>'); ?>
        <div class="space-y-4">
          <?php foreach ($catViews as $f): ?>
            <div>
              <div class="mb-1.5 flex justify-between text-xs"><span class="flex items-center gap-2 text-white/75"><span class="h-2 w-2 rounded-full" style="background:<?= e($f['color']) ?>"></span><?= e($f['name']) ?></span><span class="tabular-nums text-white/45"><?= number_format((int)$f['views']) ?></span></div>
              <div class="h-2 w-full overflow-hidden rounded-full bg-white/5">
                <div class="h-full rounded-full" style="width:<?= max(3, round($f['views'] / $maxViews * 100)) ?>%;background:linear-gradient(90deg, <?= e($f['color']) ?>, <?= e($f['color']) ?>99);box-shadow:0 0 12px <?= e($f['color']) ?>66"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="adm-card p-6">
        <?php $h('Calendar', 'Upcoming events', '<a href="?tab=events" class="text-xs text-violet hover:underline">All →</a>'); ?>
        <ul class="space-y-3">
          <?php foreach ($upcoming as $ev): ?>
            <li class="flex gap-3 rounded-2xl border border-white/5 bg-white/[0.02] p-3">
              <span class="flex h-12 w-12 flex-none flex-col items-center justify-center rounded-xl bg-gradient-to-br from-violet/30 to-magenta/20 text-white">
                <span class="font-display text-xl leading-none"><?= e(date('d', strtotime($ev['event_date']))) ?></span>
                <span class="text-[9px] uppercase tracking-widest text-white/60"><?= e(date('M', strtotime($ev['event_date']))) ?></span>
              </span>
              <span class="min-w-0">
                <span class="block truncate text-sm font-medium text-white"><?= e($ev['title']) ?></span>
                <span class="block truncate text-xs text-white/45"><?= e($ev['venue']) ?> · <?= e($ev['city']) ?></span>
              </span>
            </li>
          <?php endforeach; ?>
          <?php if (!$upcoming): ?><li class="text-sm text-white/40">No upcoming events.</li><?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <div class="adm-card p-6 xl:col-span-2">
        <?php $h('Live feed', 'Recent member activity'); ?>
        <ul class="divide-y divide-white/5">
          <?php foreach ($activity as $a): ?>
            <li class="flex items-center gap-3 py-3">
              <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-white/5 text-[11px] font-semibold text-white/70"><?= e(initials($a['name'])) ?></span>
              <p class="min-w-0 flex-1 truncate text-sm text-white/70"><span class="font-medium text-white"><?= e($a['name']) ?></span> <span class="text-violet"><?= e(strtolower($a['action'])) ?></span> <?= e($a['detail']) ?></p>
              <span class="flex-none text-[11px] text-white/35"><?= e(time_ago($a['created_at'])) ?></span>
            </li>
          <?php endforeach; ?>
          <?php if (!$activity): ?><li class="py-6 text-sm text-white/40">No activity yet.</li><?php endif; ?>
        </ul>
      </div>
      <div class="adm-card p-6">
        <?php $h('Spotlight', 'Top characters', '<a href="?tab=characters" class="text-xs text-violet hover:underline">Manage →</a>'); ?>
        <ul class="space-y-3">
          <?php foreach ($topChars as $i => $c): ?>
            <li class="flex items-center gap-3">
              <span class="w-5 font-display text-xl text-white/30"><?= $i + 1 ?></span>
              <img src="<?= e(media($c['image'])) ?>" alt="" class="h-11 w-11 rounded-xl object-cover ring-1 ring-white/10">
              <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-white"><?= e($c['name']) ?></span><span class="block truncate text-[11px] text-cyan"><?= e($c['fandom_label']) ?></span></span>
              <span class="flex items-center gap-1 text-xs tabular-nums text-white/45"><?= icon('eye', 12) ?> <?= number_format((int)$c['views']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

<?php elseif ($tab === 'categories'): admin_table('categories', $entities['categories'], entity_rows($entities['categories'], $search), $catNames, $tab, 'Add category', 'Universes');

elseif ($tab === 'content'): admin_table('content', $entities['content'], entity_rows($entities['content'], $search), $catNames, $tab, 'Add content', 'Library');

elseif ($tab === 'events'): admin_table('events', $entities['events'], entity_rows($entities['events'], $search), $catNames, $tab, 'Add event', 'Calendar');

elseif ($tab === 'users'): admin_table('users', $entities['users'], entity_rows($entities['users'], $search), $catNames, $tab, 'Add user', 'Members');

elseif ($tab === 'characters'):
  $chars = entity_rows($entities['characters'], $search);
  $catColors = array_column(categories(), 'color', 'category_id');
  $inCarousel = count(array_filter($chars, fn($c) => (int)$c['in_carousel'] === 1));
  $filter = $_GET['show'] ?? '';
  if ($filter === 'spotlight') $chars = array_values(array_filter($chars, fn($c) => (int)$c['in_carousel'] === 1));
  if ($filter === 'hidden') $chars = array_values(array_filter($chars, fn($c) => (int)$c['in_carousel'] !== 1)); ?>
  <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap gap-2 text-xs">
      <?php foreach (['' => 'All', 'spotlight' => 'In spotlight', 'hidden' => 'Grid only'] as $k => $l): ?>
        <a href="?<?= e(http_build_query(['tab' => 'characters', 'show' => $k, 'q' => $search])) ?>" class="rounded-full border px-3.5 py-1.5 transition <?= $filter === $k ? 'border-white bg-white text-black' : 'border-white/15 text-white/60 hover:border-white/40 hover:text-white' ?>"><?= $l ?></a>
      <?php endforeach; ?>
      <span class="ml-1 self-center text-white/40"><?= $inCarousel ?> in the 3D spotlight (max 8 shown)</span>
    </div>
    <a href="?tab=characters&add=characters" class="adm-btn adm-btn-primary"><?= icon('plus', 14) ?> Add character</a>
  </div>
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($chars as $c): $col = $catColors[$c['category_id']] ?? '#8b5cf6'; ?>
      <div class="adm-card adm-card-hover group overflow-hidden">
        <div class="relative aspect-[4/5] overflow-hidden rounded-t-3xl">
          <img src="<?= e(media($c['image'])) ?>" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
          <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent"></div>
          <div class="absolute inset-x-0 top-0 flex items-start justify-between p-3">
            <span class="adm-pill bg-black/60 text-white/85 backdrop-blur"><span class="h-1.5 w-1.5 rounded-full" style="background:<?= e($col) ?>"></span><?= e($catNames[$c['category_id']] ?? '') ?></span>
            <?php if ((int)$c['in_carousel'] === 1): ?><span class="adm-pill bg-violet/80 text-white"><?= icon('sparkles', 10) ?> Spotlight</span><?php endif; ?>
          </div>
          <div class="absolute inset-x-0 bottom-0 p-4">
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-cyan"><?= e($c['fandom_label']) ?></p>
            <p class="mt-1 font-display text-3xl leading-none text-white"><?= e($c['name']) ?></p>
            <?php if ($c['role'] !== ''): ?><p class="mt-1 text-[10px] uppercase tracking-widest text-white/55"><?= e($c['role']) ?></p><?php endif; ?>
          </div>
        </div>
        <div class="flex items-center justify-between gap-2 px-4 py-3">
          <span class="flex items-center gap-1.5 text-xs tabular-nums text-white/50" title="<?= count(parse_timeline($c['timeline'])) ?> timeline entries · <?= count(parse_lines($c['related'])) ?> related media"><?= icon('eye', 13) ?> <?= number_format((int)$c['views']) ?> views</span>
          <div class="flex items-center gap-1">
            <a href="<?= e(url('?character=' . (int)$c['character_id'])) ?>" target="_blank" class="adm-icon-btn" title="View on site"><?= icon('external-link', 14) ?></a>
            <?php row_actions('characters', $entities['characters'], $c, 'characters'); ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <a href="?tab=characters&add=characters" class="flex min-h-[260px] flex-col items-center justify-center gap-3 rounded-3xl border border-dashed border-white/15 text-white/45 transition hover:border-violet/60 hover:text-white">
      <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white/5"><?= icon('plus', 20) ?></span>
      <span class="text-xs font-semibold uppercase tracking-[0.2em]">New character</span>
    </a>
  </div>
  <?php if (!$chars && $search !== ''): ?><p class="mt-6 text-sm text-white/45">No characters match “<?= e($search) ?>”.</p><?php endif; ?>

<?php elseif ($tab === 'merch'): ?>
  <div class="space-y-8">
    <?php admin_table('merch', $entities['merch'], entity_rows($entities['merch'], $search), $catNames, $tab, 'Add product', 'Merchandise showcase'); ?>
    <?php admin_table('releases', $entities['releases'], entity_rows($entities['releases'], $search), $catNames, $tab, 'Add release', 'Upcoming releases'); ?>
  </div>

<?php elseif ($tab === 'submissions'):
  $subs = q_all("SELECT c.*, cat.name AS category, u.name AS author, u.email FROM contents c JOIN categories cat USING(category_id)
                 LEFT JOIN users u ON u.user_id=c.submitted_by WHERE c.submitted_by IS NOT NULL ORDER BY c.status='pending' DESC, c.created_at DESC"); ?>
  <div class="space-y-4">
    <?php if (!$subs): ?><div class="adm-card p-10 text-center text-sm text-white/45">No fan submissions yet.</div><?php endif; ?>
    <?php foreach ($subs as $s): ?>
      <div class="adm-card p-5 sm:p-6 <?= $s['status'] === 'pending' ? 'ring-1 ring-amber/30' : '' ?>">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div class="flex min-w-0 gap-4">
            <?php if ($s['image']): ?><img src="<?= e(media($s['image'])) ?>" alt="" class="hidden h-20 w-28 flex-none rounded-xl object-cover ring-1 ring-white/10 sm:block"><?php endif; ?>
            <div class="min-w-0">
              <p class="adm-eyebrow"><?= e($s['category']) ?> · by <?= e($s['author'] ?? 'deleted user') ?> · <?= e(time_ago($s['created_at'])) ?></p>
              <p class="mt-1.5 text-lg font-semibold text-white"><?= e($s['title']) ?></p>
              <p class="mt-1 line-clamp-2 text-sm text-white/55"><?= e($s['excerpt']) ?></p>
            </div>
          </div>
          <?= status_pill($s['status']) ?>
        </div>
        <details class="mt-4">
          <summary class="cursor-pointer text-xs font-semibold uppercase tracking-[0.18em] text-white/45 hover:text-white">Read full submission</summary>
          <div class="mt-3 space-y-3 rounded-2xl border border-white/5 bg-black/30 p-5 text-sm leading-relaxed text-white/75">
            <?php foreach (paragraphs($s['body']) as $p): ?><p><?= nl2br(e($p)) ?></p><?php endforeach; ?>
          </div>
        </details>
        <div class="mt-4 flex flex-wrap gap-2">
          <?php foreach (['approve' => ['Approve & publish', 'adm-btn-primary', 'check'], 'reject' => ['Reject', 'adm-btn-ghost', 'x']] as $d => [$l, $c, $ic]):
            if (($d === 'approve' && $s['status'] === 'published') || ($d === 'reject' && $s['status'] === 'rejected')) continue; ?>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="moderate"><input type="hidden" name="tab" value="submissions">
              <input type="hidden" name="id" value="<?= (int)$s['content_id'] ?>"><input type="hidden" name="decision" value="<?= $d ?>">
              <button class="adm-btn <?= $c ?>"><?= icon($ic, 13) ?> <?= $l ?></button></form>
          <?php endforeach; ?>
          <a href="?tab=content&edit=<?= (int)$s['content_id'] ?>&entity=content" class="adm-btn adm-btn-ghost"><?= icon('pencil', 13) ?> Edit</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif ($tab === 'media'):
  $mediaParams = [];
  $mediaSql = 'SELECT * FROM media_files';
  if ($search !== '') { $mediaSql .= ' WHERE title LIKE ? OR tags LIKE ?'; $mediaParams = ["%$search%", "%$search%"]; }
  $mediaFiles = q_all($mediaSql . ' ORDER BY media_id DESC', $mediaParams);
  $editMedia = isset($_GET['media']) ? q_one('SELECT * FROM media_files WHERE media_id=?', [(int)$_GET['media']]) : null;
  $avParams = [];
  $avSql = "SELECT c.*, cat.name AS category FROM contents c LEFT JOIN categories cat USING(category_id) WHERE c.media_url IS NOT NULL AND c.media_url <> ''";
  if ($search !== '') { $avSql .= ' AND (c.title LIKE ? OR c.media_url LIKE ?)'; $avParams = ["%$search%", "%$search%"]; }
  $avItems = q_all($avSql . " ORDER BY c.type = 'audio', c.release_date DESC, c.content_id DESC", $avParams);
  $avVideos = array_values(array_filter($avItems, fn($x) => $x['type'] !== 'audio'));
  $avAudio  = array_values(array_filter($avItems, fn($x) => $x['type'] === 'audio')); ?>
  <?php foreach ([['Videos & trailers', $avVideos, 'film'], ['Audio', $avAudio, 'music-2']] as [$avTitle, $avList, $avIcon]): if (!$avList) continue; ?>
    <section class="mb-10">
      <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
          <p class="adm-eyebrow flex items-center gap-2"><?= icon($avIcon, 13) ?> <?= count($avList) ?> <?= count($avList) === 1 ? 'file' : 'files' ?> on the site</p>
          <h3 class="adm-title mt-1 text-3xl"><?= e($avTitle) ?></h3>
        </div>
        <a href="?tab=content&add=content" class="adm-btn adm-btn-ghost"><?= icon('plus', 13) ?> Add media</a>
      </div>
      <div class="adm-media-grid">
        <?php foreach ($avList as $av): $avEmbed = embed_url($av['media_url']); $avSrc = media_src($av['media_url']); ?>
          <div class="adm-card adm-media-card">
            <?php if ($avEmbed): ?>
              <div class="adm-media-audio"><img src="<?= e(media($av['image'])) ?>" alt="" loading="lazy">
                <span class="absolute inset-0 flex items-center justify-center"><?= play_button($av, 'adm-btn adm-btn-primary', 'Play') ?></span></div>
            <?php elseif ($av['type'] === 'audio'): ?>
              <div class="adm-media-audio"><img src="<?= e(media($av['image'])) ?>" alt="" loading="lazy"><audio src="<?= e($avSrc) ?>" controls preload="metadata"></audio></div>
            <?php else: ?>
              <video src="<?= e($avSrc) ?>" poster="<?= e(media($av['image'])) ?>" controls preload="metadata" playsinline></video>
            <?php endif; ?>
            <div class="adm-media-meta">
              <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                  <p class="adm-media-title"><?= e($av['title']) ?></p>
                  <p class="adm-media-sub"><b><?= e(content_type_label($av['type'])) ?></b> · <?= e($av['category'] ?? '') ?><?= $av['meta'] ? ' · ' . e($av['meta']) : '' ?></p>
                </div>
                <div class="flex flex-none items-center">
                  <?= play_button($av) ?>
                  <a href="?<?= e(http_build_query(['tab' => 'media', 'edit' => $av['content_id'], 'entity' => 'content'])) ?>" class="adm-icon-btn" title="Edit"><?= icon('pencil', 14) ?></a>
                </div>
              </div>
              <p class="adm-media-status"><?= $avEmbed ? 'Streams from ' . e(parse_url($av['media_url'], PHP_URL_HOST) ?: 'external player') : 'Checking file…' ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
  <div class="mb-4">
    <p class="adm-eyebrow flex items-center gap-2"><?= icon('image', 13) ?> Uploaded to the library</p>
    <h3 class="adm-title mt-1 text-3xl">Images</h3>
  </div>
  <form method="post" enctype="multipart/form-data" class="adm-card mb-6 grid grid-cols-1 items-end gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-5">
    <?= csrf_field() ?><input type="hidden" name="tab" value="media"><input type="hidden" name="do" value="<?= $editMedia ? 'media_update' : 'media_upload' ?>">
    <?php if ($editMedia): ?><input type="hidden" name="id" value="<?= (int)$editMedia['media_id'] ?>"><?php endif; ?>
    <div>
      <span class="adm-label"><?= $editMedia ? 'Editing media' : 'Image file' ?></span>
      <?php if ($editMedia): ?><img src="<?= e(media($editMedia['file_path'])) ?>" class="mt-2 h-11 w-11 rounded-xl object-cover" alt=""><?php else: ?>
      <label class="adm-input flex cursor-pointer items-center gap-2 text-white/55 hover:text-white"><?= icon('upload', 14) ?> <span data-file-label class="truncate">Choose image…</span>
        <input type="file" name="file" accept="image/*" required class="hidden" onchange="this.previousElementSibling.textContent=this.files[0]?this.files[0].name:'Choose image…'"></label><?php endif; ?>
    </div>
    <label class="adm-label">Title<input name="title" value="<?= e($editMedia['title'] ?? '') ?>" class="adm-input normal-case tracking-normal"></label>
    <label class="adm-label">Tags (comma separated)<input name="tags" value="<?= e($editMedia['tags'] ?? '') ?>" class="adm-input normal-case tracking-normal"></label>
    <label class="adm-label">Category
      <select name="category_id" class="adm-input normal-case tracking-normal"><option value="0">— none —</option>
        <?php foreach ($catNames as $id => $n): ?><option value="<?= $id ?>" <?= ($editMedia['category_id'] ?? 0) == $id ? 'selected' : '' ?>><?= e($n) ?></option><?php endforeach; ?>
      </select></label>
    <div class="flex gap-2">
      <button class="adm-btn adm-btn-primary flex-1 py-3!"><?= icon($editMedia ? 'check' : 'upload', 14) ?> <?= $editMedia ? 'Save' : 'Upload' ?></button>
      <?php if ($editMedia): ?><a href="?tab=media" class="adm-btn adm-btn-ghost py-3!">Cancel</a><?php endif; ?>
    </div>
  </form>
  <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
    <?php foreach ($mediaFiles as $m): ?>
      <div class="group relative aspect-square overflow-hidden rounded-2xl border border-white/10">
        <img src="<?= e(media($m['file_path'])) ?>" alt="<?= e($m['title']) ?>" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 to-transparent p-3">
          <p class="truncate text-xs font-medium text-white"><?= e($m['title']) ?></p>
          <p class="truncate text-[10px] text-cyan"><?= e($catNames[$m['category_id']] ?? '') ?> <?= e($m['tags']) ?></p>
        </div>
        <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/60 opacity-0 backdrop-blur-sm transition group-hover:opacity-100">
          <button type="button" onclick="navigator.clipboard&&navigator.clipboard.writeText(<?= e(json_encode($m['file_path'])) ?>);this.title='Copied!'" class="rounded-full bg-white/15 p-2.5 text-white hover:bg-white/25" title="Copy path"><?= icon('copy', 14) ?></button>
          <a href="?tab=media&media=<?= (int)$m['media_id'] ?>" class="rounded-full bg-white/15 p-2.5 text-white hover:bg-white/25" title="Edit details"><?= icon('pencil', 14) ?></a>
          <form method="post" onsubmit="return confirm('Remove this image from the library?')"><?= csrf_field() ?>
            <input type="hidden" name="tab" value="media"><input type="hidden" name="do" value="media_delete"><input type="hidden" name="id" value="<?= (int)$m['media_id'] ?>">
            <button class="rounded-full bg-white/15 p-2.5 text-white hover:bg-red-500/60" title="Delete"><?= icon('trash-2', 14) ?></button></form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$mediaFiles): ?><div class="adm-card p-10 text-center text-sm text-white/45">The media library is empty — upload the first image above.</div><?php endif; ?>

<?php elseif ($tab === 'chatbot'):
  $total    = (int)q_val('SELECT COUNT(*) FROM chatbot_queries');
  $week     = (int)q_val('SELECT COUNT(*) FROM chatbot_queries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
  $answered = (int)q_val('SELECT COUNT(*) FROM chatbot_queries WHERE answered = 1');
  $rated    = q_one('SELECT COUNT(helpful) n, COALESCE(SUM(helpful),0) up FROM chatbot_queries');
  $rate     = $total ? round($answered / $total * 100) : 100;
  $helpful  = $rated['n'] ? round($rated['up'] / $rated['n'] * 100) . '%' : '—';
  $missed   = q_all("SELECT message, COUNT(*) n, MAX(created_at) last FROM chatbot_queries WHERE answered = 0 GROUP BY message ORDER BY last DESC LIMIT 8");
  $intents  = q_all('SELECT intent, COUNT(*) n FROM chatbot_queries GROUP BY intent ORDER BY n DESC LIMIT 8');
  $maxIntent = max(1, max(array_column($intents, 'n') ?: [1]));
  $queries  = q_all('SELECT q.*, u.username FROM chatbot_queries q LEFT JOIN users u USING(user_id) ORDER BY q.query_id DESC LIMIT 12');
  $aiKey    = CHAT_AI_KEY !== '';
  $aiOn     = setting('chatbot_ai', '0') === '1'; ?>
  <div class="space-y-6">
    <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
      <?php foreach ([['Conversations', number_format($total), 'message-square'], ['This week', number_format($week), 'activity'], ['Answer rate', $rate . '%', 'circle-check'], ['Rated helpful', $helpful, 'thumbs-up']] as [$l, $v, $ic]): ?>
        <div class="adm-card p-5">
          <div class="flex items-center justify-between"><p class="adm-eyebrow"><?= e($l) ?></p><span class="text-violet"><?= icon($ic, 16) ?></span></div>
          <p class="mt-3 font-display text-4xl leading-none text-white"><?= e($v) ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <!-- Settings -->
      <form method="post" class="adm-card space-y-5 p-6"><?= csrf_field() ?><input type="hidden" name="tab" value="chatbot"><input type="hidden" name="do" value="chatbot_settings">
        <?php $h('Personality', 'Assistant settings'); ?>
        <label class="adm-label">Assistant name<input name="chatbot_name" maxlength="30" value="<?= e(setting('chatbot_name', 'ORA')) ?>" class="adm-input normal-case tracking-normal"></label>
        <label class="adm-label">Welcome message<textarea name="chatbot_welcome" rows="3" maxlength="400" class="adm-input normal-case leading-relaxed tracking-normal"><?= e(setting('chatbot_welcome')) ?></textarea></label>
        <label class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3">
          <span>
            <span class="block text-sm font-medium text-white">AI answers (Claude)</span>
            <span class="block text-[11px] <?= $aiKey ? 'text-emerald-300' : 'text-white/40' ?>"><?= $aiKey ? 'API key detected — ' . e(CHAT_AI_MODEL) : 'Add CHAT_AI_KEY in includes/config.php to enable' ?></span>
          </span>
          <span class="adm-switch"><input type="checkbox" name="chatbot_ai" value="1" <?= $aiOn ? 'checked' : '' ?> <?= $aiKey ? '' : 'disabled' ?>><span></span></span>
        </label>
        <p class="text-[11px] leading-relaxed text-white/40">Without AI, <?= e(setting('chatbot_name', 'ORA')) ?> still answers from the knowledge base below plus live site data — characters, content, events, releases and merch.</p>
        <button class="adm-btn adm-btn-primary w-full"><?= icon('check', 14) ?> Save settings</button>
      </form>

      <!-- Unanswered -->
      <div class="adm-card p-6 xl:col-span-2">
        <?php $h('Learning queue', 'Questions ORA couldn’t answer'); ?>
        <ul class="divide-y divide-white/5">
          <?php foreach ($missed as $m):
            $kw = implode(', ', array_slice(array_values(array_unique(array_filter(preg_split('/[^\p{L}\p{N}-]+/u', mb_strtolower($m['message'])), fn($w) => mb_strlen($w) > 3))), 0, 4)); ?>
            <li class="flex flex-wrap items-center gap-3 py-3">
              <p class="min-w-0 flex-1 text-sm text-white/80">“<?= e($m['message']) ?>” <span class="ml-1 text-[11px] text-white/35"><?= (int)$m['n'] ?>× · <?= e(time_ago($m['last'])) ?></span></p>
              <a href="?<?= e(http_build_query(['tab' => 'chatbot', 'add' => 'faq', 'prefill' => ['question' => '', 'keywords' => $kw]])) ?>" class="adm-btn adm-btn-ghost py-1.5!"><?= icon('wand-sparkles', 13) ?> Teach answer</a>
            </li>
          <?php endforeach; ?>
          <?php if (!$missed): ?><li class="py-8 text-center text-sm text-white/40"><?= icon('circle-check', 16, 'mb-2 inline text-emerald-300') ?><br>Nothing in the queue — ORA answered everything.</li><?php endif; ?>
        </ul>
        <?php if ($intents): ?>
          <p class="adm-eyebrow mb-3 mt-6">What fans ask about</p>
          <div class="grid grid-cols-1 gap-x-6 gap-y-2.5 sm:grid-cols-2">
            <?php foreach ($intents as $it): ?>
              <div>
                <div class="mb-1 flex justify-between text-xs"><span class="capitalize text-white/70"><?= e($it['intent']) ?></span><span class="text-white/40"><?= (int)$it['n'] ?></span></div>
                <div class="h-1.5 rounded-full bg-white/5"><div class="h-full rounded-full bg-gradient-to-r from-violet to-cyan" style="width:<?= max(4, round($it['n'] / $maxIntent * 100)) ?>%"></div></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php admin_table('faq', $entities['faq'], entity_rows($entities['faq'], $search), $catNames, $tab, 'Add knowledge entry', 'Knowledge base'); ?>

    <div class="adm-card p-6">
      <?php $h('Transcript', 'Recent conversations'); ?>
      <div class="space-y-4">
        <?php foreach ($queries as $qq): ?>
          <div class="rounded-2xl border border-white/5 bg-white/[0.02] p-4">
            <div class="flex flex-wrap items-center gap-2 text-[11px] text-white/40">
              <span class="font-semibold text-violet">@<?= e($qq['username'] ?? 'visitor') ?></span> · <?= e(time_ago($qq['created_at'])) ?>
              <span class="adm-pill bg-white/5 text-white/55"><?= e($qq['intent'] ?? 'faq') ?></span>
              <?php if (isset($qq['answered']) && !(int)$qq['answered']): ?><span class="adm-pill bg-amber/10 text-amber">unanswered</span><?php endif; ?>
              <?php if (isset($qq['helpful']) && $qq['helpful'] !== null): ?><span class="<?= (int)$qq['helpful'] ? 'text-emerald-300' : 'text-red-300' ?>"><?= icon((int)$qq['helpful'] ? 'thumbs-up' : 'thumbs-down', 12) ?></span><?php endif; ?>
            </div>
            <p class="mt-1.5 text-sm text-white"><?= e($qq['message']) ?></p>
            <p class="mt-1 line-clamp-2 text-xs text-white/50"><span class="text-cyan"><?= e(setting('chatbot_name', 'ORA')) ?>:</span> <?= e(str_replace('**', '', $qq['response'])) ?></p>
          </div>
        <?php endforeach; ?>
        <?php if (!$queries): ?><p class="text-sm text-white/40">No conversations yet.</p><?php endif; ?>
      </div>
    </div>
  </div>

<?php elseif ($tab === 'feedback'):
  $fbParams = [];
  $fbSql = 'SELECT f.*, u.username FROM feedback f LEFT JOIN users u USING(user_id)';
  $typeFilter = $_GET['type'] ?? '';
  if (in_array($typeFilter, ['bug', 'suggestion', 'query'], true)) { $fbSql .= ' WHERE f.type=?'; $fbParams[] = $typeFilter; }
  $fb = q_all($fbSql . " ORDER BY f.status='new' DESC, f.created_at DESC", $fbParams); ?>
  <div class="mb-5 flex flex-wrap gap-2 text-xs">
    <?php foreach (['' => 'All', 'bug' => 'Bugs', 'suggestion' => 'Suggestions', 'query' => 'Queries'] as $k => $l): ?>
      <a href="?tab=feedback&type=<?= $k ?>" class="rounded-full border px-3.5 py-1.5 transition <?= $typeFilter === $k ? 'border-white bg-white text-black' : 'border-white/15 text-white/60 hover:border-white/40 hover:text-white' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
  <div class="space-y-3">
    <?php foreach ($fb as $f): ?>
      <div class="adm-card flex flex-wrap items-start justify-between gap-4 p-5 <?= $f['status'] === 'new' ? 'ring-1 ring-magenta/30' : '' ?>">
        <div class="flex min-w-0 flex-1 gap-4">
          <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full <?= ['bug' => 'bg-red-400/10 text-red-300', 'suggestion' => 'bg-cyan/10 text-cyan', 'query' => 'bg-amber/10 text-amber'][$f['type']] ?>"><?= icon(['bug' => 'circle-alert', 'suggestion' => 'sparkles', 'query' => 'circle-help'][$f['type']], 16) ?></span>
          <div class="min-w-0">
            <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-white">@<?= e($f['username'] ?? $f['name']) ?>
              <span class="adm-pill bg-white/5 text-white/60"><?= e($f['type']) ?></span><?= status_pill($f['status']) ?></p>
            <p class="mt-1.5 text-sm text-white/70"><?= e($f['message']) ?></p>
            <p class="mt-1.5 text-xs text-white/35"><?= e($f['email']) ?><?= $f['page'] ? ' · page: ' . e($f['page']) : '' ?> · <?= e(time_ago($f['created_at'])) ?></p>
          </div>
        </div>
        <div class="flex flex-none flex-col items-end gap-3">
          <?php if ($f['rating']): ?><div class="flex gap-0.5 text-amber"><?php for ($j = 0; $j < 5; $j++) echo icon('star', 14, $j < $f['rating'] ? '' : 'opacity-25', ['fill' => 'currentColor']); ?></div><?php endif; ?>
          <div class="flex items-center gap-2">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="tab" value="feedback"><input type="hidden" name="do" value="feedback_status"><input type="hidden" name="id" value="<?= (int)$f['feedback_id'] ?>">
              <select name="status" onchange="this.form.submit()" class="adm-input mt-0! cursor-pointer py-1.5! text-xs">
                <?php foreach (['new', 'reviewed', 'resolved'] as $st): ?><option value="<?= $st ?>" <?= $f['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option><?php endforeach; ?>
              </select></form>
            <form method="post" onsubmit="return confirm('Delete this feedback?')"><?= csrf_field() ?><input type="hidden" name="tab" value="feedback"><input type="hidden" name="do" value="feedback_delete"><input type="hidden" name="id" value="<?= (int)$f['feedback_id'] ?>">
              <button class="adm-icon-btn is-danger" title="Delete"><?= icon('trash-2', 14) ?></button></form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (!$fb): ?><div class="adm-card p-10 text-center text-sm text-white/45">No feedback yet.</div><?php endif; ?>
  </div>

<?php elseif ($tab === 'analytics'):
  $chatDays = q_all("SELECT DATE(created_at) d, COUNT(*) n FROM chatbot_queries WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY DATE(created_at)");
  $chatMap = array_column($chatDays, 'n', 'd');
  $maxChat = max(1, max($chatMap ?: [1]));
  $topItems = q_all("SELECT title, 'Content' k, views FROM contents WHERE status='published' UNION ALL SELECT name, 'Character', views FROM characters UNION ALL SELECT name, 'Merch', views FROM merchandise ORDER BY views DESC LIMIT 8");
  $maxFans = max(1, max(array_column($catViews, 'fans') ?: [1]));
  $kpis = [
    ['Active (24h)', q_val('SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 1 DAY)'), 'activity'],
    ['Active (30d)', q_val('SELECT COUNT(*) FROM users WHERE last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY)'), 'users'],
    ['Chatbot chats', q_val('SELECT COUNT(*) FROM chatbot_queries'), 'message-square'],
    ['Bookmarks', q_val('SELECT COUNT(*) FROM bookmarks'), 'bookmark'],
    ['Ratings', q_val('SELECT COUNT(*) FROM ratings'), 'star'],
    ['Event saves', q_val('SELECT COUNT(*) FROM event_saves'), 'calendar-days'],
  ]; ?>
  <div class="space-y-6">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
      <?php foreach ($kpis as [$l, $v, $ic]): ?>
        <div class="adm-card p-4"><div class="flex items-center justify-between"><p class="adm-eyebrow tracking-[0.2em]!"><?= e($l) ?></p><span class="text-violet"><?= icon($ic, 14) ?></span></div><p class="mt-2 font-display text-4xl leading-none text-white"><?= number_format((int)$v) ?></p></div>
      <?php endforeach; ?>
    </div>
    <div class="adm-card p-6">
      <?php $h('Traffic', 'Views by fandom'); ?>
      <div class="flex h-64 items-end gap-3">
        <?php foreach ($catViews as $f): ?>
          <div class="group flex h-full flex-1 flex-col items-center justify-end gap-2" title="<?= number_format((int)$f['views']) ?> views">
            <span class="text-[10px] tabular-nums text-white/0 transition group-hover:text-white/70"><?= number_format((int)$f['views']) ?></span>
            <div class="w-full rounded-t-xl transition-all duration-500 group-hover:brightness-125" style="height:<?= max(4, round($f['views'] / $maxViews * 100)) ?>%;background:linear-gradient(180deg, <?= e($f['color']) ?>, <?= e($f['color']) ?>33);box-shadow:0 0 24px <?= e($f['color']) ?>44"></div>
            <span class="truncate text-[10px] uppercase tracking-wider text-white/45"><?= e($f['name']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <div class="adm-card p-6">
        <?php $h('Chatbot', 'Last 7 days'); ?>
        <div class="flex h-44 items-end gap-2">
          <?php for ($i = 6; $i >= 0; $i--): $d = date('Y-m-d', strtotime("-$i day")); $n = (int)($chatMap[$d] ?? 0); ?>
            <div class="flex h-full flex-1 flex-col items-center justify-end gap-1" title="<?= $n ?> messages">
              <span class="text-[10px] text-white/50"><?= $n ?></span>
              <div class="w-full rounded-t-lg bg-gradient-to-t from-violet/40 to-violet" style="height:<?= max(3, round($n / $maxChat * 100)) ?>%"></div>
              <span class="text-[10px] uppercase text-white/40"><?= date('D', strtotime($d)) ?></span>
            </div>
          <?php endfor; ?>
        </div>
      </div>
      <div class="adm-card p-6">
        <?php $h('Community', 'Favourite fandoms'); ?>
        <div class="space-y-3">
          <?php $sorted = $catViews; usort($sorted, fn($a, $b) => $b['fans'] <=> $a['fans']); foreach ($sorted as $f): ?>
            <div>
              <div class="mb-1 flex justify-between text-xs text-white/65"><span><?= e($f['name']) ?></span><span class="text-white/40"><?= (int)$f['fans'] ?> fans</span></div>
              <div class="h-1.5 w-full rounded-full bg-white/5"><div class="h-full rounded-full" style="width:<?= max(3, round($f['fans'] / $maxFans * 100)) ?>%;background:<?= e($f['color']) ?>"></div></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="adm-card p-6">
        <?php $h('Leaderboard', 'Most viewed'); ?>
        <ul class="space-y-3">
          <?php foreach ($topItems as $i => $ti): ?>
            <li class="flex items-center gap-3 text-sm">
              <span class="w-5 font-display text-lg text-white/30"><?= $i + 1 ?></span>
              <span class="min-w-0 flex-1 truncate text-white/80"><span class="mr-1.5 text-[9px] uppercase tracking-widest text-cyan"><?= e($ti['k']) ?></span><?= e($ti['title']) ?></span>
              <span class="flex-none text-xs tabular-nums text-white/45"><?= number_format((int)$ti['views']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

<?php elseif ($tab === 'site'):
  $maintOn = setting('maintenance_mode', '0') === '1';
  $annOn   = setting('announcement_enabled', '0') === '1'; ?>
  <form method="post" enctype="multipart/form-data" class="space-y-6">
    <?= csrf_field() ?><input type="hidden" name="tab" value="site"><input type="hidden" name="do" value="site_settings">

    <div class="adm-card space-y-1 border-l-4 <?= $maintOn ? 'border-l-red-400' : 'border-l-white/10' ?> p-6">
      <label class="flex items-center justify-between gap-3">
        <span>
          <span class="block font-display text-xl text-white">Maintenance mode</span>
          <span class="block text-[11px] <?= $maintOn ? 'text-red-300' : 'text-white/40' ?>"><?= $maintOn ? 'The live site is currently showing this message to everyone except admins.' : 'Take the public site offline while you make changes — admins still have full access.' ?></span>
        </span>
        <span class="adm-switch"><input type="checkbox" name="maintenance_mode" value="1" <?= $maintOn ? 'checked' : '' ?>><span></span></span>
      </label>
      <label class="adm-label mt-3">Message shown to visitors<textarea name="maintenance_message" rows="2" maxlength="500" class="adm-input normal-case leading-relaxed tracking-normal"><?= e(setting('maintenance_message')) ?></textarea></label>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <div class="adm-card space-y-5 p-6">
        <?php $h('Homepage', 'Hero section'); ?>
        <label class="adm-label">Eyebrow badge<input name="hero_eyebrow" maxlength="80" value="<?= e(setting('hero_eyebrow')) ?>" class="adm-input normal-case tracking-normal"></label>
        <label class="adm-label">Subheadline<input name="hero_subtitle" maxlength="80" value="<?= e(setting('hero_subtitle')) ?>" class="adm-input normal-case tracking-normal"></label>
        <label class="adm-label">Background image
          <input type="file" name="hero_image" accept="image/*" data-preview="hero-preview" class="adm-input normal-case tracking-normal">
        </label>
        <div class="flex items-center gap-3">
          <img id="hero-preview" src="<?= e(media(setting('hero_image'))) ?>" class="h-14 w-24 flex-none rounded-lg bg-white/5 object-cover ring-1 ring-white/10 <?= setting('hero_image') === '' ? 'hidden' : '' ?>">
          <?php if (setting('hero_image') !== ''): ?><label class="flex items-center gap-2 text-xs text-white/50"><input type="checkbox" name="hero_image_clear" value="1"> Remove custom image (use default)</label><?php endif; ?>
        </div>
      </div>

      <div class="adm-card space-y-5 p-6">
        <?php $h('Discoverability', 'SEO'); ?>
        <label class="adm-label">Meta title<input name="seo_title" maxlength="160" value="<?= e(setting('seo_title')) ?>" class="adm-input normal-case tracking-normal"></label>
        <label class="adm-label">Meta description<textarea name="seo_description" rows="3" maxlength="320" class="adm-input normal-case leading-relaxed tracking-normal"><?= e(setting('seo_description')) ?></textarea></label>
      </div>

      <div class="adm-card space-y-5 p-6">
        <?php $h('Footer', 'Tagline & socials'); ?>
        <label class="adm-label">Footer tagline<input name="footer_tagline" maxlength="160" value="<?= e(setting('footer_tagline')) ?>" class="adm-input normal-case tracking-normal"></label>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <label class="adm-label">Twitter / X<input name="social_twitter" type="url" placeholder="https://…" value="<?= e(setting('social_twitter')) ?>" class="adm-input normal-case tracking-normal"></label>
          <label class="adm-label">Instagram<input name="social_instagram" type="url" placeholder="https://…" value="<?= e(setting('social_instagram')) ?>" class="adm-input normal-case tracking-normal"></label>
          <label class="adm-label">YouTube<input name="social_youtube" type="url" placeholder="https://…" value="<?= e(setting('social_youtube')) ?>" class="adm-input normal-case tracking-normal"></label>
          <label class="adm-label">Discord<input name="social_discord" type="url" placeholder="https://…" value="<?= e(setting('social_discord')) ?>" class="adm-input normal-case tracking-normal"></label>
          <label class="adm-label">TikTok<input name="social_tiktok" type="url" placeholder="https://…" value="<?= e(setting('social_tiktok')) ?>" class="adm-input normal-case tracking-normal"></label>
        </div>
      </div>

      <div class="adm-card space-y-5 p-6">
        <?php $h('Site-wide', 'Announcement banner'); ?>
        <label class="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3">
          <span class="block text-sm font-medium text-white">Show banner above the navbar</span>
          <span class="adm-switch"><input type="checkbox" name="announcement_enabled" value="1" <?= $annOn ? 'checked' : '' ?>><span></span></span>
        </label>
        <label class="adm-label">Banner text<input name="announcement_text" maxlength="160" value="<?= e(setting('announcement_text')) ?>" class="adm-input normal-case tracking-normal"></label>
        <label class="adm-label">Link (optional)<input name="announcement_link" type="url" placeholder="https://…" value="<?= e(setting('announcement_link')) ?>" class="adm-input normal-case tracking-normal"></label>
      </div>
    </div>

    <button class="adm-btn adm-btn-primary"><?= icon('check', 14) ?> Save all site control settings</button>
  </form>

<?php elseif ($tab === 'settings'): ?>
  <div class="grid max-w-4xl grid-cols-1 gap-6 lg:grid-cols-2">
    <form method="post" class="adm-card space-y-5 p-6"><?= csrf_field() ?><input type="hidden" name="tab" value="settings"><input type="hidden" name="do" value="settings">
      <?php $h('Platform', 'General'); ?>
      <label class="adm-label">Platform name<input name="platform_name" value="<?= e(setting('platform_name', 'Fan Hub Plus')) ?>" class="adm-input normal-case tracking-normal" /></label>
      <label class="adm-label">Support email<input name="support_email" type="email" value="<?= e(setting('support_email')) ?>" class="adm-input normal-case tracking-normal" /></label>
      <button class="adm-btn adm-btn-primary"><?= icon('check', 14) ?> Save changes</button>
    </form>
    <form method="post" class="adm-card space-y-5 p-6"><?= csrf_field() ?><input type="hidden" name="tab" value="settings"><input type="hidden" name="do" value="password">
      <?php $h('Security', 'Change password'); ?>
      <label class="adm-label">Current password<input name="current" type="password" required autocomplete="current-password" class="adm-input" /></label>
      <label class="adm-label">New password<input name="new" type="password" required minlength="8" autocomplete="new-password" placeholder="At least 8 characters" class="adm-input normal-case tracking-normal" /></label>
      <button class="adm-btn adm-btn-ghost"><?= icon('key-round', 14) ?> Update password</button>
    </form>
  </div>
<?php endif;
