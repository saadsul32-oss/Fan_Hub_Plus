<?php
/**
 * Multi-level search, filter & sort across all content types (SRS: Fandom Content Explorer)
 * Filters: q, category, type, year, genre   Sort: popular | latest | alphabetical
 * Returns the filter bar + results as HTML (rendered with the original SearchOverlay markup).
 */
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: text/html; charset=utf-8');

$q        = trim(mb_substr($_GET['q'] ?? '', 0, 100));
$category = $_GET['category'] ?? 'All';
$type     = $_GET['type'] ?? 'All';
$year     = $_GET['year'] ?? '';
$genre    = $_GET['genre'] ?? '';
$sort     = $_GET['sort'] ?? 'Popular';
$authed   = is_logged_in();

$index = "
  SELECT 'content' AS src, c.content_id AS id, c.title, cat.name AS category, c.type AS kind, c.image,
         YEAR(c.release_date) AS yr, c.popularity AS pop, c.genre, c.release_date AS dt,
         CONCAT_WS(' ', c.title, c.excerpt, c.genre, cat.name) AS hay
    FROM contents c JOIN categories cat USING(category_id) WHERE c.status='published'
  UNION ALL
  SELECT 'character', ch.character_id, ch.name, cat.name, 'character', ch.image,
         YEAR(ch.created_at), LEAST(99, ROUND(ch.views/35)), '', DATE(ch.created_at),
         CONCAT_WS(' ', ch.name, ch.fandom_label, ch.role, ch.short_desc, cat.name)
    FROM characters ch JOIN categories cat USING(category_id)
  UNION ALL
  SELECT 'merch', m.item_id, m.name, cat.name, 'merch', m.image,
         YEAR(m.created_at), LEAST(99, ROUND(m.views/35)), m.item_type, DATE(m.created_at),
         CONCAT_WS(' ', m.name, m.fandom_label, m.item_type, m.tag, cat.name)
    FROM merchandise m JOIN categories cat USING(category_id)";

$where = [];
$params = [];
if ($q !== '')              { $where[] = 'hay LIKE :q'; $params['q'] = '%' . $q . '%'; }
if ($category !== 'All')    { $where[] = 'category = :cat'; $params['cat'] = $category; }
if ($type !== 'All')        { $where[] = 'kind = :kind'; $params['kind'] = strtolower($type); }
if ($year !== '' && ctype_digit($year)) { $where[] = 'yr = :yr'; $params['yr'] = (int)$year; }
if ($genre !== '')          { $where[] = 'genre = :genre'; $params['genre'] = $genre; }
$order = ['Popular' => 'pop DESC, title', 'Latest' => 'dt DESC, pop DESC', 'Alphabetical' => 'title ASC'][$sort] ?? 'pop DESC';

$sql = "SELECT * FROM ($index) idx" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $order LIMIT 60";
$results = q_all($sql, $params);
$total = count($results);
$visible = $authed ? $results : array_slice($results, 0, 6);

$categories = array_merge(['All'], array_column(categories(), 'name'));
$types = ['All' => 'All types', 'article' => 'Article', 'video' => 'Video', 'trailer' => 'Trailer', 'audio' => 'Audio', 'character' => 'Character', 'merch' => 'Merch'];
$years = array_column(q_all("SELECT DISTINCT YEAR(release_date) y FROM contents WHERE release_date IS NOT NULL AND status='published' ORDER BY y DESC"), 'y');
$genres = array_column(q_all("SELECT DISTINCT genre FROM contents WHERE genre <> '' AND status='published' ORDER BY genre"), 'genre');

