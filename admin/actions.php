<?php
/**
 * Admin POST actions (Post/Redirect/Get). Every action is CSRF-protected and admin-only.
 */
function flash(string $msg, bool $ok = true): void { $_SESSION['flash'] = ['msg' => $msg, 'ok' => $ok]; }

function admin_redirect(string $tab, array $extra = []): void
{
    header('Location: ' . url('admin/?' . http_build_query(['tab' => $tab] + $extra)));
    exit;
}

function handle_admin_post(array $admin): void
{
    if (!csrf_valid()) { flash('Session expired — please try again.', false); admin_redirect($_POST['tab'] ?? 'overview'); }
    $tab = preg_replace('/[^a-z]/', '', $_POST['tab'] ?? 'overview');
    $entities = admin_entities();
    $act = $_POST['do'] ?? '';

    try {
        switch ($act) {
            case 'save':
                $key = $_POST['entity'] ?? '';
                if (!isset($entities[$key])) throw new RuntimeException('Unknown entity');
                $ent = $entities[$key];
                $id = (int)($_POST['id'] ?? 0);
                $data = [];
                foreach ($ent['fields'] as $col => $f) {
                    [$type, $label] = $f;
                    $required = $f[2] ?? false;
                    $val = $_POST[$col] ?? '';
                    if ($type === 'checkbox') { $data[$col] = isset($_POST[$col]) ? 1 : 0; continue; }
                    if ($type === 'password') {
                        if ($val !== '') {
                            if (strlen($val) < 8) throw new RuntimeException('Password must be at least 8 characters.');
                            $data['password_hash'] = password_hash($val, PASSWORD_BCRYPT);
                        } elseif (!$id) throw new RuntimeException('A password is required for new users.');
                        continue;
                    }
                    if ($type === 'image' && !empty($_FILES[$col]['name'])) {
                        $up = save_upload($_FILES[$col], 'media');
                        if ($up) {
                            $val = $up;
                            q('INSERT INTO media_files (file_path, title, tags, category_id, uploaded_by) VALUES (?,?,?,?,?)',
                              [$up, mb_substr((string)($_POST['name'] ?? $_POST['title'] ?? $ent['label']), 0, 150), strtolower($ent['label']), $_POST['category_id'] ?? null ?: null, $admin['user_id']]);
                        }
                    }
                    $val = is_string($val) ? trim($val) : $val;
                    if ($type === 'select' && $val !== '' && !array_key_exists($val, $f[3])) throw new RuntimeException("Invalid value for $label.");
                    if ($required && $val === '') throw new RuntimeException("$label is required.");
                    if (in_array($type, ['number', 'date'], true) && $val === '') $val = in_array($col, ['sort_order', 'popularity'], true) ? 0 : null;
                    if ($type === 'number' && $val !== null && !is_numeric($val)) throw new RuntimeException("$label must be a number.");
                    if ($col === 'email' && !filter_var($val, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid email address.');
                    $data[$col] = $val;
                }
                if ($key === 'users' && $id === (int)$admin['user_id'] && ($data['role'] !== 'admin' || $data['status'] !== 'active'))
                    throw new RuntimeException('You cannot demote or block your own admin account.');

                $cols = array_keys($data);
                if ($id) {
                    $set = implode(', ', array_map(fn($c) => "`$c` = ?", $cols));
                    q("UPDATE `{$ent['table']}` SET $set WHERE `{$ent['pk']}` = ?", array_merge(array_values($data), [$id]));
                    flash($ent['label'] . ' updated.');
                } else {
                    $ph = implode(',', array_fill(0, count($cols), '?'));
                    q("INSERT INTO `{$ent['table']}` (`" . implode('`,`', $cols) . "`) VALUES ($ph)", array_values($data));
                    flash($ent['label'] . ' added.');
                }
                break;

            case 'delete':
                $key = $_POST['entity'] ?? '';
                if (!isset($entities[$key])) throw new RuntimeException('Unknown entity');
                $id = (int)($_POST['id'] ?? 0);
                if ($key === 'users' && $id === (int)$admin['user_id']) throw new RuntimeException('You cannot delete your own account.');
                q("DELETE FROM `{$entities[$key]['table']}` WHERE `{$entities[$key]['pk']}` = ?", [$id]);
                flash($entities[$key]['label'] . ' deleted.');
                break;

            case 'moderate':
                $id = (int)$_POST['id'];
                $decision = $_POST['decision'] === 'approve' ? 'published' : 'rejected';
                $row = q_one('SELECT title, submitted_by FROM contents WHERE content_id=?', [$id]);
                if (!$row) throw new RuntimeException('Submission not found.');
                q('UPDATE contents SET status=?, release_date=IF(?="published", CURDATE(), release_date) WHERE content_id=?', [$decision, $decision, $id]);
                if ($row['submitted_by']) log_activity((int)$row['submitted_by'], $decision === 'published' ? 'Approved' : 'Rejected', $row['title']);
                flash($decision === 'published' ? 'Submission approved and published.' : 'Submission rejected.');
                break;

            case 'feedback_status':
                $st = in_array($_POST['status'] ?? '', ['new', 'reviewed', 'resolved'], true) ? $_POST['status'] : 'reviewed';
                q('UPDATE feedback SET status=? WHERE feedback_id=?', [$st, (int)$_POST['id']]);
                flash('Feedback marked as ' . $st . '.');
                break;

            case 'feedback_delete':
                q('DELETE FROM feedback WHERE feedback_id=?', [(int)$_POST['id']]);
                flash('Feedback deleted.');
                break;

            case 'media_upload':
                if (empty($_FILES['file']['name'])) throw new RuntimeException('Choose an image to upload.');
                $path = save_upload($_FILES['file'], 'media');
                q('INSERT INTO media_files (file_path, title, tags, category_id, uploaded_by) VALUES (?,?,?,?,?)',
                  [$path, mb_substr(trim($_POST['title'] ?? ''), 0, 150), mb_substr(strtolower(trim($_POST['tags'] ?? '')), 0, 255), ((int)($_POST['category_id'] ?? 0)) ?: null, $admin['user_id']]);
                flash('Media uploaded.');
                break;

            case 'media_update':
                q('UPDATE media_files SET title=?, tags=?, category_id=? WHERE media_id=?',
                  [mb_substr(trim($_POST['title'] ?? ''), 0, 150), mb_substr(strtolower(trim($_POST['tags'] ?? '')), 0, 255), ((int)($_POST['category_id'] ?? 0)) ?: null, (int)$_POST['id']]);
                flash('Media details saved.');
                break;

            case 'media_delete':
                $m = q_one('SELECT file_path FROM media_files WHERE media_id=?', [(int)$_POST['id']]);
                if ($m) {
                    q('DELETE FROM media_files WHERE media_id=?', [(int)$_POST['id']]);
                    if (strpos($m['file_path'], 'uploads/') === 0) @unlink(APP_ROOT . '/' . $m['file_path']);
                }
                flash('Media removed from library.');
                break;

            case 'site_settings':
                // Full site control: hero, SEO, footer, socials, announcement bar, maintenance mode.
                // A checkbox posts nothing when unchecked, so each toggle is derived explicitly.
                $vals = [
                    'hero_eyebrow'   => mb_substr(trim($_POST['hero_eyebrow'] ?? ''), 0, 80),
                    'hero_subtitle'  => mb_substr(trim($_POST['hero_subtitle'] ?? ''), 0, 80),
                    'seo_title'      => mb_substr(trim($_POST['seo_title'] ?? ''), 0, 160),
                    'seo_description'=> mb_substr(trim($_POST['seo_description'] ?? ''), 0, 320),
                    'footer_tagline' => mb_substr(trim($_POST['footer_tagline'] ?? ''), 0, 160),
                    'social_twitter'   => mb_substr(trim($_POST['social_twitter'] ?? ''), 0, 255),
                    'social_instagram' => mb_substr(trim($_POST['social_instagram'] ?? ''), 0, 255),
                    'social_youtube'   => mb_substr(trim($_POST['social_youtube'] ?? ''), 0, 255),
                    'social_discord'   => mb_substr(trim($_POST['social_discord'] ?? ''), 0, 255),
                    'social_tiktok'    => mb_substr(trim($_POST['social_tiktok'] ?? ''), 0, 255),
                    'announcement_enabled' => isset($_POST['announcement_enabled']) ? '1' : '0',
                    'announcement_text'    => mb_substr(trim($_POST['announcement_text'] ?? ''), 0, 160),
                    'announcement_link'    => mb_substr(trim($_POST['announcement_link'] ?? ''), 0, 255),
                    'maintenance_mode'    => isset($_POST['maintenance_mode']) ? '1' : '0',
                    'maintenance_message' => mb_substr(trim($_POST['maintenance_message'] ?? ''), 0, 500),
                ];
                foreach (['social_twitter', 'social_instagram', 'social_youtube', 'social_discord', 'social_tiktok', 'announcement_link'] as $u) {
                    if ($vals[$u] !== '' && !preg_match('#^https?://#i', $vals[$u])) throw new RuntimeException('Links must start with http:// or https://.');
                }
                if (!empty($_FILES['hero_image']['name'])) {
                    $up = save_upload($_FILES['hero_image'], 'media');
                    if ($up) $vals['hero_image'] = $up;
                } elseif (!empty($_POST['hero_image_clear'])) {
                    $vals['hero_image'] = '';
                }
                foreach ($vals as $k => $v) {
                    q('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)', [$k, $v]);
                }
                flash('Site control settings saved.' . ($vals['maintenance_mode'] === '1' ? ' Maintenance mode is now ON for visitors.' : ''));
                break;

            case 'settings':
                foreach (['platform_name', 'support_email'] as $k) {
                    q('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)', [$k, trim($_POST[$k] ?? '')]);
                }
                flash('Settings saved.');
                break;

            case 'chatbot_settings':
                $name = trim($_POST['chatbot_name'] ?? '') ?: 'ORA';
                $vals = ['chatbot_name' => mb_substr($name, 0, 30), 'chatbot_welcome' => mb_substr(trim($_POST['chatbot_welcome'] ?? ''), 0, 400),
                         'chatbot_ai' => (isset($_POST['chatbot_ai']) && CHAT_AI_KEY !== '') ? '1' : '0'];
                foreach ($vals as $k => $v) {
                    q('INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)', [$k, $v]);
                }
                flash('Chatbot settings saved.');
                break;

            case 'password':
                if (!password_verify($_POST['current'] ?? '', $admin['password_hash'])) throw new RuntimeException('Current password is incorrect.');
                if (strlen($_POST['new'] ?? '') < 8) throw new RuntimeException('New password must be at least 8 characters.');
                q('UPDATE users SET password_hash=? WHERE user_id=?', [password_hash($_POST['new'], PASSWORD_BCRYPT), $admin['user_id']]);
                flash('Password changed.');
                break;

            default:
                throw new RuntimeException('Unknown action.');
        }
    } catch (PDOException $ex) {
        flash(($ex->errorInfo[1] ?? 0) == 1062 ? 'That value already exists (duplicate email / username / slug).' : 'Could not save: ' . ($ex->errorInfo[2] ?? $ex->getMessage()), false);
        if ($act === 'save') admin_redirect($tab, !empty($_POST['id']) ? ['edit' => (int)$_POST['id'], 'entity' => $_POST['entity']] : ['add' => $_POST['entity']]);
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), false);
        if ($act === 'save') admin_redirect($tab, !empty($_POST['id']) ? ['edit' => (int)$_POST['id'], 'entity' => $_POST['entity']] : ['add' => $_POST['entity']]);
    }
    admin_redirect($tab);
}
