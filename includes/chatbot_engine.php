<?php
/**
 * ORA chatbot engine.
 *
 * ora_reply($message, $user) returns
 *   [
 *     'reply'    => string   (plain text, **bold** and line breaks allowed),
 *     'cards'    => [ ['type'=>'character|article|media|merch|event|release', 'id'=>int, 'title'=>..., 'sub'=>..., 'image'=>...], ... ],
 *     'actions'  => [ ['label'=>..., 'send'=>'text'] | ['label'=>..., 'go'=>'#events'] | ['label'=>..., 'search'=>'q'] | ['label'=>..., 'do'=>'auth|dashboard|feedback'] ],
 *     'intent'   => string,
 *     'answered' => bool,
 *   ]
 *
 * Order of resolution: guided tour → small talk → account → characters → events → releases →
 * merch → trending → recommendations → fandom/category → content type → knowledge base (admin) →
 * library search → optional AI (Claude) → friendly fallback.
 */

function ora_norm(string $s): string
{
    $s = mb_strtolower($s);
    $s = str_replace(['k-pop', 'k pop'], 'kpop', $s);
    $s = preg_replace('/[^\p{L}\p{N}\s\'-]+/u', ' ', $s);
    return trim(preg_replace('/\s+/u', ' ', $s));
}

function ora_has(string $text, $words): bool
{
    foreach ((array)$words as $w) {
        $w = ora_norm($w);
        if ($w !== '' && preg_match('/(^|[^\p{L}\p{N}])' . preg_quote($w, '/') . '($|[^\p{L}\p{N}])/u', $text)) return true;
    }
    return false;
}

function ora_excerpt(?string $t, int $len = 170): string
{
    $t = trim(preg_replace('/\s+/', ' ', (string)$t));
    return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len - 1)) . '…' : $t;
}

function ora_content_card(array $c): array
{
    return ['type' => $c['type'] === 'article' ? 'article' : 'media', 'id' => (int)$c['content_id'], 'title' => $c['title'],
            'sub' => content_type_label($c['type']) . ' · ' . ($c['category'] ?? ''), 'image' => media($c['image'])];
}
function ora_character_card(array $c): array
{
    return ['type' => 'character', 'id' => (int)$c['character_id'], 'title' => $c['name'], 'sub' => $c['fandom_label'], 'image' => media($c['image'])];
}
function ora_merch_card(array $m): array
{
    return ['type' => 'merch', 'id' => (int)$m['item_id'], 'title' => $m['name'], 'sub' => trim($m['item_type'] . ($m['price'] ? ' · ' . $m['price'] : '')), 'image' => media($m['image'])];
}
function ora_event_card(array $e): array
{
    return ['type' => 'event', 'id' => (int)$e['event_id'], 'title' => $e['title'], 'sub' => fmt_date($e['event_date'], 'M j') . ' · ' . $e['city'], 'image' => ''];
}

function ora_default_actions(): array
{
    return [
        ['label' => 'Trending now', 'send' => "What's trending?"],
        ['label' => 'Meet the characters', 'send' => 'Show me characters'],
        ['label' => 'Upcoming events', 'send' => 'Upcoming events'],
        ['label' => 'Give me a tour', 'send' => 'Give me a tour'],
    ];
}

