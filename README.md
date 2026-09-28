# Fan Hub Plus — PHP + MySQL version

Original React/TypeScript front-end converted to **PHP 8 + MySQL** with the same interface
(identical HTML structure + Tailwind classes, precompiled to `assets/css/app.css`).

## Installation (XAMPP)
1. Copy the `fanhub` folder to `C:\xampp\htdocs\fanhub`
2. Start **Apache** and **MySQL** from the XAMPP control panel
3. Open http://localhost/phpmyadmin → **Import** → choose `database/fanhub_plus.sql` → Go
   (it creates the database `fanhub_plus` with all tables and demo data)
4. If your MySQL root user has a password, edit `includes/config.php` (DB_USER / DB_PASS)
5. Open http://localhost/fanhub/  — Admin panel: http://localhost/fanhub/admin/

Requirements: PHP 8.0+ with `pdo_mysql` and `fileinfo` (both enabled in XAMPP), MySQL 5.7+ / MariaDB 10.3+.
Internet is needed for Google Fonts, Pexels demo images and the event map (Leaflet/CARTO). Demo videos and audio are bundled in `assets/media/`, so they play offline.

## Login credentials
| Role | Email | Password |
|------|-------|----------|
| Administrator | admin@fanhubplus.com | Admin@123 |
| Registered user | maya@fanhubplus.com | User@123 |
| Registered user | kenji@fanhubplus.com | User@123 |
| Registered user | aurora@fanhubplus.com | User@123 |
| Registered user | devon@fanhubplus.com | User@123 |
| Registered user | misa@fanhubplus.com | User@123 |
| Visitor | (no login) | — |

## Admin: Site Control (full control of the live site)
The admin console (`admin/` → **Site Control**, under System in the sidebar) now controls the public
site directly — no code edits needed:
- **Maintenance mode** — take the site offline for everyone except admins, with a custom message.
- **Homepage hero** — eyebrow badge text, subheadline and background image.
- **SEO** — the default meta title / description used across the site (and for social share previews).
- **Footer** — tagline text and social links (Twitter/X, Instagram, YouTube, Discord, TikTok) — a
  platform's icon and link only appear once its URL is filled in.
- **Announcement banner** — an optional dismiss-free strip above the navbar, with an optional link.

All of it is stored in the existing `settings` table (no new tables, no manual SQL) and is picked up
automatically the next time the schema check runs (`ensure_schema()`), so it works on both fresh
imports and existing databases.

## Admin: one login, no separate admin URL to remember
You don't have to open `admin/login.php` by hand. Sign in from the **same login on the homepage**
(the account button in the navbar): if the email/password belongs to an Administrator account, you're
sent straight to `admin/` after signing in. A regular user account still lands back on the homepage
with the normal member dashboard (the "Fandom Command Center" panel) — nothing changes for fans.
This works because `api/auth.php` already returns whether the account is an admin, and the homepage's
login form (`assets/js/app.js`) now checks that flag and redirects.

## Email (verification / forgot password)
XAMPP has no mail server, so `MAIL_DEMO = true` in `includes/config.php` shows the tokenised link
on screen and stores every email in the `mail_outbox` table. Set it to `false` on a real server with SMTP.

## Folder structure
```
index.php              Home page (all sections, server-rendered from MySQL)
sitemap.php            Sitemap (linked in the footer)
verify.php             Email verification (token link)
reset_password.php     Password reset (token link, 1 hour expiry)
includes/              config, DB + helpers, icons, partials (one per original React component), modal fragments
api/                   AJAX endpoints: auth, search, fragment, bookmark, rate, calendar, chat, feedback, profile, submit, track
admin/                 Admin console: login, CRUD, moderation, media library, analytics, settings
assets/css/app.css     Compiled Tailwind CSS      assets/js/app.js   Front-end behaviour (vanilla JS)
database/fanhub_plus.sql   Schema + seed data
uploads/               Avatars and uploaded media
src/input.css          Tailwind source (only needed if you change classes)
```

## Rebuilding CSS (only if you add new Tailwind classes)
```
npm install tailwindcss@4.1.17 @tailwindcss/cli@4.1.17
npx @tailwindcss/cli -i src/input.css -o assets/css/app.css --minify
```

## What's new in v2
**Characters section (fixed & redesigned)**
- The old 3D cylinder was off-centre and overlapped the dots, hint text and grid; the "active" card was also wrong while it auto-rotated. It is replaced by a centred 3D coverflow spotlight (drag / swipe / arrow keys / dots / prev-next, autoplay with progress bar that pauses on hover) with an info panel for the active character.
- Character grid: live search, category chips with counts, fandom dropdown, sort (Featured / Most viewed / A–Z), empty state + reset, bookmark button on every card, descriptions visible on touch devices.
- Profile modal: prev / next, share link, "You may also like", richer timeline. Deep links work: `index.php?character=3` (also `?article=`, `?merch=`, `?media=`).

**ORA chatbot (fully working)**
- Understands greetings, help, guided tour, characters ("who is Kaien Voss"), events by city ("events in Lahore"), releases, merch, trending, recommendations (personalised for signed-in fans), fandom categories, content types and the admin knowledge base, with a library-search fallback.
- Replies include clickable cards (open profiles / articles / merch) and quick-action chips; thumbs up / down feedback, new-conversation button, voice input (where the browser supports it), history restore, rate limiting.
- Optional AI answers with Claude: set `CHAT_AI_KEY` in `includes/config.php` (or env `FH_AI_KEY`), then switch **AI answers** on in Admin → Chatbot. Without a key everything still works from your own data.

**Admin console (redesigned to the site theme)**
- Void background with aurora glow, glass panels, Bebas display type and violet → magenta → cyan accents; grouped sidebar, sticky top bar, mobile menu.
- Overview dashboard (greeting, stats, alerts, traffic, events, activity feed, top characters), visual character manager with portraits and spotlight badges, image preview in forms, switches for yes/no fields.
- Chatbot tab: answer-rate and helpfulness stats, assistant name / welcome message / AI toggle, a "learning queue" of questions ORA couldn't answer with a one-click **Teach answer**, intent breakdown and transcripts.

**Upgrading an existing database** — nothing to do: missing columns/settings are added automatically on the first page load. (A fresh import of `database/fanhub_plus.sql` already includes them.)

## What's new in v3
**Video & audio playback fixed** — the old demo links (Google `gtv-videos-bucket`, MDN, SoundHelix) stopped working. The files now ship in `assets/media/` and existing databases are updated automatically on the first page load (home page or admin). Media URLs can be a path like `assets/media/video/clip.mp4`, a full MP4/MP3 link, or a YouTube/Vimeo link.

**Logo** — the official FAN HUB PLUS logo is in `images/brand/` (full, horizontal, icon, light-mode versions and favicons). It is used in the site navbar, footer, browser tab icon, and the admin sidebar and login page.

**Admin console redesign** — the admin now has its own "control room" look, separate from the cinematic user site: solid dark sidebar with the logo, compact top bar, Space Grotesk headings, and violet → blue accents taken from the logo. Styles live in `admin/assets/admin.css` (the public site's `app.css` is untouched). Admin → Media Library now lists every video and audio file with a player and a load check; Content rows with media get a play button; the add/edit form previews the media URL live.
