<?php
/* Personalised dashboard (converted from Dashboard.tsx) — everything is live data for the logged-in user. Expects $user */
$uid = (int)$user['user_id'];
$favIds = array_map('intval', array_column(q_all('SELECT category_id FROM user_categories WHERE user_id=?', [$uid]), 'category_id'));
$saved = q_all("
  SELECT b.bookmark_id, b.item_type, b.item_id, b.note, b.created_at, c.title AS title, c.image, c.type AS sub FROM bookmarks b JOIN contents c ON b.item_type='content' AND c.content_id=b.item_id WHERE b.user_id=:u1
  UNION ALL
  SELECT b.bookmark_id, b.item_type, b.item_id, b.note, b.created_at, ch.name, ch.image, 'character' FROM bookmarks b JOIN characters ch ON b.item_type='character' AND ch.character_id=b.item_id WHERE b.user_id=:u2
  UNION ALL
  SELECT b.bookmark_id, b.item_type, b.item_id, b.note, b.created_at, m.name, m.image, 'merch' FROM bookmarks b JOIN merchandise m ON b.item_type='merch' AND m.item_id=b.item_id WHERE b.user_id=:u3
  ORDER BY created_at DESC", ['u1' => $uid, 'u2' => $uid, 'u3' => $uid]);
$activity = q_all('SELECT * FROM activity_log WHERE user_id=? ORDER BY created_at DESC LIMIT 6', [$uid]);
$myEvents = q_all('SELECT e.* FROM event_saves s JOIN events e USING(event_id) WHERE s.user_id=? ORDER BY e.event_date', [$uid]);
$mySubs = q_all("SELECT content_id, title, status, created_at FROM contents WHERE submitted_by=? ORDER BY created_at DESC LIMIT 6", [$uid]);
$first = explode(' ', trim($user['name']))[0];
$fontOptions = ['0.875' => 'Small', '1.000' => 'Default', '1.150' => 'Large', '1.250' => 'X-Large'];
$curFont = number_format((float)$user['pref_font'], 3);
if (!function_exists('open_type')) { function open_type(string $itemType, string $sub): string {
  if ($itemType === 'character') return 'character';
  if ($itemType === 'merch') return 'merch';
  return $sub === 'article' ? 'article' : 'media';
} }
?>
<div data-modal data-dashboard class="fixed inset-0 z-[95] overflow-y-auto bg-void animate-[fade-in_0.4s_ease]" data-lenis-prevent>
  <div class="mx-auto max-w-6xl px-5 pb-24 pt-10 sm:px-8">
    <div class="flex items-center justify-between">
      <p class="flex items-center gap-2 text-xs uppercase tracking-[0.3em] text-cyan"><?= icon('sparkles', 13) ?> Fandom Command Center</p>
      <button data-close="modal" class="text-white/60 hover:text-white" data-cursor-hover aria-label="Close dashboard"><?= icon('x', 20) ?></button>
    </div>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-6 rounded-3xl glass p-7">
      <div class="flex items-center gap-4">
        <?php if ($user['avatar']): ?>
          <img src="<?= e(media($user['avatar'])) ?>" alt="" class="h-16 w-16 rounded-2xl object-cover" />
        <?php else: ?>
          <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet to-magenta font-display text-2xl text-white"><?= e(initials($user['name'])) ?></span>
        <?php endif; ?>
        <div>
          <h2 class="font-display text-3xl text-white">Welcome back, <?= e($first) ?></h2>
          <p class="text-sm text-white/50">Here's what's happening across your fandoms today.</p>
        </div>
      </div>
      <button data-action="logout" class="flex items-center gap-2 rounded-full border border-white/15 px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-white/70 hover:border-red-400/50 hover:text-red-300" data-cursor-hover>
        <?= icon('log-out', 14) ?> Log out
      </button>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="rounded-3xl border border-white/10 bg-white/[0.02] p-6 lg:col-span-1">
        <h4 class="mb-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/50"><?= icon('heart', 13) ?> Favorite Fandoms</h4>
        <form data-api="api/profile.php" data-autosave class="flex flex-wrap gap-2">
          <input type="hidden" name="action" value="favorites">
          <?php foreach (categories() as $c): ?>
            <label class="cursor-pointer" data-cursor-hover>
              <input type="checkbox" name="cats[]" value="<?= (int)$c['category_id'] ?>" class="peer sr-only" <?= in_array((int)$c['category_id'], $favIds, true) ? 'checked' : '' ?>>
              <span class="inline-block rounded-full border px-3.5 py-1.5 text-sm transition border-white/10 text-white/35 hover:border-white/30 peer-checked:border-violet/40 peer-checked:bg-violet/10 peer-checked:text-violet"><?= e($c['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </form>

        <h4 class="mb-4 mt-8 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/50"><?= icon('settings', 13) ?> Preferences</h4>
        <form data-api="api/profile.php" data-autosave data-prefs class="space-y-3 text-sm text-white/70">
          <input type="hidden" name="action" value="prefs">
          <label class="flex items-center justify-between rounded-xl border border-white/10 px-4 py-3">
            <span>Email digest</span>
            <select name="digest" class="bg-transparent text-right text-emerald-400 outline-none">
              <?php foreach (['Off', 'Daily', 'Weekly', 'Monthly'] as $o): ?><option class="bg-surface" <?= $user['pref_digest'] === $o ? 'selected' : '' ?>><?= $o ?></option><?php endforeach; ?>
            </select>
          </label>
          <label class="flex items-center justify-between rounded-xl border border-white/10 px-4 py-3">
            <span>Notifications</span>
            <select name="notifications" class="bg-transparent text-right text-emerald-400 outline-none">
              <option class="bg-surface" value="1" <?= $user['pref_notifications'] ? 'selected' : '' ?>>Enabled</option>
              <option class="bg-surface" value="0" <?= !$user['pref_notifications'] ? 'selected' : '' ?>>Disabled</option>
            </select>
          </label>
          <label class="flex items-center justify-between rounded-xl border border-white/10 px-4 py-3">
            <span>Theme</span>
            <select name="theme" class="bg-transparent text-right text-emerald-400 outline-none">
              <option class="bg-surface" value="dark" <?= $user['pref_theme'] === 'dark' ? 'selected' : '' ?>>Dark</option>
              <option class="bg-surface" value="light" <?= $user['pref_theme'] === 'light' ? 'selected' : '' ?>>Light</option>
            </select>
          </label>
          <label class="flex items-center justify-between rounded-xl border border-white/10 px-4 py-3">
            <span>Font size</span>
            <select name="font" class="bg-transparent text-right text-emerald-400 outline-none">
              <?php foreach ($fontOptions as $v => $l): ?><option class="bg-surface" value="<?= $v ?>" <?= $curFont === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
            </select>
          </label>
        </form>
      </div>

      <div class="rounded-3xl border border-white/10 bg-white/[0.02] p-6 lg:col-span-2">
        <h4 class="mb-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/50"><?= icon('bookmark', 13) ?> Saved &amp; Bookmarked</h4>
        <?php if (!$saved): ?>
          <p class="text-sm text-white/40">No bookmarks yet — save articles, characters or merch to see them here.</p>
        <?php else: ?>
          <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <?php foreach ($saved as $s): ?>
              <div class="group overflow-hidden rounded-xl border border-white/10">
                <div class="relative aspect-square cursor-pointer overflow-hidden" data-open="<?= open_type($s['item_type'], $s['sub']) ?>" data-id="<?= (int)$s['item_id'] ?>" data-cursor-hover>
                  <img src="<?= e(media($s['image'])) ?>" alt="<?= e($s['title']) ?>" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
                  <button data-bookmark="<?= e($s['item_type'] . ':' . $s['item_id']) ?>" data-refresh="dashboard" class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white/80 opacity-0 transition group-hover:opacity-100 hover:text-red-300" aria-label="Remove bookmark"><?= icon('trash-2', 12) ?></button>
                </div>
                <p class="truncate p-2 text-xs text-white/80"><?= e($s['title']) ?></p>
                <form data-api="api/bookmark.php" data-autosave class="px-2 pb-2">
                  <input type="hidden" name="action" value="note"><input type="hidden" name="key" value="<?= e($s['item_type'] . ':' . $s['item_id']) ?>">
                  <input name="note" maxlength="500" value="<?= e($s['note']) ?>" placeholder="Add a note…" class="w-full rounded-md bg-white/5 px-2 py-1 text-[11px] text-white/70 outline-none placeholder-white/30 focus:ring-1 focus:ring-violet/50" />
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <h4 class="mb-4 mt-8 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/50"><?= icon('clock-3', 13) ?> Recent Activity</h4>
        <div class="space-y-2">
          <?php if (!$activity): ?><p class="text-sm text-white/40">Your activity will appear here as you explore.</p><?php endif; ?>
          <?php foreach ($activity as $a): ?>
            <div class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3 text-sm">
              <span class="text-white/80"><span class="mr-2 font-semibold text-cyan"><?= e($a['action']) ?></span><?= e($a['detail']) ?></span>
              <span class="flex-none text-xs text-white/40"><?= e(time_ago($a['created_at'])) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Profile -->
      <div class="rounded-3xl border border-white/10 bg-white/[0.02] p-6">
        <h4 class="mb-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/50"><?= icon('user-cog', 13) ?> Profile</h4>
        <form data-api="api/profile.php" data-refresh="dashboard" enctype="multipart/form-data" class="space-y-3">
          <input type="hidden" name="action" value="profile">
          <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
            <?= icon('user', 16, 'text-white/40') ?>
            <input name="name" maxlength="100" value="<?= e($user['name']) ?>" placeholder="Full name" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
          </div>
          <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm text-white/50">
            <?= icon('mail', 16, 'text-white/40') ?> <span class="truncate"><?= e($user['email']) ?></span>
          </div>
          <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-white/15 px-4 py-3 text-sm text-white/50 hover:border-white/40" data-cursor-hover>
            <?= icon('upload', 16, 'text-white/40') ?> <span data-file-label>Upload avatar (optional)</span>
            <input type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif" class="sr-only">
          </label>
          <div data-form-msg class="hidden rounded-xl border px-4 py-3 text-xs"></div>
          <button class="w-full rounded-full bg-white py-2.5 text-xs font-semibold uppercase tracking-widest text-black transition hover:bg-violet hover:text-white" data-cursor-hover>Save profile</button>
        </form>
      </div>

      <!-- Fan submission -->
      <div class="rounded-3xl border border-white/10 bg-white/[0.02] p-6 lg:col-span-2">
        <h4 class="mb-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/50"><?= icon('pen-line', 13) ?> Submit fan content</h4>
        <form data-api="api/submit.php" data-refresh="dashboard" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3 sm:col-span-2">
            <input name="title" maxlength="200" required placeholder="Title of your article" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
          </div>
          <label class="flex items-center justify-between rounded-xl border border-white/15 bg-white/5 px-4 py-3 text-sm text-white/40">
            <span>Category</span>
            <select name="category_id" class="bg-transparent text-right text-white outline-none">
              <?php foreach (categories() as $c): ?><option class="bg-surface" value="<?= (int)$c['category_id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
          </label>
          <div class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/5 px-4 py-3">
            <input name="genre" maxlength="60" placeholder="Genre (e.g. Shonen)" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
          </div>
          <div class="rounded-xl border border-white/15 bg-white/5 px-4 py-3 sm:col-span-2">
            <input name="excerpt" maxlength="255" placeholder="One-line summary" class="w-full bg-transparent text-sm text-white outline-none placeholder-white/40" />
          </div>
          <div class="rounded-xl border border-white/15 bg-white/5 px-4 py-3 sm:col-span-2">
            <textarea name="body" rows="5" required placeholder="Write your article… (leave a blank line between paragraphs)" class="w-full resize-none bg-transparent text-sm text-white outline-none placeholder-white/40"></textarea>
          </div>
          <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-white/15 px-4 py-3 text-sm text-white/50 hover:border-white/40" data-cursor-hover>
            <?= icon('image', 16, 'text-white/40') ?> <span data-file-label>Cover image (optional)</span>
            <input type="file" name="image" accept="image/png,image/jpeg,image/webp,image/gif" class="sr-only">
          </label>
          <button class="rounded-full bg-white py-2.5 text-xs font-semibold uppercase tracking-widest text-black transition hover:bg-violet hover:text-white" data-cursor-hover>Submit for review</button>
          <div data-form-msg class="hidden rounded-xl border px-4 py-3 text-xs sm:col-span-2"></div>
        </form>
      </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
      <div class="rounded-3xl border border-white/10 bg-white/[0.02] p-6">
        <h4 class="mb-4 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/50"><?= icon('calendar-days', 13) ?> My Event Calendar</h4>
        <div class="space-y-2">
          <?php if (!$myEvents): ?><p class="text-sm text-white/40">Use "Add to calendar" on any event to plan your universe.</p><?php endif; ?>
          <?php foreach ($myEvents as $ev): ?>
            <div class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3 text-sm">
              <span class="min-w-0 truncate text-white/80"><span class="mr-2 font-semibold text-violet"><?= e(fmt_date($ev['event_date'], 'M j')) ?></span><?= e($ev['title']) ?></span>
              <span class="flex-none text-xs text-white/40"><?= e($ev['city']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="rounded-3xl border border-white/10 bg-white/[0.02] p-6">
        <div class="mb-4 flex items-center justify-between">
          <h4 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.25em] text-white/50"><?= icon('inbox', 13) ?> My Submissions</h4>
          <button data-action="feedback" class="text-[11px] uppercase tracking-wide text-white/50 hover:text-white" data-cursor-hover>Send feedback</button>
        </div>
        <div class="space-y-2">
          <?php if (!$mySubs): ?><p class="text-sm text-white/40">Your fan articles and their review status will appear here.</p><?php endif; ?>
          <?php foreach ($mySubs as $s):
            $cls = ['published' => 'text-emerald-400', 'pending' => 'text-amber', 'rejected' => 'text-red-300'][$s['status']]; ?>
            <div class="flex items-center justify-between rounded-xl border border-white/5 bg-white/[0.02] px-4 py-3 text-sm">
              <span class="min-w-0 truncate text-white/80"><?= e($s['title']) ?></span>
              <span class="flex-none text-xs font-semibold uppercase tracking-wide <?= $cls ?>"><?= e($s['status'] === 'published' ? 'Approved' : ucfirst($s['status'])) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>
