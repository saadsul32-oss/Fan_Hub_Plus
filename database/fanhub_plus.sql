-- =====================================================================
--  FAN HUB PLUS  —  Fandom Universe Portal
--  MySQL / MariaDB schema + seed data
--  Import via phpMyAdmin (Import tab) or:  mysql -u root < fanhub_plus.sql
-- =====================================================================

DROP DATABASE IF EXISTS fanhub_plus;
CREATE DATABASE fanhub_plus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fanhub_plus;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- USERS  (roles: user / admin ; visitors are simply not logged in)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  user_id            INT AUTO_INCREMENT PRIMARY KEY,
  name               VARCHAR(100) NOT NULL,
  username           VARCHAR(50)  NOT NULL UNIQUE,
  email              VARCHAR(150) NOT NULL UNIQUE,
  password_hash      VARCHAR(255) NOT NULL,
  role               ENUM('user','admin') NOT NULL DEFAULT 'user',
  status             ENUM('active','blocked') NOT NULL DEFAULT 'active',
  avatar             VARCHAR(255) NULL,
  email_verified     TINYINT(1) NOT NULL DEFAULT 0,
  verify_token       VARCHAR(64) NULL,
  reset_token        VARCHAR(64) NULL,
  reset_expires      DATETIME NULL,
  pref_theme         ENUM('dark','light') NOT NULL DEFAULT 'dark',
  pref_font          DECIMAL(4,3) NOT NULL DEFAULT 1.000,
  pref_digest        ENUM('Off','Daily','Weekly','Monthly') NOT NULL DEFAULT 'Weekly',
  pref_notifications TINYINT(1) NOT NULL DEFAULT 1,
  last_login         DATETIME NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CATEGORIES (the eight fandom universes)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  category_id  INT AUTO_INCREMENT PRIMARY KEY,
  slug         VARCHAR(40)  NOT NULL UNIQUE,
  name         VARCHAR(60)  NOT NULL,
  tagline      VARCHAR(120) NOT NULL,
  description  TEXT NOT NULL,
  image        VARCHAR(500) NOT NULL,
  color        VARCHAR(20)  NOT NULL DEFAULT '#8b5cf6',
  glow         VARCHAR(40)  NOT NULL DEFAULT 'rgba(139,92,246,0.5)',
  stat1_label  VARCHAR(40)  NOT NULL DEFAULT '',
  stat1_value  VARCHAR(20)  NOT NULL DEFAULT '',
  stat2_label  VARCHAR(40)  NOT NULL DEFAULT '',
  stat2_value  VARCHAR(20)  NOT NULL DEFAULT '',
  sort_order   INT NOT NULL DEFAULT 0,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- User -> Category (favourite fandoms) : many-to-many