/** Main entry */
function ora_reply(string $msg, ?array $user): array
{
    $uid   = $user ? (int)$user['user_id'] : null;
    $text  = ora_norm($msg);
    $first = $user ? explode(' ', trim($user['name']))[0] : '';
    $out   = ['reply' => null, 'cards' => [], 'actions' => [], 'intent' => 'fallback', 'answered' => true];
    $set   = function (string $intent, string $reply, array $cards = [], array $actions = []) use (&$out) {
        $out = ['reply' => $reply, 'cards' => $cards, 'actions' => $actions, 'intent' => $intent, 'answered' => true];
    };

    /* ---------- 1. Guided tour (multi-step) ---------- */
    $TOUR = [
        ['**Step 1 of 5 — Fandoms.** Scroll to the Fandom Universe Explorer and hover (or tap) any of the eight worlds. Clicking one opens search filtered to that fandom.', '#fandoms'],
        ['**Step 2 of 5 — Search.** Tap the magnifier (or press Ctrl + K) to search everything. Filter by category, type, year and genre, and sort by popular, latest or A–Z.', null],
        ['**Step 3 of 5 — Characters & Media.** Swipe the character spotlight, open full profiles, then stream trailers, videos and soundtracks in Watch & Listen — you can rate each one.', '#characters'],
        ['**Step 4 of 5 — Events.** Filter events by city or tap "Near me" to sort them by distance, then add them to your calendar.', '#events'],
        ['**Step 5 of 5 — Your account.** Sign in to bookmark items with notes, pick favourite fandoms, submit fan articles and see everything in your Dashboard. That\'s the tour — ask me anything!', null],
    ];
    $tourStep = function (int $i) use ($TOUR, $set) {
        [$t, $go] = $TOUR[$i];
        $acts = [];
        if ($go) $acts[] = ['label' => 'Take me there', 'go' => $go];
        if ($i < count($TOUR) - 1) { $acts[] = ['label' => 'Next step', 'send' => 'next']; $acts[] = ['label' => 'Stop tour', 'send' => 'stop tour']; }
        else { $acts = array_merge($acts, array_slice(ora_default_actions(), 0, 3)); }
        $set('tour', $t, [], $acts);
    };
    if (preg_match('/\b(tour|guide me|get started|getting started|how does (this|it|the site) work|new here|onboard|how to use)\b/u', $text)) {
        $_SESSION['chat_tour'] = 0;
        $tourStep(0);
        $out['reply'] = "Let's take a quick tour! " . $out['reply'];
        return $out;
    }
    if (isset($_SESSION['chat_tour'])) {
        if (preg_match('/\b(stop|exit|quit|end|cancel)\b/u', $text)) {
            unset($_SESSION['chat_tour']);
            $set('tour', 'Tour ended. Ask me anything about the Fandom Universe whenever you like!', [], ora_default_actions());
            return $out;
        }
        if (preg_match('/^(next|continue|go on|yes|ok|okay|sure|yep|haan|han|ji|next step)\b/u', $text)) {
            $step = $_SESSION['chat_tour'] + 1;
            if ($step >= count($TOUR) - 1) unset($_SESSION['chat_tour']); else $_SESSION['chat_tour'] = $step;
            $tourStep(min($step, count($TOUR) - 1));
            return $out;
        }
        unset($_SESSION['chat_tour']);   // user moved on to another question
    }

    /* ---------- 2. Exact suggested question from the knowledge base ---------- */
    foreach (q_all("SELECT * FROM chatbot_faq WHERE question <> ''") as $faq) {
        if (ora_norm($faq['question']) === $text) { $set('faq', $faq['answer'], [], array_slice(ora_default_actions(), 0, 3)); return $out; }
    }

    /* ---------- 2b. Small talk ---------- */
    if (preg_match('/^(hi+|hey+|hello+|hiya|yo|salam|salaam|assalam|assalamualaikum|aoa|namaste|good (morning|afternoon|evening))\b/u', $text) && mb_strlen($text) < 40) {
        $set('greeting', ($first ? "Hey $first! " : 'Hey there! ') . "I'm **" . setting('chatbot_name', 'ORA') . "**, your Fandom Universe guide. I can find characters, trending content, events, merch and upcoming releases — or recommend something new.", [], ora_default_actions());
        return $out;
    }
    if (preg_match('/\b(thanks|thank you|thx|ty|shukriya|shukria|awesome|great|cool|nice)\b/u', $text) && mb_strlen($text) < 40) {
        $set('thanks', 'Anytime! Anything else you want to explore?', [], ora_default_actions());
        return $out;
    }
    if (preg_match('/^(bye|goodbye|see you|cya|khuda hafiz|allah hafiz)\b/u', $text)) {
        $set('bye', 'See you in the Fandom Universe! I\'ll be right here if you need me. ✦');
        return $out;
    }
    if (preg_match('/\b(who are you|what are you|your name|what can you do|help|commands|menu|options)\b/u', $text) && mb_strlen($text) < 60) {
        $set('help', "I'm " . setting('chatbot_name', 'ORA') . " — here's what I can do:\n• Find **characters** (try \"Who is Kaien Voss?\")\n• Show **trending** content and **recommendations**\n• List **events** by city (\"events in Lahore\")\n• Upcoming **releases** and **merch**\n• Explain features — bookmarks, dashboard, fan submissions\n• Give you a **guided tour** of the site", [], ora_default_actions());
        return $out;
    }

    /* ---------- 3. Account helpers ---------- */
    if (ora_has($text, ['my bookmarks', 'my saved', 'my dashboard', 'my account', 'my profile', 'saved items'])) {
        if (!$uid) { $set('account', 'Sign in first and I\'ll show you everything you have saved. Your bookmarks, notes, favourite fandoms and submissions live in the Dashboard.', [], [['label' => 'Sign in', 'do' => 'auth']]); return $out; }
        $n = (int)q_val('SELECT COUNT(*) FROM bookmarks WHERE user_id=?', [$uid]);
        $set('account', "You have **$n** bookmarked item" . ($n === 1 ? '' : 's') . ". Open your Dashboard to see them with your notes.", [], [['label' => 'Open dashboard', 'do' => 'dashboard']]);
        return $out;
    }

    /* ---------- 4. Characters ---------- */
    $chars = q_all('SELECT ch.*, cat.name AS category FROM characters ch JOIN categories cat USING(category_id) ORDER BY ch.views DESC');
    $hit = null; $hitScore = 0;
    foreach ($chars as $c) {
        $name = ora_norm($c['name']);
        $score = 0;
        if (ora_has($text, $name)) $score = 10;
        else foreach (preg_split('/[\s-]+/u', $name) as $part) if (mb_strlen($part) >= 4 && ora_has($text, $part)) $score = max($score, 6);
        if ($score > $hitScore) { $hitScore = $score; $hit = $c; }
    }
    if ($hit) {
        $tl = parse_timeline($hit['timeline']);
        $reply = "**{$hit['name']}** — {$hit['role']} from *" . character_fandom($hit) . "* ({$hit['category']}).\n" . ora_excerpt($hit['bio'], 230);
        if ($tl) $reply .= "\nKey moment: " . $tl[count($tl) - 1]['year'] . ' — ' . $tl[count($tl) - 1]['event'];
        $acts = [['label' => 'Open full profile', 'open' => ['view' => 'character', 'id' => (int)$hit['character_id']]]];
        foreach (array_slice(parse_lines($hit['related']), 0, 2) as $r) $acts[] = ['label' => $r, 'search' => $r];
        $set('character', $reply, [ora_character_card($hit)], $acts);
        return $out;
    }
    if (ora_has($text, ['character', 'characters', 'hero', 'heroes', 'villain', 'protagonist', 'who is', 'who are', 'cast', 'icons'])) {
        $cards = array_map('ora_character_card', array_slice($chars, 0, 6));
        $set('characters', $chars ? 'Here are the icons of the universe — tap any card for a full cinematic profile.' : 'No character profiles have been published yet.', $cards,
            [['label' => 'Go to characters', 'go' => '#characters'], ['label' => 'Most popular character?', 'send' => 'Who is the most popular character?']]);
        if ($chars && ora_has($text, ['popular', 'famous', 'top', 'best'])) $out['reply'] = "The most viewed character right now is **{$chars[0]['name']}** (" . number_format((int)$chars[0]['views']) . ' views). Here\'s the full line-up:';
        return $out;
    }

    /* ---------- 5. Events ---------- */
    if (ora_has($text, ['event', 'events', 'convention', 'conventions', 'con', 'meetup', 'meet up', 'fest', 'festival', 'screening', 'concert', 'tour dates', 'watch party', 'near me'])) {
        $cities = array_column(q_all('SELECT DISTINCT city FROM events WHERE event_date >= CURDATE()'), 'city');
        $city = null;
        foreach ($cities as $ct) if (ora_has($text, $ct)) { $city = $ct; break; }
        $rows = $city
            ? q_all('SELECT * FROM events WHERE event_date >= CURDATE() AND city = ? ORDER BY event_date LIMIT 5', [$city])
            : q_all('SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 5');
        if (!$rows) {
            $set('events', $city ? "No upcoming events in $city right now. Here are the cities with events: " . implode(', ', $cities) . '.' : 'There are no upcoming events at the moment — check back soon!', [], [['label' => 'Open events', 'go' => '#events']]);
            return $out;
        }
        $lines = array_map(fn($e) => '• **' . $e['title'] . '** — ' . fmt_date($e['event_date'], 'D, M j') . ', ' . $e['venue'] . ' (' . $e['city'] . ')', $rows);
        $intro = $city ? "Upcoming in **$city**:" : 'Here\'s what\'s coming up:';
        if (ora_has($text, ['near me', 'nearby', 'close to me'])) $intro = 'Tap **Near me** in the Events section and I\'ll sort by distance using your location. Meanwhile, the next events are:';
        $acts = [['label' => 'Open events & map', 'go' => '#events']];
        foreach (array_slice(array_values(array_diff($cities, [$city, 'Online', 'Multiple Cities'])), 0, 3) as $ct) $acts[] = ['label' => "Events in $ct", 'send' => "Events in $ct"];
        $set('events', $intro . "\n" . implode("\n", $lines), [], $acts);
        return $out;
    }

    /* ---------- 6. Releases ---------- */
    if (ora_has($text, ['release', 'releases', 'releasing', 'coming soon', 'upcoming', 'new season', 'next season', 'launch', 'premiere', 'comeback', 'drop date'])) {
        $rows = q_all('SELECT r.*, cat.name AS category FROM releases r JOIN categories cat USING(category_id) WHERE r.release_date >= CURDATE() ORDER BY r.release_date LIMIT 5');
        if ($rows) {
            $lines = array_map(fn($r) => '• **' . $r['title'] . '** (' . $r['kind'] . ') — ' . fmt_date($r['release_date']), $rows);
            $set('releases', "Upcoming releases:\n" . implode("\n", $lines), [], [['label' => 'See the release calendar', 'go' => '#merch'], ['label' => 'Upcoming events', 'send' => 'Upcoming events']]);
            return $out;
        }
    }

    /* ---------- 7. Merch ---------- */
    if (ora_has($text, ['merch', 'merchandise', 'buy', 'shop', 'store', 'figure', 'figures', 'hoodie', 'vinyl', 'poster', 'collectible', 'collectibles', 'product', 'products', 'wishlist'])) {
        $rows = q_all('SELECT * FROM merchandise ORDER BY views DESC LIMIT 6');
        $set('merch', $rows ? 'The Merchandise Showcase highlights collector pieces (showcase only — no checkout). Tap any item for details or save it to your wishlist:' : 'The merch showcase is empty right now.',
            array_map('ora_merch_card', $rows), [['label' => 'Open merch showcase', 'go' => '#merch']]);
        return $out;
    }

    /* ---------- 8. Content by type (videos, trailers, music, articles) ---------- */
    $types = ['trailer' => ['trailer', 'trailers', 'teaser'], 'video' => ['video', 'videos', 'watch', 'clip', 'clips'], 'audio' => ['music', 'song', 'songs', 'soundtrack', 'ost', 'podcast', 'audio', 'listen'], 'article' => ['article', 'articles', 'read', 'news', 'blog', 'review', 'reviews']];
    foreach ($types as $type => $kw) {
        if (ora_has($text, $kw) && mb_strlen($text) < 60 && !preg_match('/\b(recommend|suggest|what should i|similar|something like)\b/u', $text)) {
            $rows = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE c.status='published' AND c.type=? ORDER BY " . (ora_has($text, ['trending', 'popular', 'top', 'best']) ? 'c.popularity DESC' : 'c.release_date DESC') . " LIMIT 6", [$type]);
            if ($rows) {
                $set('content', 'Latest ' . strtolower(content_type_label($type)) . ($type === 'audio' ? '' : 's') . ' in the library:', array_map('ora_content_card', $rows),
                    [['label' => 'Browse all ' . strtolower(content_type_label($type)) . ($type === 'audio' ? '' : 's'), 'searchType' => $type]]);
                return $out;
            }
        }
    }

    /* ---------- 9. Trending ---------- */
    if (ora_has($text, ['trending', 'popular', 'hot', 'viral', 'top', 'buzz', 'this week', 'whats new', "what's new", 'latest'])) {
        $rows = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE c.status='published' ORDER BY c.is_trending DESC, c.popularity DESC LIMIT 6");
        $set('trending', 'Here\'s what the community is buzzing about right now:', array_map('ora_content_card', $rows), [['label' => 'Open trending', 'go' => '#explore'], ['label' => 'Recommend me something', 'send' => 'Recommend me something']]);
        return $out;
    }

    /* ---------- 10. Category / fandom mentioned ---------- */
    $cat = null;
    foreach (categories() as $c) {
        if (ora_has($text, [$c['name'], $c['slug'], rtrim(mb_strtolower($c['name']), 's')])) { $cat = $c; break; }
    }

    /* ---------- 11. Recommendations ---------- */
    if (preg_match('/\b(recommend|recommendation|suggest|suggestion|what should i|something like|similar to|bored|what to watch|what to read)\b/u', $text)) {
        $ids = [];
        $why = '';
        if ($cat) { $ids = [(int)$cat['category_id']]; $why = "Top picks in **{$cat['name']}**:"; }
        elseif ($uid) {
            $favs = q_all('SELECT c.category_id, c.name FROM user_categories uc JOIN categories c USING(category_id) WHERE uc.user_id=?', [$uid]);
            if ($favs) { $ids = array_map('intval', array_column($favs, 'category_id')); $why = 'Based on your love for ' . implode(', ', array_column($favs, 'name')) . ', check these out:'; }
        }
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $rows = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE c.status='published' AND c.category_id IN ($in) ORDER BY c.popularity DESC LIMIT 5", $ids);
        } else {
            $rows = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE c.status='published' ORDER BY c.popularity DESC LIMIT 5");
            $why = 'Here are the community favourites right now:';
        }
        $acts = [];
        if (!$uid) $acts[] = ['label' => 'Sign in for personal picks', 'do' => 'auth'];
        elseif (!$cat && !$ids) $acts[] = ['label' => 'Choose favourite fandoms', 'do' => 'dashboard'];
        foreach (array_slice(categories(), 0, 3) as $c) $acts[] = ['label' => 'More ' . $c['name'], 'send' => 'Recommend ' . $c['name']];
        $set('recommend', $rows ? $why : 'I don\'t have enough published content to recommend yet.', array_map('ora_content_card', $rows), $acts);
        return $out;
    }
    if ($cat && mb_strlen($text) < 60) {
        $rows = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE c.status='published' AND c.category_id=? ORDER BY c.popularity DESC LIMIT 5", [$cat['category_id']]);
        $cc = q_all('SELECT ch.*, cat.name AS category FROM characters ch JOIN categories cat USING(category_id) WHERE ch.category_id=? LIMIT 2', [$cat['category_id']]);
        $set('category', "**{$cat['name']}** — {$cat['tagline']}. " . ora_excerpt($cat['description'], 140), array_merge(array_map('ora_character_card', $cc), array_map('ora_content_card', $rows)),
            [['label' => "Search {$cat['name']}", 'searchCategory' => $cat['name']], ['label' => "Recommend {$cat['name']}", 'send' => "Recommend {$cat['name']}"]]);
        return $out;
    }

    /* ---------- 12. Knowledge base (admin-managed) ---------- */
    $best = null; $bestScore = 0;
    foreach (q_all('SELECT * FROM chatbot_faq ORDER BY sort_order, faq_id') as $faq) {
        $score = 0;
        foreach (explode(',', $faq['keywords']) as $kw) if (trim($kw) !== '' && ora_has($text, $kw)) $score += 2 + substr_count(trim($kw), ' ');
        if ($faq['question'] !== '' && ora_norm($faq['question']) === $text) $score += 20;
        if ($score > $bestScore) { $bestScore = $score; $best = $faq; }
    }
    if ($best) {
        $set('faq', $best['answer'], [], array_slice(ora_default_actions(), 0, 3));
        return $out;
    }

    /* ---------- 13. Library search ---------- */
    $stop = ['what', 'when', 'where', 'which', 'about', 'there', 'their', 'with', 'have', 'does', 'show', 'tell', 'find', 'give', 'please', 'want', 'know', 'this', 'that', 'from', 'your', 'kya', 'hai', 'batao'];
    $words = array_values(array_filter(preg_split('/\s+/u', $text), fn($w) => mb_strlen($w) > 3 && !in_array($w, $stop, true)));
    if ($words) {
        $conds = []; $params = [];
        foreach (array_slice($words, 0, 5) as $i => $w) { $conds[] = "(c.title LIKE :w$i OR c.genre LIKE :g$i)"; $params["w$i"] = "%$w%"; $params["g$i"] = "%$w%"; }
        $rows = q_all("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE c.status='published' AND (" . implode(' OR ', $conds) . ') ORDER BY c.popularity DESC LIMIT 5', $params);
        $mconds = []; $mparams = [];
        foreach (array_slice($words, 0, 5) as $i => $w) { $mconds[] = "name LIKE :m$i"; $mparams["m$i"] = "%$w%"; }
        $merch = q_all('SELECT * FROM merchandise WHERE ' . implode(' OR ', $mconds) . ' LIMIT 3', $mparams);
        if ($rows || $merch) {
            $set('search', 'I found these in the library:', array_merge(array_map('ora_content_card', $rows), array_map('ora_merch_card', $merch)), [['label' => 'Search "' . ora_excerpt($msg, 28) . '"', 'search' => $msg]]);
            return $out;
        }
    }

    /* ---------- 14. Optional AI answer (Claude) ---------- */
    $ai = ora_ai_reply($msg, $user);
    if ($ai !== null) {
        $set('ai', $ai, [], array_slice(ora_default_actions(), 0, 3));
        return $out;
    }

    /* ---------- 15. Fallback ---------- */
    $set('fallback', "I'm still learning that one! I can help with characters, trending content, events, releases, merch and recommendations — or say \"give me a tour\".", [], ora_default_actions());
    $out['answered'] = false;
    return $out;
}

