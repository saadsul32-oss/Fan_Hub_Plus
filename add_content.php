<?php
/**
 * Script to add more content to the database to increase quantities
 */
require 'includes/functions.php';
$conn = new mysqli('localhost', 'root', '', 'fanhub_plus');

// Add more movies (category_id=3 for movies) - individual inserts
$movies = [
  [3, 'New Sci-Fi Epic Trailer', 'trailer', 'Sci-Fi', 'https://images.pexels.com/photos/33162637/pexels-photo-33162637.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'A new sci-fi epic is coming', NULL, NULL, '2026-10-15', 85, 0, 0, 0],
  [3, 'Action Blockbuster Review', 'article', 'Action', 'https://images.pexels.com/photos/33162637/pexels-photo-33162637.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Review of the latest action blockbuster', NULL, NULL, '2026-11-01', 80, 0, 0, 0],
  [3, 'Comedy Film Spotlight', 'article', 'Comedy', 'https://images.pexels.com/photos/33162637/pexels-photo-33162637.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Highlighting the funniest films this year', NULL, NULL, '2026-12-01', 75, 0, 0, 0],
  [3, 'Animation Showcase', 'article', 'Industry', 'https://images.pexels.com/photos/33162637/pexels-photo-33162637.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Showcasing the best animated films', NULL, NULL, '2026-09-20', 70, 0, 0, 0],
  [3, 'Horror Film Review', 'article', 'Horror', 'https://images.pexels.com/photos/33162637/pexels-photo-33162637.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Review of the scariest horror films', NULL, NULL, '2026-08-15', 65, 0, 0, 0],
];

foreach ($movies as $m) {
  $conn->query("INSERT INTO contents (category_id, title, type, genre, image, excerpt, body, read_time, release_date, popularity, views, is_featured, is_trending, in_gallery) VALUES (
    " . $m[0] . ", '" . $conn->real_escape_string($m[1]) . "', '" . $m[2] . "', '" . $m[3] . "', '" . $m[4] . "', '" . $m[5] . "', " . ($m[6] !== NULL ? "'" . $conn->real_escape_string($m[6]) . "'" : 'NULL') . ", " . ($m[7] !== NULL ? "'" . $conn->real_escape_string($m[7]) . "'" : 'NULL') . ", '" . $m[8] . "', " . $m[9] . ", " . $m[10] . ", " . $m[11] . ", " . $m[12] . ", " . $m[13] . "
  )");
}

// Add more TV shows (category_id=4 for TV)
$tvShows = [
  [4, 'New Series Premiere', 'video', 'Drama', 'https://images.pexels.com/photos/9902128/pexels-photo-9902128.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Premiere of a new drama series', NULL, NULL, NULL, 88, 0, 0, 0],
  [4, 'Season Finale Explained', 'video', 'Drama', 'https://images.pexels.com/photos/9902128/pexels-photo-9902128.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Explaining the season finale', NULL, NULL, NULL, 82, 0, 0, 0],
  [4, 'Reality Show Update', 'audio', 'Podcast', 'https://images.pexels.com/photos/36481094/pexels-photo-36481094.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Latest updates from reality shows', NULL, NULL, 'Podcast · 45 min', 72, 0, 0, 0],
];

foreach ($tvShows as $t) {
  $conn->query("INSERT INTO contents (category_id, title, type, genre, image, excerpt, body, read_time, release_date, popularity, views, is_featured, is_trending, in_gallery) VALUES (
    " . $t[0] . ", '" . $conn->real_escape_string($t[1]) . "', '" . $t[2] . "', '" . $t[3] . "', '" . $t[4] . "', '" . $t[5] . "', " . ($t[6] !== NULL ? "'" . $conn->real_escape_string($t[6]) . "'" : 'NULL') . ", " . ($t[7] !== NULL ? "'" . $conn->real_escape_string($t[7]) . "'" : 'NULL') . ", '" . $t[8] . "', " . $t[9] . ", " . $t[10] . ", " . $t[11] . ", " . $t[12] . ", " . $t[13] . "
  )");
}

// Add more characters
$characters = [
  [6, 3, 'Arthur Pierce', 'Movies · New Release', 'Hero', 'images/characters/hero1.jpg', 'The lead hero in the new sci-fi blockbuster', 'Arthur Pierce leads the mission to save humanity from an alien invasion. Wielding a prototype weapon, he must navigate unknown territories and make difficult choices.', 'Arc 1 | Awakens to the alien threat.\nArc 5 | Forms alliance with resistant forces.\nArc 10 | Final confrontation with the alien queen.', 'New Release Guide\nArthur Pierce Action Figures', 1500],
  [7, 4, 'Dr. Sarah Connors', 'TV · New Series', 'Scientist', 'images/characters/hero2.jpg', 'The brilliant scientist on the new drama series', 'Dr. Sarah Connors is the lead scientist working on a secret project that could change the world. She must balance her work with her personal life while facing numerous obstacles.', 'Ch. 1 | Project begins.\nCh. 5 | Major breakthrough.\nCh. 10 | Project threatened.', 'New Series Guide\nDr. Sarah Connors Episodes', 1200],
  [8, 5, 'Kira Johnson', 'K-Pop · New Group', 'Lead Dancer', 'images/characters/hero3.jpg', 'The lead dancer of the new K-Pop group', 'Kira Johnson is the lead dancer and visual of the new K-Pop group Nova Eclipse. Known for her powerful performances and flawless technique.', '2026 | Debuts with Nova Eclipse.\n2026 | First solo showcase.', 'Nova Eclipse Timeline', 1800],
];

foreach ($characters as $c) {
  $bioEscaped = addslashes($c[9]);
  $relatedEscaped = addslashes($c[10]);
  $conn->query("INSERT INTO characters (character_id, category_id, name, fandom_label, role, image, short_desc, bio, timeline, related, views) VALUES (
    " . $c[0] . ", " . $c[1] . ", '" . $conn->real_escape_string($c[2]) . "', '" . $conn->real_escape_string($c[3]) . "', '" . $c[4] . "', '" . $c[5] . "', '" . $c[6] . "', '" . $c[7] . "', '" . $bioEscaped . "', '" . $relatedEscaped . "', " . $c[11] . "
  )");
}

// Add more articles
$articles = [
  [1, 'Anime Industry Update', 'article', 'Industry', 'https://images.pexels.com/photos/31971471/pexels-photo-31971471.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Latest trends in the anime industry', NULL, NULL, '5 min read', '2026-09-25', 78, 0, 0],
  [2, 'Gaming Trends 2026', 'article', 'Roguelike', 'https://images.pexels.com/photos/14266493/pexels-photo-14266493.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Top gaming trends for 2026', NULL, NULL, '6 min read', '2026-09-22', 74, 0, 0],
  [6, 'Comic Book Crossover', 'article', 'Superhero', 'https://images.pexels.com/photos/11317071/pexels-photo-11317071.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'Major comic book crossover event', NULL, NULL, '7 min read', '2026-09-18', 71, 0, 0],
];

foreach ($articles as $a) {
  $conn->query("INSERT INTO contents (category_id, title, type, genre, image, excerpt, body, read_time, release_date, popularity, views, is_featured, is_trending, in_gallery) VALUES (
    " . $a[0] . ", '" . $conn->real_escape_string($a[1]) . "', '" . $a[2] . "', '" . $a[3] . "', '" . $a[4] . "', '" . $a[5] . "', " . ($a[6] !== NULL ? "'" . $conn->real_escape_string($a[6]) . "'" : 'NULL') . ", " . ($a[7] !== NULL ? "'" . $conn->real_escape_string($a[7]) . "'" : 'NULL') . ", '" . $a[8] . "', " . $a[9] . ", " . $a[10] . ", " . $a[11] . ", " . $a[12] . ", " . $a[13] . "
  )");
}

// Add more merchandise
$merch = [
  [5, 3, 'Movie Poster Figure', 'Movies · New Release', 'Figure', 'images/merch/figure.jpg', 'Premium figure from the new release', '249.00', 'Limited Edition', 1, 500],
  [6, 4, 'TV Series Hoodie', 'TV · New Series', 'Apparel', 'images/merch/apparel.jpg', 'Hoodie from the new TV series', '69.00', 'Collectible', 0, 350],
  [7, 5, 'K-Pop Lightstick', 'K-Pop · New Group', 'Vinyl', 'images/merch/vinyl.jpg', 'Official lightstick for the new K-Pop group', '45.00', 'Pre-Order', 1, 420],
];

foreach ($merch as $m) {
  $conn->query("INSERT INTO merchandise (item_id, category_id, name, fandom_label, item_type, image, description, price, tag, is_upcoming, views) VALUES (
    " . $m[0] . ", " . $m[1] . ", '" . $conn->real_escape_string($m[2]) . "', '" . $conn->real_escape_string($m[3]) . "', '" . $m[4] . "', '" . $m[5] . "', '" . $m[6] . "', " . $m[7] . ", '" . $m[8] . "', " . $m[9] . ", " . $m[10] . "
  )");
}

// Add more releases
$releases = [
  [3, 'New Sci-Fi Movie', 'Movie', '2026-12-25', 'https://images.pexels.com/photos/33162637/pexels-photo-33162637.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'The much-awaited sci-fi movie', 'Anticipated'],
  [4, 'New TV Series', 'Show', '2027-02-14', 'https://images.pexels.com/photos/9902128/pexels-photo-9902128.jpeg?auto=compress&cs=tinysrgb&fit=crop&h=900&w=1400', 'The new drama series everyone is talking about', ''],
  [5, 'K-Pop World Tour', 'Merch', '2026-11-01', 'images/merch/vinyl.jpg', 'World tour for the new K-Pop group', 'Limited Edition'],
];

foreach ($releases as $r) {
  $conn->query("INSERT INTO releases (category_id, title, kind, release_date, image, description, tag) VALUES (
    " . $r[0] . ", '" . $conn->real_escape_string($r[1]) . "', '" . $r[2] . "', '" . $r[3] . "', '" . $r[4] . "', '" . $r[5] . "', '" . $r[6] . "'
  )");
}

// Add more events
$events = [
  [3, 'Movie Premiere Night', '2026-12-20', 'New York', 'Cinema Complex', 40.712800, -74.006000, 'Premiere of the new sci-fi movie', 'https://example.com/tickets/movie-premiere'],
  [4, 'TV Series Watch Party', '2027-01-15', 'Los Angeles', 'Streaming Hub', 34.052200, -118.243700, 'Watch party for the new TV series premiere', 'https://example.com/tickets/tv-watch-party'],
  [5, 'K-Pop Concert', '2026-11-20', 'Tokyo', 'Tokyo Dome', 35.689500, 139.691700, 'K-Pop concert featuring the new group', 'https://example.com/tickets/kpop-concert'],
];

foreach ($events as $e) {
  $conn->query("INSERT INTO events (category_id, title, event_date, city, venue, latitude, longitude, description, ticket_url) VALUES (
    " . $e[0] . ", '" . $conn->real_escape_string($e[1]) . "', '" . $e[2] . "', '" . $conn->real_escape_string($e[3]) . "', '" . $conn->real_escape_string($e[4]) . "', " . $e[5] . ", " . $e[6] . ", '" . $conn->real_escape_string($e[7]) . "', '" . $conn->real_escape_string($e[8]) . "'
  )");
}

echo "Content added successfully!\n";

// Verify new counts
$result = $conn->query("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE cat.slug='movies' AND c.status='published'");
echo "Movies count now: " . $result->num_rows . "\n";

$result2 = $conn->query("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE cat.slug='tv' AND c.status='published'");
echo "TV Shows count now: " . $result2->num_rows . "\n";

$result3 = $conn->query("SELECT ch.*, cat.name AS category FROM characters ch JOIN categories cat USING(category_id)");
echo "Characters count now: " . $result3->num_rows . "\n";

$result4 = $conn->query("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE c.type='article' AND c.status='published'");
echo "Articles count now: " . $result4->num_rows . "\n";

$conn->close();
?>