$chipOn  = 'border-white bg-white text-black';
$chipOff = 'border-white/15 text-white/60 hover:border-white/40';
$labels = ['article' => 'Article', 'video' => 'Video', 'trailer' => 'Trailer', 'audio' => 'Audio', 'character' => 'Character', 'merch' => 'Merch'];
function open_for(array $r): string {
  if ($r['src'] === 'character') return 'character';
  if ($r['src'] === 'merch') return 'merch';
  return $r['kind'] === 'article' ? 'article' : 'media';
}
?>
<div class="flex flex-wrap items-center gap-2">
  <?= icon('sliders-horizontal', 14, 'mr-1 text-white/40') ?>
  <?php foreach ($categories as $c): ?>
    <button data-sf="category" data-value="<?= e($c) ?>" class="rounded-full border px-3.5 py-1.5 text-xs font-medium transition <?= $category === $c ? $chipOn : $chipOff ?>" data-cursor-hover><?= e($c) ?></button>
  <?php endforeach; ?>
  <span class="mx-2 hidden h-4 w-px bg-white/15 sm:block"></span>
  <?php foreach (['Popular', 'Latest', 'Alphabetical'] as $s): ?>
    <button data-sf="sort" data-value="<?= $s ?>" class="rounded-full px-3.5 py-1.5 text-xs font-medium transition <?= $sort === $s ? 'text-cyan' : 'text-white/40 hover:text-white/70' ?>" data-cursor-hover><?= $s ?></button>
  <?php endforeach; ?>
</div>

<div class="mt-3 flex flex-wrap items-center gap-2">
  <?php foreach ($types as $k => $l): ?>
    <button data-sf="type" data-value="<?= e($k) ?>" class="rounded-full px-3 py-1 text-[11px] font-medium uppercase tracking-wide transition <?= strtolower($type) === strtolower($k) ? 'bg-violet/20 text-violet' : 'text-white/40 hover:text-white/70' ?>" data-cursor-hover><?= e($l) ?></button>
  <?php endforeach; ?>
  <span class="mx-2 hidden h-4 w-px bg-white/15 sm:block"></span>
  <select data-sf-select="year" class="rounded-full border border-white/15 bg-transparent px-3 py-1 text-[11px] text-white/60 outline-none" aria-label="Release year">
    <option class="bg-surface" value="">Any year</option>
    <?php foreach ($years as $y): ?><option class="bg-surface" value="<?= (int)$y ?>" <?= (string)$year === (string)$y ? 'selected' : '' ?>><?= (int)$y ?></option><?php endforeach; ?>
  </select>
  <select data-sf-select="genre" class="rounded-full border border-white/15 bg-transparent px-3 py-1 text-[11px] text-white/60 outline-none" aria-label="Genre">
    <option class="bg-surface" value="">Any genre</option>
    <?php foreach ($genres as $g): ?><option class="bg-surface" value="<?= e($g) ?>" <?= $genre === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?>
  </select>
  <span class="ml-auto text-[11px] text-white/35"><?= $total ?> result<?= $total === 1 ? '' : 's' ?></span>
</div>

<div class="mt-8 min-h-0 flex-1 overflow-y-auto no-scrollbar" data-lenis-prevent>
  <?php if (!$visible): ?>
    <p class="mt-16 text-center text-white/40">No results found. Try a different search or filter.</p>
  <?php else: ?>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <?php foreach ($visible as $r): ?>
        <button data-open="<?= open_for($r) ?>" data-id="<?= (int)$r['id'] ?>" class="group flex items-center gap-4 rounded-xl border border-white/10 bg-white/[0.02] p-3 text-left transition hover:border-white/25 hover:bg-white/[0.06]" data-cursor-hover>
          <div class="h-14 w-14 flex-none overflow-hidden rounded-lg">
            <img src="<?= e(media($r['image'])) ?>" alt="<?= e($r['title']) ?>" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
          </div>
          <div class="min-w-0">
            <span class="text-[10px] font-semibold uppercase tracking-wide text-violet"><?= e($labels[$r['kind']] ?? $r['kind']) ?> · <?= e($r['category']) ?></span>
            <p class="truncate font-display text-base text-white"><?= e($r['title']) ?></p>
          </div>
        </button>
      <?php endforeach; ?>
    </div>
    <?php if (!$authed && $total > count($visible)): ?>
      <div class="mt-6 rounded-xl border border-white/10 bg-white/[0.02] p-5 text-center text-sm text-white/60">
        Showing <?= count($visible) ?> of <?= $total ?> results.
        <button data-action="auth" class="ml-1 font-semibold text-white underline-offset-2 hover:underline" data-cursor-hover>Sign in to see everything</button>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