CREATE TABLE user_categories (
  user_id     INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY (user_id, category_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CONTENT  (articles, videos, trailers, audio) incl. fan submissions
-- ---------------------------------------------------------------------
CREATE TABLE contents (
  content_id    INT AUTO_INCREMENT PRIMARY KEY,
  category_id   INT NOT NULL,
  title         VARCHAR(200) NOT NULL,
  type          ENUM('article','video','trailer','audio') NOT NULL DEFAULT 'article',
  genre         VARCHAR(60)  NOT NULL DEFAULT '',
  image         VARCHAR(500) NOT NULL DEFAULT '',
  excerpt       TEXT NULL,
  body          MEDIUMTEXT NULL,
  media_url     VARCHAR(500) NULL,
  meta          VARCHAR(120) NULL,
  read_time     VARCHAR(30)  NULL,
  release_date  DATE NULL,
  popularity    INT NOT NULL DEFAULT 50,
  views         INT NOT NULL DEFAULT 0,
  is_featured   TINYINT(1) NOT NULL DEFAULT 0,
  is_trending   TINYINT(1) NOT NULL DEFAULT 0,
  in_gallery    TINYINT(1) NOT NULL DEFAULT 0,
  status        ENUM('published','pending','rejected') NOT NULL DEFAULT 'published',
  submitted_by  INT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE,
  FOREIGN KEY (submitted_by) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX idx_type_status (type, status),
  INDEX idx_popularity (popularity)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CHARACTER PROFILES
-- ---------------------------------------------------------------------
CREATE TABLE characters (
  character_id  INT AUTO_INCREMENT PRIMARY KEY,
  category_id   INT NOT NULL,
  name          VARCHAR(100) NOT NULL,
  fandom_label  VARCHAR(120) NOT NULL,
  role          VARCHAR(60)  NOT NULL DEFAULT '',
  image         VARCHAR(500) NOT NULL,
  short_desc    VARCHAR(255) NOT NULL DEFAULT '',
  bio           TEXT NOT NULL,
  timeline      TEXT NULL COMMENT 'one entry per line:  Label | Event',
  related       TEXT NULL COMMENT 'one related media title per line',
  in_carousel   TINYINT(1) NOT NULL DEFAULT 1,
  views         INT NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MERCHANDISE (showcase only - no purchasing)
-- ---------------------------------------------------------------------
CREATE TABLE merchandise (
  item_id       INT AUTO_INCREMENT PRIMARY KEY,
  category_id   INT NOT NULL,
  name          VARCHAR(150) NOT NULL,
  fandom_label  VARCHAR(120) NOT NULL,
  item_type     VARCHAR(40)  NOT NULL DEFAULT 'Figure',
  image         VARCHAR(500) NOT NULL,
  description   TEXT NOT NULL,
  price         VARCHAR(20)  NOT NULL DEFAULT '',
  tag           VARCHAR(40)  NOT NULL DEFAULT '' COMMENT 'Limited Edition / Pre-Order / Collectible',
  is_upcoming   TINYINT(1) NOT NULL DEFAULT 0,
  views         INT NOT NULL DEFAULT 0,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- UPCOMING RELEASES (anime, games, movies, shows, comics, merch drops)
-- ---------------------------------------------------------------------
CREATE TABLE releases (
  release_id    INT AUTO_INCREMENT PRIMARY KEY,
  category_id   INT NOT NULL,
  title         VARCHAR(150) NOT NULL,
  kind          VARCHAR(30)  NOT NULL DEFAULT 'Anime',
  release_date  DATE NOT NULL,
  image         VARCHAR(500) NOT NULL DEFAULT '',
  description   VARCHAR(255) NOT NULL DEFAULT '',
  tag           VARCHAR(40)  NOT NULL DEFAULT '',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- EVENTS (location aware)
-- ---------------------------------------------------------------------
CREATE TABLE events (
  event_id     INT AUTO_INCREMENT PRIMARY KEY,
  category_id  INT NOT NULL,
  title        VARCHAR(200) NOT NULL,
  event_date   DATE NOT NULL,
  city         VARCHAR(80)  NOT NULL,
  venue        VARCHAR(150) NOT NULL,
  latitude     DECIMAL(9,6) NULL,
  longitude    DECIMAL(9,6) NULL,
  description  TEXT NOT NULL,
  ticket_url   VARCHAR(500) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE event_saves (
  user_id    INT NOT NULL,
  event_id   INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, event_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- BOOKMARKS (+ personal notes)  —  polymorphic item reference
-- ---------------------------------------------------------------------
CREATE TABLE bookmarks (
  bookmark_id  INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  item_type    ENUM('content','character','merch') NOT NULL,
  item_id      INT NOT NULL,
  note         VARCHAR(500) NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bookmark (user_id, item_type, item_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- RATINGS on media (5-star)
-- ---------------------------------------------------------------------
CREATE TABLE ratings (
  rating_id   INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  content_id  INT NOT NULL,
  stars       TINYINT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rating (user_id, content_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (content_id) REFERENCES contents(content_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CHATBOT knowledge base + stored conversation history
-- ---------------------------------------------------------------------
CREATE TABLE chatbot_faq (
  faq_id         INT AUTO_INCREMENT PRIMARY KEY,
  question       VARCHAR(200) NOT NULL DEFAULT '',
  keywords       VARCHAR(255) NOT NULL,
  answer         TEXT NOT NULL,
  is_suggestion  TINYINT(1) NOT NULL DEFAULT 0,
  sort_order     INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE chatbot_queries (
  query_id    INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NULL,
  session_id  VARCHAR(64) NOT NULL,
  message     TEXT NOT NULL,
  response    TEXT NOT NULL,
  intent      VARCHAR(40) NOT NULL DEFAULT 'faq',
  answered    TINYINT(1) NOT NULL DEFAULT 1,
  helpful     TINYINT(1) NULL,
  meta        TEXT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX idx_session (session_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- FEEDBACK (bug / suggestion / query)
-- ---------------------------------------------------------------------
CREATE TABLE feedback (
  feedback_id  INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NULL,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL,
  type         ENUM('bug','suggestion','query') NOT NULL,
  page         VARCHAR(120) NULL,
  rating       TINYINT NULL,
  message      TEXT NOT NULL,
  status       ENUM('new','reviewed','resolved') NOT NULL DEFAULT 'new',
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ACTIVITY LOG (dashboard "recent activity" + admin stats)
-- ---------------------------------------------------------------------
CREATE TABLE activity_log (
  activity_id  INT AUTO_INCREMENT PRIMARY KEY,
  user_id      INT NOT NULL,
  action       VARCHAR(30)  NOT NULL,
  detail       VARCHAR(255) NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_user_time (user_id, created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- MEDIA LIBRARY (admin uploads)
-- ---------------------------------------------------------------------
CREATE TABLE media_files (
  media_id    INT AUTO_INCREMENT PRIMARY KEY,
  file_path   VARCHAR(500) NOT NULL,
  title       VARCHAR(150) NOT NULL DEFAULT '',
  tags        VARCHAR(255) NOT NULL DEFAULT '',
  category_id INT NULL,
  uploaded_by INT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
  FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- SETTINGS + demo mail outbox (for local XAMPP without SMTP)
-- ---------------------------------------------------------------------
CREATE TABLE settings (
  setting_key    VARCHAR(60) PRIMARY KEY,
  setting_value  TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE mail_outbox (
  mail_id     INT AUTO_INCREMENT PRIMARY KEY,
  to_email    VARCHAR(150) NOT NULL,
  subject     VARCHAR(200) NOT NULL,
  body        TEXT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Passwords:  Admin@123  (admin)   |   User@123  (all normal users)
INSERT INTO users (user_id, name, username, email, password_hash, role, email_verified, pref_digest, last_login, created_at) VALUES
(1, 'Site Administrator', 'admin',       'admin@fanhubplus.com', '$2y$10$bbyX42.nrJpqx8iSNGYKEuhSutswRypjreME.KaBvBkJAlbUeOH2.', 'admin', 1, 'Off',    NOW(), '2026-01-05 10:00:00'),
(2, 'Maya Chen',          'maya.chen',   'maya@fanhubplus.com',  '$2y$10$uCUBsCc6se7Sh5GFlsFN8.JNatUzGVo8h53emhkrTGbECU8rya4Ca', 'user',  1, 'Weekly', NOW(), '2026-02-11 18:20:00'),
(3, 'Kenji Ryu',          'kenji_r',     'kenji@fanhubplus.com', '$2y$10$uCUBsCc6se7Sh5GFlsFN8.JNatUzGVo8h53emhkrTGbECU8rya4Ca', 'user',  1, 'Weekly', DATE_SUB(NOW(), INTERVAL 2 DAY),  '2026-03-01 12:00:00'),
(4, 'Aurora Star',        'star.aurora', 'aurora@fanhubplus.com','$2y$10$uCUBsCc6se7Sh5GFlsFN8.JNatUzGVo8h53emhkrTGbECU8rya4Ca', 'user',  1, 'Daily',  DATE_SUB(NOW(), INTERVAL 5 DAY),  '2026-03-14 09:45:00'),
(5, 'Devon Kade',         'devon.k',     'devon@fanhubplus.com', '$2y$10$uCUBsCc6se7Sh5GFlsFN8.JNatUzGVo8h53emhkrTGbECU8rya4Ca', 'user',  1, 'Monthly',DATE_SUB(NOW(), INTERVAL 20 DAY), '2026-04-02 16:10:00'),
(6, 'Misa Aoki',          'misa99',      'misa@fanhubplus.com',  '$2y$10$uCUBsCc6se7Sh5GFlsFN8.JNatUzGVo8h53emhkrTGbECU8rya4Ca', 'user',  1, 'Weekly', DATE_SUB(NOW(), INTERVAL 1 DAY),  '2026-05-20 21:30:00');

INSERT INTO categories (category_id, slug, name, tagline, description, image, color, glow, stat1_label, stat1_value, stat2_label, stat2_value, sort_order) VALUES
(1, 'anime',   'Anime',   'Worlds beyond imagination', 'From shonen epics to slice-of-life gems — dive into seasonal guides, studio spotlights and character deep-dives.', 'https://images.pexels.com/photos/31971471/pexels-photo-31971471.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', '#ec4899', 'rgba(236,72,153,0.55)', 'Series tracked', '4,120', 'Active threads', '18.2K', 1),
(2, 'gaming',  'Gaming',  'Level up your universe',    'Esports coverage, patch breakdowns, indie spotlights and the builds every squad is talking about.', 'https://images.pexels.com/photos/9794458/pexels-photo-9794458.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', '#22d3ee', 'rgba(34,211,238,0.5)', 'Titles covered', '2,860', 'Live streams', '412', 2),
(3, 'movies',  'Movies',  'The big screen, closer',    'Box office pulse, director cuts, franchise timelines and the trailers everyone will be quoting.', 'https://images.pexels.com/photos/7991501/pexels-photo-7991501.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', '#f5a524', 'rgba(245,165,36,0.5)', 'Reviews', '9,340', 'Franchises', '310', 3),
(4, 'tv',      'TV',      'Binge the multiverse',      'Episode trackers, showrunner interviews and fan theories for the shows dominating the conversation.', 'https://images.pexels.com/photos/9902128/pexels-photo-9902128.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', '#8b5cf6', 'rgba(139,92,246,0.5)', 'Shows', '1,980', 'Recaps', '6,750', 4),
(5, 'kpop',    'K-Pop',   'Stages that never dim',     'Comeback trackers, idol profiles, choreography breakdowns and fandom-led chart wars.', 'https://images.pexels.com/photos/32399568/pexels-photo-32399568.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', '#ff5fa2', 'rgba(255,95,162,0.55)', 'Groups', '540', 'Comebacks', '128', 5),
(6, 'comics',  'Comics',  'Panels of pure power',      'Universe-spanning storylines, artist spotlights and the crossover events fans are dissecting.', 'https://images.pexels.com/photos/11317071/pexels-photo-11317071.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', '#f5384e', 'rgba(245,56,78,0.5)', 'Issues indexed', '22K', 'Universes', '34', 6),
(7, 'manga',   'Manga',   'Ink that moves worlds',     'Chapter-by-chapter breakdowns, mangaka spotlights and the next arcs everyone is theorizing about.', 'https://images.pexels.com/photos/1707581/pexels-photo-1707581.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', '#c9c9d8', 'rgba(201,201,216,0.4)', 'Titles', '3,400', 'Chapters', '310K', 7),
(8, 'cosplay', 'Cosplay', 'Bring characters to life',  'Build guides, convention coverage, spotlight interviews and prop-making tutorials from top creators.', 'https://images.pexels.com/photos/29901451/pexels-photo-29901451.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', '#34d399', 'rgba(52,211,153,0.5)', 'Builds featured', '1,240', 'Conventions', '86', 8);

INSERT INTO user_categories (user_id, category_id) VALUES (2,1),(2,5),(2,6),(3,1),(3,2),(4,5),(5,3),(5,4),(6,8),(6,7);

-- ARTICLES ------------------------------------------------------------
INSERT INTO contents (content_id, category_id, title, type, genre, image, excerpt, body, read_time, release_date, popularity, views, is_featured, is_trending, in_gallery) VALUES
(1, 1, 'Inside the Studio Reinventing Anime Production Pipelines', 'article', 'Industry', 'https://images.pexels.com/photos/31971471/pexels-photo-31971471.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'How a small studio''s hybrid 2D/3D pipeline is quietly changing what''s possible on a TV budget.',
 'For decades, television anime has lived under brutal schedule and budget constraints. A new generation of studios is challenging that assumption with hybrid pipelines that blend hand-drawn key frames with procedural in-betweening.\n\nThe result isn''t just efficiency — it''s a distinct visual language. Directors are leaning into the technique''s strengths: fluid camera movement, dynamic lighting, and compositing tricks that would have taken months to render by hand.\n\nWe spoke to pipeline supervisors, in-between animators, and colorists to understand how the workflow actually functions day to day, and what it means for the next five years of the medium.',
 '8 min read', '2026-03-12', 76, 1840, 1, 0, 0),
(2, 2, 'The Silent Rise of Solo-Developed Roguelikes', 'article', 'Roguelike', 'https://images.pexels.com/photos/14266493/pexels-photo-14266493.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'Why one-person teams keep topping the charts in the genre''s most competitive year yet.',
 'The roguelike genre has never been more crowded, yet solo developers keep finding ways to break through with tightly-scoped, deeply personal projects.\n\nWe break down three breakout titles from the past year and the design philosophy connecting them.',
 '6 min read', '2026-03-09', 71, 1210, 0, 0, 0),
(3, 3, 'Every Franchise Timeline, Finally Untangled', 'article', 'Sci-Fi', 'https://images.pexels.com/photos/33162637/pexels-photo-33162637.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'A definitive, spoiler-marked chronology for the universe''s most confusing timeline.',
 'With five spin-offs, two prequels and a non-linear main saga, keeping track of canon has become a genre unto itself.\n\nHere''s the full order — theatrical, chronological, and ''first-watch recommended''.',
 '10 min read', '2026-03-06', 68, 990, 0, 0, 0),
(4, 5, 'The Comeback Era That Broke Streaming Records', 'article', 'Pop', 'https://images.pexels.com/photos/8041259/pexels-photo-8041259.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'A closer look at the choreography, production, and fan-driven rollout strategy.',
 'Every comeback era tells a story in three acts: teaser, reveal, and afterglow. This one broke the mold entirely.\n\nWe map the release strategy hour by hour, and why it worked.',
 '5 min read', '2026-03-02', 74, 1502, 0, 0, 0),
(5, 6, 'Crossover Events Are Getting Too Big — Or Are They?', 'article', 'Superhero', 'https://images.pexels.com/photos/36398813/pexels-photo-36398813.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'An editorial on scale, stakes, and why bigger doesn''t always mean better.',
 'Universe-ending stakes used to be rare. Now they''re annual. We ask writers and editors whether escalation has a ceiling.',
 '7 min read', '2026-02-27', 63, 804, 0, 0, 0);

-- TRENDING --------------------------------------------------------------
INSERT INTO contents (content_id, category_id, title, type, genre, image, excerpt, body, media_url, meta, read_time, release_date, popularity, views, is_trending) VALUES
(6,  1, 'Inside the Final Arc: What the Leaks Really Mean', 'article', 'Shonen', 'https://images.pexels.com/photos/31971471/pexels-photo-31971471.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'Separating credible leaks from wishful thinking ahead of the final arc.',
 'Every long-running series eventually reaches the arc fans have been theorizing about for years, and this one is no exception. Leaked storyboards have been circulating for weeks.\n\nWe compared the leaks against the studio''s known production schedule and the source material to see which claims actually hold up.', NULL, NULL, '4 min read', '2026-09-18', 98, 5210, 1),
(7,  2, 'World Championship Grand Finals — Full Recap', 'video', 'Esports', 'https://images.pexels.com/photos/14266493/pexels-photo-14266493.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'Every clutch round from the grand finals, condensed.', NULL, 'assets/media/video/here-we-are.mp4', 'Recap · 12 min', NULL, '2026-09-12', 95, 4820, 1),
(8,  3, 'Teaser Trailer #2 Breakdown, Shot by Shot', 'trailer', 'Sci-Fi', 'https://images.pexels.com/photos/29508640/pexels-photo-29508640.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'Frame-by-frame analysis of the newest teaser.', NULL, 'assets/media/video/sintel-part-1.mp4', 'Trailer · 3 min', NULL, '2026-09-08', 91, 4400, 1),
(9,  5, 'Comeback Album — First Listen Reaction', 'audio', 'Pop', 'https://images.pexels.com/photos/36481094/pexels-photo-36481094.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'Our hosts react track by track.', NULL, 'assets/media/audio/outfoxing.mp3', 'Podcast · 35 min', NULL, '2026-09-05', 89, 3950, 1),
(10, 6, 'The Multiverse Event Nobody Saw Coming', 'article', 'Superhero', 'https://images.pexels.com/photos/36398813/pexels-photo-36398813.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'How a quiet one-shot became the year''s biggest crossover.',
 'Nobody expected a one-shot anthology issue to reshape an entire publishing line, but that is exactly what happened this summer.\n\nWe trace the event from its first solicitation to the final page, and what it sets up for next year.', NULL, NULL, '6 min read', '2026-08-30', 87, 3610, 1),
(11, 4, 'Season Finale Ending Explained', 'video', 'Drama', 'https://images.pexels.com/photos/9902128/pexels-photo-9902128.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'That final scene, unpacked.', NULL, 'assets/media/video/sintel-part-2.mp4', 'Explainer · 9 min', NULL, '2026-08-24', 84, 3300, 1),
(12, 8, 'Backstage at the Biggest Con of the Year', 'video', 'Convention', 'https://images.pexels.com/photos/29901451/pexels-photo-29901451.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'Behind the curtain with the masquerade finalists.', NULL, 'assets/media/video/flower.mp4', 'Vlog · 15 min', NULL, '2026-08-19', 80, 2980, 1),
(13, 7, 'New Chapter Drop: Fandom Reacts Live', 'article', 'Fantasy', 'https://images.pexels.com/photos/1707581/pexels-photo-1707581.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'The live reaction thread, summarized.',
 'The newest chapter landed at midnight and the fandom did what it does best: speculate loudly and in real time.\n\nHere are the moments that lit up the live thread and the theories that gained the most traction.', NULL, NULL, '3 min read', '2026-08-15', 78, 2710, 1);

-- MEDIA: featured trailer + video gallery + audio ---------------------------
INSERT INTO contents (content_id, category_id, title, type, genre, image, excerpt, media_url, meta, release_date, popularity, views, is_featured, in_gallery) VALUES
(14, 7, 'Thornbound: The Animated Chronicles', 'trailer', 'Fantasy', 'https://images.pexels.com/photos/10109585/pexels-photo-10109585.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=1200&w=1800', 'The featured trailer for the animated adaptation.', 'assets/media/video/big-buck-bunny.mp4', 'Featured Trailer', '2026-09-20', 90, 2600, 1, 0),
(15, 1, 'Ashen Requiem — Official Season Finale Trailer', 'trailer', 'Shonen', 'https://images.pexels.com/photos/31971471/pexels-photo-31971471.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Official finale trailer.', 'assets/media/video/sintel-part-1.mp4', 'Trailer · 2 min', '2026-09-01', 82, 2100, 0, 1),
(16, 2, 'World Championship — Opening Ceremony', 'video', 'Esports', 'https://images.pexels.com/photos/14266493/pexels-photo-14266493.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'The full opening ceremony.', 'assets/media/video/here-we-are.mp4', 'Video · 18 min', '2026-08-28', 77, 1880, 0, 1),
(17, 5, 'Nova Eclipse — ''Glasshouse'' Live Performance', 'video', 'Pop', 'https://images.pexels.com/photos/32399568/pexels-photo-32399568.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Live stage performance.', 'assets/media/video/flower.mp4', 'Video · 4 min', '2026-08-20', 79, 1990, 0, 1),
(18, 3, 'Midnight Sentinel — Teaser #3', 'trailer', 'Superhero', 'https://images.pexels.com/photos/33162637/pexels-photo-33162637.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Third teaser for the premiere.', 'assets/media/video/sintel-part-2.mp4', 'Trailer · 1 min', '2026-08-10', 73, 1500, 0, 1),
(19, 5, 'Eclipse Protocol — Full Album Stream', 'audio', 'Pop', 'https://images.pexels.com/photos/36481094/pexels-photo-36481094.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Full album stream.', 'assets/media/audio/chasing-corporate.mp3', 'Nova Eclipse · 42 min', '2026-09-10', 75, 1700, 0, 1),
(20, 1, 'Ashen Requiem OST — Main Theme', 'audio', 'Soundtrack', 'https://images.pexels.com/photos/31971471/pexels-photo-31971471.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Main theme from the OST.', 'assets/media/audio/sintel-score.mp3', 'Studio Kessho · 4 min', '2026-07-30', 70, 1320, 0, 1),
(21, 4, 'The Fandom Cast — Ep. 112', 'audio', 'Podcast', 'https://images.pexels.com/photos/36481094/pexels-photo-36481094.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Weekly fandom podcast.', 'assets/media/audio/viper.mp3', 'Podcast · 58 min', '2026-09-22', 66, 900, 0, 1);

-- A pending fan submission (for admin moderation demo)
INSERT INTO contents (content_id, category_id, title, type, genre, image, excerpt, body, read_time, release_date, popularity, status, submitted_by) VALUES
(22, 2, 'My 100-Hour Journey Through Titanfall Zero', 'article', 'Action', 'https://images.pexels.com/photos/9794458/pexels-photo-9794458.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400',
 'A fan diary of every drop, loss and victory.', 'I started Titanfall Zero expecting a quick shooter and ended up with a hundred hours on the clock.\n\nThis is a diary of what kept me coming back.', '5 min read', '2026-09-21', 40, 'pending', 3);

-- CHARACTERS ------------------------------------------------------------
INSERT INTO characters (character_id, category_id, name, fandom_label, role, image, short_desc, bio, timeline, related, views) VALUES
(1, 1, 'Kaien Voss', 'Anime · Ashen Requiem', 'Protagonist', 'images/characters/hero1.jpg', 'A wandering swordsman chasing the ghost of his forgotten kingdom.',
 'Kaien Voss wields the Ashen Blade, a weapon forged from the remains of a fallen star. Once heir to a kingdom erased from every map, he now travels the shattered continents searching for the truth behind its disappearance — and the people responsible for it.',
 'Arc 1 | Awakens with no memory beside the ruins of Veyrenhall.\nArc 3 | Forms an uneasy alliance with the Ember Clan.\nArc 7 | Confronts the truth about the Ashen Blade''s origin.',
 'Ashen Requiem: Season 2\nThe Ember Clan Explained\nKaien Voss Fan Art Collection', 3120),
(2, 2, 'Rook-9', 'Gaming · Titanfall Zero', 'Mech Pilot', 'images/characters/hero2.jpg', 'An elite pilot bonded to a sentient war machine on a dying frontier.',
 'Rook-9 is the last certified pilot of the Colossus-class titans, machines built to end the Frontier War. Bonded neurally to his titan, ECHO, Rook fights to protect the last free colonies from corporate occupation — one drop at a time.',
 'Ch. 1 | Recruited into the Frontier Militia.\nCh. 4 | Bonds permanently with ECHO after a near-fatal drop.\nCh. 9 | Leads the assault on the Orbital Foundry.',
 'Titanfall Zero: Ranked Season 6\nBest Rook-9 Loadouts\nECHO Lore Explained', 2480),
(3, 6, 'Nyx Corvain', 'Comics · Midnight Sentinel', 'Anti-Hero', 'images/characters/hero3.jpg', 'A vigilante fueled by stolen power, protecting a city that fears her.',
 'Once a scientist, Nyx Corvain absorbed an experimental energy source that grants her devastating power at a personal cost. She now polices the underworld of Corvain City by night, feared by criminals and civilians alike.',
 'Vol. 1 | Origin — The Reactor Incident.\nVol. 5 | First clash with the Sentinel Council.\nVol. 12 | The Blackout Crossover Event.',
 'Midnight Sentinel: Blackout Event\nNyx Corvain Power Ranking\nCorvain City Map', 2710),
(4, 5, 'Yuna Park', 'K-Pop · Nova Eclipse', 'Lead Vocalist', 'images/characters/hero4.jpg', 'The voice behind Nova Eclipse''s record-breaking comeback era.',
 'Yuna Park debuted at 17 and has since become one of the genre''s most celebrated vocalists, known for blending traditional Korean melodic phrasing with futuristic production. Her solo stages are legendary among fans for their raw emotional delivery.',
 '2021 | Debuts with Nova Eclipse.\n2023 | First solo showcase — ''Glasshouse''.\n2025 | Leads the ''Eclipse Protocol'' world tour.',
 'Nova Eclipse Comeback Timeline\nYuna Park Vocal Analysis\nEclipse Protocol Tour Dates', 2950),
(5, 7, 'Sable Ashworth', 'Manga · Thornbound', 'Sorceress', 'images/characters/hero5.jpg', 'A forest witch bound by an ancient pact she never agreed to.',
 'Sable inherited her power from a pact signed centuries before her birth, tying her fate to the dying forest she protects. Her story explores the cost of inherited duty and the search for a way to rewrite a destiny she never chose.',
 'Ch. 1 | Discovers the pact mark on her hand.\nCh. 40 | Meets the last of the Thornbound Order.\nCh. 88 | Chooses to break the pact at great cost.',
 'Thornbound Chapter 90 Predictions\nThe Pact Explained\nSable Ashworth Fan Theories', 2230);

-- MERCHANDISE -----------------------------------------------------------
INSERT INTO merchandise (item_id, category_id, name, fandom_label, item_type, image, description, price, tag, is_upcoming, views) VALUES
(1, 1, 'Ashen Blade Collector''s Figure', 'Anime · Ashen Requiem', 'Figure', 'images/merch/figure.jpg', '1/7 scale premium figure with interchangeable hands and display base, hand-painted details.', '$189.00', 'Limited Edition', 0, 1640),
(2, 4, 'Fandom Universe Signature Hoodie', 'Fan Hub Plus', 'Apparel', 'images/merch/apparel.jpg', 'Heavyweight cotton hoodie with reactive glow-thread embroidery.', '$74.00', 'Collectible', 0, 980),
(3, 5, 'Nova Eclipse — Eclipse Protocol Vinyl', 'K-Pop · Nova Eclipse', 'Vinyl', 'images/merch/vinyl.jpg', 'Limited holographic pressing with foil-stamped gatefold sleeve.', '$52.00', 'Pre-Order', 1, 1320),
(4, 7, 'Thornbound Chapter One Art Print', 'Manga · Thornbound', 'Print', 'images/merch/poster.jpg', 'Museum-grade giclée print, numbered edition of 500, framed.', '$96.00', 'Collectible', 0, 760);

-- UPCOMING RELEASES -----------------------------------------------------
INSERT INTO releases (category_id, title, kind, release_date, image, description, tag) VALUES
(1, 'Ashen Requiem — Season 3',            'Anime', '2026-10-09', 'https://images.pexels.com/photos/31971471/pexels-photo-31971471.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'The final season of the dark-fantasy hit.', 'Anticipated'),
(2, 'Titanfall Zero: Frontier Edition',    'Game',  '2026-11-14', 'https://images.pexels.com/photos/9794458/pexels-photo-9794458.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Remastered campaign with new titan classes.', 'Pre-Order'),
(3, 'Midnight Sentinel: Blackout',         'Movie', '2026-12-04', 'https://images.pexels.com/photos/7991501/pexels-photo-7991501.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'The comic crossover hits theaters.', 'Anticipated'),
(4, 'Orbit Station — Season 2',            'Show',  '2027-01-15', 'https://images.pexels.com/photos/9902128/pexels-photo-9902128.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'The sci-fi drama returns with ten episodes.', ''),
(6, 'Midnight Sentinel #50 (Variant)',     'Comic', '2026-10-28', 'https://images.pexels.com/photos/11317071/pexels-photo-11317071.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Anniversary issue with foil variant cover.', 'Collectible'),
(5, 'Nova Eclipse Lightstick v2',          'Merch', '2026-11-01', 'images/merch/vinyl.jpg', 'Official lightstick drop for the world tour.', 'Limited Edition');

-- EVENTS (future dates, with coordinates for map / near-me) -------------
INSERT INTO events (category_id, title, event_date, city, venue, latitude, longitude, description, ticket_url) VALUES
(4, 'Fandom Universe Live: Season Finale Watch Party', '2026-10-18', 'Online',       'Global Livestream',            NULL,      NULL,       'Join thousands of fans for a synchronized watch party with live reactions and cast interviews.', 'https://example.com/tickets/watch-party'),
(8, 'Karachi Fandom Fest 2026',                         '2026-10-24', 'Karachi',      'Expo Centre Karachi',          24.903600, 67.076500,  'Pakistan''s biggest weekend of cosplay, anime screenings, gaming tournaments and artist alley.', 'https://example.com/tickets/karachi-fandom-fest'),
(8, 'Lahore Cosplay Meetup',                            '2026-11-07', 'Lahore',       'Expo Centre Lahore',           31.467600, 74.265800,  'Community cosplay meetup with photo walks, prop workshops and a mini masquerade.', 'https://example.com/tickets/lahore-cosplay'),
(1, 'Anime Night Screening — Islamabad',                '2026-11-21', 'Islamabad',    'Centaurus Cineplex',           33.707700, 73.049800,  'A double-feature anime screening night with fan trivia between films.', 'https://example.com/tickets/isb-anime-night'),
(8, 'Nexus Comic & Cosplay Convention',                 '2026-12-05', 'Los Angeles',  'Los Angeles Convention Center', 34.040300, -118.269600, 'Three days of panels, artist alleys, and the year''s most anticipated cosplay masquerade.', 'https://example.com/tickets/nexus-con'),
(2, 'World Esports Championship — Grand Finals',        '2027-01-23', 'Seoul',        'Seoul Arena',                  37.519900, 127.127300, 'The top 8 teams battle for a record prize pool in front of a sold-out arena.', 'https://example.com/tickets/wec-finals'),
(5, 'Eclipse Protocol World Tour Kickoff',              '2027-02-14', 'Tokyo',        'Tokyo Dome',                   35.705600, 139.751900, 'Nova Eclipse opens their biggest tour yet with an all-new stage production.', 'https://example.com/tickets/eclipse-protocol'),
(3, 'Midnight Sentinel: Blackout — Premiere Night',     '2027-03-06', 'Multiple Cities', 'Multiple Cities',           NULL,      NULL,       'The comic saga''s biggest crossover event hits theaters worldwide.', 'https://example.com/tickets/blackout-premiere');

-- BOOKMARKS / RATINGS / CALENDAR for Maya ------------------------------
INSERT INTO bookmarks (user_id, item_type, item_id, note) VALUES
(2, 'content', 1, 'Great deep-dive — share with the study group'),
(2, 'content', 4, NULL),
(2, 'character', 1, 'Favourite protagonist'),
(2, 'merch', 1, 'Birthday wishlist');

INSERT INTO ratings (user_id, content_id, stars) VALUES (2,14,5),(3,14,4),(4,17,5),(2,7,4),(5,8,4),(6,12,5);
INSERT INTO event_saves (user_id, event_id) VALUES (2,2),(2,7);

INSERT INTO activity_log (user_id, action, detail, created_at) VALUES
(2, 'Followed',   'Nova Eclipse',                                              DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 'Watched',    'World Championship Grand Finals — Full Recap',              DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 'Bookmarked', 'Ashen Blade Collector''s Figure',                           DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(2, 'Read',       'Inside the Studio Reinventing Anime Production Pipelines',  DATE_SUB(NOW(), INTERVAL 2 HOUR));

-- CHATBOT ---------------------------------------------------------------
INSERT INTO chatbot_faq (question, keywords, answer, is_suggestion, sort_order) VALUES
('What''s trending in anime this week?', 'trending,popular,hot', 'Right now the community is buzzing about the Ashen Requiem finale leaks and the World Esports Championship grand finals. Want me to pull up the full trending list?', 1, 1),
('Recommend a show like Ashen Requiem', 'recommend,suggest,similar,like', 'If you enjoyed Ashen Requiem, you''ll probably love Thornbound for its mythic tone, or Titanfall Zero if you want something more action-driven. Want a personalized recommendation based on your favorites?', 1, 2),
('When is the next K-Pop comeback?', 'kpop,k-pop,comeback', 'Nova Eclipse just kicked off their ''Eclipse Protocol'' era — the world tour starts February 14th in Tokyo. I can add it to your events calendar!', 1, 3),
('Show me upcoming conventions near me', 'convention,event,near', 'Karachi Fandom Fest is coming up on October 24th at Expo Centre Karachi, and the Nexus Comic & Cosplay Convention follows in December. Open the Events section and tap "Near me" to sort events by distance.', 1, 4),
('', 'merch,buy,shop', 'Our Merchandise Showcase highlights collector pieces like the Ashen Blade figure and the Eclipse Protocol vinyl. It''s a showcase experience — tap any item for full details.', 0, 5),
('', 'hello,hi,hey', 'Hey there! I''m ORA, your Fandom Universe guide. Ask me about trending content, characters, events or recommendations.', 0, 6),
('', 'register,sign up,signup,account,login', 'Tap LOGIN in the top bar and choose "Create an account". Once your email is verified you can bookmark items, rate media, save events and submit fan content.', 0, 7),
('', 'bookmark,save,saved', 'Hit the bookmark icon on any article, character or merch item. Everything you save (with your notes) appears in your Dashboard.', 0, 8),
('', 'feedback,bug,report,contact', 'Scroll to the footer and open Contact / Feedback. You can report a bug, share a suggestion or ask a query — our team reviews every message.', 0, 9),
('', 'submit,fan art,write,contribute', 'Registered fans can submit articles from the Dashboard ("Submit fan content"). An admin reviews each submission before it goes live.', 0, 10);

-- FEEDBACK --------------------------------------------------------------
INSERT INTO feedback (user_id, name, email, type, page, rating, message, status, created_at) VALUES
(3, 'Kenji Ryu',   'kenji@fanhubplus.com',  'suggestion', NULL, 5, 'The character detail modal is gorgeous — would love a share button.', 'new', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(4, 'Aurora Star', 'aurora@fanhubplus.com', 'suggestion', NULL, 5, 'Search filters are super fast, exactly what I needed.', 'reviewed', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 'Devon Kade',  'devon@fanhubplus.com',  'bug', 'Theme toggle', 4, 'Would like dark/light toggle to persist across sessions.', 'resolved', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(6, 'Misa Aoki',   'misa@fanhubplus.com',   'query', NULL, 4, 'Events calendar sync would be amazing for conventions.', 'new', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO chatbot_queries (user_id, session_id, message, response, created_at) VALUES
(3, 'seed-1', 'What''s trending in anime this week?', 'Right now the community is buzzing about the Ashen Requiem finale leaks and the World Esports Championship grand finals. Want me to pull up the full trending list?', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(NULL, 'seed-2', 'hello', 'Hey there! I''m ORA, your Fandom Universe guide. Ask me about trending content, characters, events or recommendations.', DATE_SUB(NOW(), INTERVAL 1 DAY));

INSERT INTO settings (setting_key, setting_value) VALUES
('platform_name', 'Fan Hub Plus'),
('support_email', 'support@fanhubplus.io'),
('chatbot_name', 'ORA'),
('chatbot_welcome', 'Hey, I''m ORA — your Fandom Universe guide. Ask me about characters, trending content, events, merch or recommendations.'),
('chatbot_ai', '0'),
('schema_version', '4');

-- MEDIA LIBRARY seed (local images, tagged + categorised by admin)
INSERT INTO media_files (file_path, title, tags, category_id, uploaded_by) VALUES
('images/hero-universe.jpg',        'Hero — Universe backdrop',  'hero,backdrop', NULL, 1),
('images/characters/hero1.jpg',     'Kaien Voss portrait',       'character,portrait', 1, 1),
('images/characters/hero2.jpg',     'Rook-9 portrait',           'character,portrait', 2, 1),
('images/characters/hero3.jpg',     'Nyx Corvain portrait',      'character,portrait', 6, 1),
('images/characters/hero4.jpg',     'Yuna Park portrait',        'character,portrait', 5, 1),
('images/characters/hero5.jpg',     'Sable Ashworth portrait',   'character,portrait', 7, 1),
('images/merch/figure.jpg',         'Ashen Blade figure',        'merch,figure,limited edition', 1, 1),
('images/merch/apparel.jpg',        'Signature hoodie',          'merch,apparel', 4, 1),
('images/merch/vinyl.jpg',          'Eclipse Protocol vinyl',    'merch,vinyl,pre-order', 5, 1),
('images/merch/poster.jpg',         'Thornbound art print',      'merch,print,collectible', 7, 1);
