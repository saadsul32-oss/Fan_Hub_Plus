<?php
if (!isset($tab, $entities)) exit;
/* Add / edit modal for any entity (?add=entity  or  ?edit=ID&entity=key). ?prefill[col]=value pre-fills a new entry. */
$formKey = $_GET['add'] ?? ($_GET['entity'] ?? null);
$editId = (int)($_GET['edit'] ?? 0);
if ($formKey && isset($entities[$formKey]) && (isset($_GET['add']) || $editId)):
  $ent = $entities[$formKey];
  $row = $editId ? q_one("SELECT * FROM `{$ent['table']}` WHERE `{$ent['pk']}` = ?", [$editId]) : [];
  if ($editId && !$row) return;
  if (!$editId && isset($_GET['prefill']) && is_array($_GET['prefill'])) {
      foreach ($_GET['prefill'] as $k => $v) if (isset($ent['fields'][$k]) && is_string($v)) $row[$k] = mb_substr($v, 0, 500);
  }
  if (!$editId && $formKey === 'characters' && !isset($row['in_carousel'])) $row['in_carousel'] = 1;
?>
<div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6">
  <a href="?tab=<?= e($tab) ?>" data-modal-close class="absolute inset-0 bg-black/75 backdrop-blur-md" aria-label="Close"></a>
  <form method="post" enctype="multipart/form-data" class="adm-card relative max-h-[92vh] w-full max-w-3xl overflow-y-auto !bg-[#0b0b14]/95 shadow-[0_40px_120px_-30px_rgba(139,92,246,0.55)] animate-[fade-in_0.35s_cubic-bezier(.22,1,.36,1)] no-scrollbar">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="save"><input type="hidden" name="tab" value="<?= e($tab) ?>">
    <input type="hidden" name="entity" value="<?= e($formKey) ?>"><input type="hidden" name="id" value="<?= $editId ?>">

    <div class="sticky top-0 z-10 flex items-center justify-between border-b border-white/10 bg-[#0b0b14]/90 px-6 py-5 backdrop-blur-xl sm:px-8">
      <div>
        <p class="adm-eyebrow text-violet!"><?= $editId ? 'Edit' : 'New' ?> <?= e(strtolower($ent['label'])) ?></p>
        <h2 class="adm-title mt-1 text-3xl"><?= $editId ? e($row[array_key_first($ent['fields'])] ?? $ent['label']) : 'Add ' . e($ent['label']) ?></h2>
      </div>
      <a href="?tab=<?= e($tab) ?>" class="adm-icon-btn h-10! w-10!" aria-label="Close"><?= icon('x', 18) ?></a>
    </div>

    <div class="grid grid-cols-1 gap-5 px-6 py-6 sm:grid-cols-2 sm:px-8">
      <?php foreach ($ent['fields'] as $col => $f):
        [$type, $label] = $f; $req = $f[2] ?? false; $val = $row[$col] ?? '';
        $wide = in_array($type, ['textarea', 'longtext', 'image'], true) || in_array($col, ['title', 'description', 'answer', 'media_url', 'short_desc'], true);
        $fid = 'f-' . $col; ?>
        <div class="<?= $wide ? 'sm:col-span-2' : '' ?>">
          <?php if ($type === 'checkbox'): ?>
            <label class="flex cursor-pointer items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm text-white/85 transition hover:border-white/20">
              <?= e($label) ?>
              <span class="adm-switch"><input type="checkbox" name="<?= e($col) ?>" value="1" <?= $val ? 'checked' : '' ?>><span></span></span>
            </label>
          <?php else: ?>
            <label for="<?= $fid ?>" class="adm-label"><?= e($label) ?><?= $req ? ' <span class="text-magenta">*</span>' : '' ?></label>
            <?php if ($type === 'textarea' || $type === 'longtext'): ?>
              <textarea id="<?= $fid ?>" name="<?= e($col) ?>" rows="<?= $type === 'longtext' ? 9 : 4 ?>" <?= $req ? 'required' : '' ?> class="adm-input leading-relaxed"><?= e($val) ?></textarea>
              <?php if ($col === 'timeline'): ?><p class="mt-1.5 text-[11px] text-white/35">One entry per line — e.g. <code class="text-cyan">Arc 3 | Forms an alliance with the Ember Clan.</code></p><?php endif; ?>
              <?php if ($col === 'related'): ?><p class="mt-1.5 text-[11px] text-white/35">One title per line — each becomes a search chip on the profile.</p><?php endif; ?>
            <?php elseif ($type === 'select'): ?>
              <select id="<?= $fid ?>" name="<?= e($col) ?>" <?= $req ? 'required' : '' ?> class="adm-input cursor-pointer">
                <?php foreach ($f[3] as $ov => $ol): ?><option value="<?= e($ov) ?>" <?= (string)$val === (string)$ov ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?>
              </select>
            <?php elseif ($type === 'image'): ?>
              <div class="mt-2 flex flex-col gap-4 rounded-2xl border border-dashed border-white/15 bg-white/[0.02] p-4 sm:flex-row sm:items-center">
                <img id="prev-<?= $fid ?>" src="<?= e(media($val)) ?>" alt="" class="<?= $val ? '' : 'hidden' ?> h-28 w-24 flex-none rounded-xl object-cover ring-1 ring-white/10">
                <div class="min-w-0 flex-1 space-y-3">
                  <input id="<?= $fid ?>" name="<?= e($col) ?>" value="<?= e($val) ?>" placeholder="https://… or images/…" data-preview-url="prev-<?= $fid ?>" class="adm-input mt-0!">
                  <label class="flex cursor-pointer items-center gap-2 text-xs text-white/55 hover:text-white">
                    <span class="adm-btn adm-btn-ghost py-2!"><?= icon('upload', 13) ?> Upload image</span>
                    <span data-file-label>JPG, PNG, WEBP or GIF · max 3 MB</span>
                    <input type="file" name="<?= e($col) ?>" accept="image/*" data-preview="prev-<?= $fid ?>" class="hidden" onchange="this.previousElementSibling.textContent=this.files[0]?this.files[0].name:''">
                  </label>
                </div>
              </div>
            <?php elseif ($type === 'password'): ?>
              <input id="<?= $fid ?>" type="password" name="<?= e($col) ?>" autocomplete="new-password" <?= !$editId ? 'required' : '' ?> class="adm-input">
            <?php else: ?>
              <input id="<?= $fid ?>" type="<?= $type === 'number' ? 'text' : e($type) ?>" <?= $type === 'number' ? 'inputmode="decimal"' : '' ?> name="<?= e($col) ?>" value="<?= e($val) ?>" <?= $req ? 'required' : '' ?> class="adm-input <?= $type === 'color' ? 'h-11 cursor-pointer p-1.5!' : '' ?>"<?= $col === 'media_url' ? ' data-media-input placeholder="assets/media/video/clip.mp4, https://…/song.mp3 or a YouTube link"' : '' ?>>
              <?php if ($col === 'media_url'): ?>
                <p class="adm-hint">MP4 video, MP3 audio, or a YouTube / Vimeo link. The preview below plays exactly what visitors will see.</p>
                <div class="adm-preview" data-media-preview data-initial="<?= e($val !== '' ? media_src($val) : '') ?>" <?= $val === '' ? 'hidden' : '' ?>></div>
              <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="sticky bottom-0 flex items-center justify-end gap-3 border-t border-white/10 bg-[#0b0b14]/90 px-6 py-4 backdrop-blur-xl sm:px-8">
      <a href="?tab=<?= e($tab) ?>" class="adm-btn adm-btn-ghost">Cancel</a>
      <button class="adm-btn adm-btn-primary"><?= icon('check', 14) ?> <?= $editId ? 'Save changes' : 'Create' ?></button>
    </div>
  </form>
</div>
<?php endif; ?>
