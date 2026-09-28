<?php
require 'includes/functions.php';
$conn = new mysqli('localhost', 'root', '', 'fanhub_plus');
$result = $conn->query("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE cat.slug='movies' AND c.status='published' ORDER BY c.popularity DESC");
$rows = $result->fetch_all(MYSQLI_ASSOC);
echo 'Movies count: ' . count($rows) . "\n";
foreach($rows as $r) {
  echo "- " . $r['title'] . " (" . $r['type'] . ")\n";
}
// Also check TV shows
$result2 = $conn->query("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE cat.slug='tv' AND c.status='published' ORDER BY c.popularity DESC");
$rows2 = $result2->fetch_all(MYSQLI_ASSOC);
echo "\nTV Shows count: " . count($rows2) . "\n";
foreach($rows2 as $r) {
  echo "- " . $r['title'] . " (" . $r['type'] . ")\n";
}
// Check characters
$result3 = $conn->query("SELECT ch.*, cat.name AS category FROM characters ch JOIN categories cat USING(category_id) ORDER BY ch.character_id");
$chars = $result3->fetch_all(MYSQLI_ASSOC);
echo "\nCharacters count: " . count($chars) . "\n";
// Check articles
$result4 = $conn->query("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE c.type='article' AND c.status='published' ORDER BY c.popularity DESC");
$articles = $result4->fetch_all(MYSQLI_ASSOC);
echo "\nArticles count: " . count($articles) . "\n";
// Check media
$result5 = $conn->query("SELECT c.*, cat.name AS category FROM contents c JOIN categories cat USING(category_id) WHERE c.type IN('video','trailer','audio') AND c.status='published' ORDER BY c.popularity DESC");
$media = $result5->fetch_all(MYSQLI_ASSOC);
echo "\nMedia count: " . count($media) . "\n";
// Check merchandise
$result6 = $conn->query("SELECT m.*, cat.name AS category FROM merchandise m JOIN categories cat USING(category_id) ORDER BY m.item_id");
$merch = $result6->fetch_all(MYSQLI_ASSOC);
echo "\nMerchandise count: " . count($merch) . "\n";
// Check releases
$result7 = $conn->query("SELECT r.*, cat.name AS category FROM releases r JOIN categories cat USING(category_id) ORDER BY r.release_id");
$releases = $result7->fetch_all(MYSQLI_ASSOC);
echo "\nReleases count: " . count($releases) . "\n";
// Check events
$result8 = $conn->query("SELECT e.*, cat.name AS category FROM events e JOIN categories cat USING(category_id) WHERE e.event_date >= CURDATE() ORDER BY e.event_date");
$events = $result8->fetch_all(MYSQLI_ASSOC);
echo "\nEvents count: " . count($events) . "\n";

$conn->close();
?>