/** True when AI mode is configured and switched on by the admin */
function ora_ai_enabled(): bool
{
    return CHAT_AI_KEY !== '' && setting('chatbot_ai', '0') === '1' && function_exists('curl_init');
}

/**
 * Ask Claude for a short answer grounded in the site's own data.
 * Returns null when AI is disabled or the request fails (caller falls back gracefully).
 */
function ora_ai_reply(string $msg, ?array $user): ?string
{
    if (!ora_ai_enabled()) return null;

    $cats   = implode(', ', array_column(categories(), 'name'));
    $chars  = implode("\n", array_map(fn($c) => "- {$c['name']} ({$c['fandom_label']}, {$c['role']}): {$c['short_desc']}", q_all('SELECT name, fandom_label, role, short_desc FROM characters ORDER BY views DESC LIMIT 20')));
    $trend  = implode("\n", array_map(fn($c) => "- {$c['title']} [{$c['type']}]", q_all("SELECT title, type FROM contents WHERE status='published' ORDER BY popularity DESC LIMIT 12")));
    $events = implode("\n", array_map(fn($e) => "- {$e['title']} — {$e['event_date']}, {$e['venue']}, {$e['city']}", q_all('SELECT title, event_date, venue, city FROM events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 8')));
    $rels   = implode("\n", array_map(fn($r) => "- {$r['title']} ({$r['kind']}) — {$r['release_date']}", q_all('SELECT title, kind, release_date FROM releases WHERE release_date >= CURDATE() ORDER BY release_date LIMIT 8')));
    $faq    = implode("\n", array_map(fn($f) => '- ' . $f['answer'], q_all('SELECT answer FROM chatbot_faq ORDER BY sort_order LIMIT 15')));
    $name   = setting('chatbot_name', 'ORA');
    $site   = setting('platform_name', 'Fan Hub Plus');

    $system = "You are $name, the friendly assistant of $site, a fandom portal covering $cats.\n"
        . "Answer in at most 90 words, warm and concise, plain text (you may use **bold** and short bullet lines starting with •).\n"
        . "Prefer facts from the SITE DATA below; if something is not in it, say so briefly and suggest using the site search. Never invent events, dates or prices.\n"
        . "Stay on topics related to fandoms, entertainment and using this website; politely decline unrelated or unsafe requests.\n"
        . "Reply in the same language the user writes in (e.g. English, Urdu or Roman Urdu).\n"
        . ($user ? "The user's name is " . explode(' ', $user['name'])[0] . ".\n" : "The user is a visitor who is not signed in.\n")
        . "\nSITE DATA\nCharacters:\n$chars\nPopular content:\n$trend\nUpcoming events:\n$events\nUpcoming releases:\n$rels\nHelp answers:\n$faq";

    // last few exchanges for context continuity
    $uid = $user ? (int)$user['user_id'] : null;
    $hist = $uid
        ? q_all('SELECT message, response FROM (SELECT query_id, message, response FROM chatbot_queries WHERE user_id=? ORDER BY query_id DESC LIMIT 4) t ORDER BY query_id', [$uid])
        : q_all('SELECT message, response FROM (SELECT query_id, message, response FROM chatbot_queries WHERE session_id=? ORDER BY query_id DESC LIMIT 4) t ORDER BY query_id', [$_SESSION['chat_sid'] ?? '']);
    $messages = [];
    foreach ($hist as $h) { $messages[] = ['role' => 'user', 'content' => $h['message']]; $messages[] = ['role' => 'assistant', 'content' => $h['response']]; }
    $messages[] = ['role' => 'user', 'content' => $msg];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-api-key: ' . CHAT_AI_KEY, 'anthropic-version: 2023-06-01'],
        CURLOPT_POSTFIELDS => json_encode(['model' => CHAT_AI_MODEL, 'max_tokens' => 400, 'system' => $system, 'messages' => $messages], JSON_UNESCAPED_UNICODE),
    ]);
    $raw = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $code !== 200) { error_log('ORA AI error: HTTP ' . $code . ' ' . mb_substr((string)$raw, 0, 300)); return null; }
    $j = json_decode($raw, true);
    $txt = '';
    foreach ($j['content'] ?? [] as $b) if (($b['type'] ?? '') === 'text') $txt .= $b['text'];
    $txt = trim($txt);
    return $txt !== '' ? mb_substr($txt, 0, 1500) : null;
}
