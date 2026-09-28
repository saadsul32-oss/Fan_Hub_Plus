<?php
/* Upcoming Events (converted from Events.tsx) + city filter, GPS "near me" and map (SRS: location-aware discovery) */
$eventsList = q_all("SELECT e.*, cat.name AS category,
    (6371 * ACOS(LEAST(1, COS(RADIANS(0)) * COS(RADIANS(e.latitude)) * COS(RADIANS(e.longitude) - RADIANS(0)) + SIN(RADIANS(0)) * SIN(RADIANS(e.latitude))))) AS distance_km
FROM events e JOIN categories cat USING(category_id)
WHERE e.event_date >= CURDATE() ORDER BY e.event_date");
$mapPoints = array_values(array_filter(array_map(fn($e) => $e['latitude'] !== null ? [
  'id' => (int)$e['event_id'], 'title' => $e['title'], 'lat' => (float)$e['latitude'], 'lng' => (float)$e['longitude'],
  'venue' => $e['venue'], 'city' => $e['city'], 'date' => fmt_date($e['event_date'])] : null, $eventsList)));
?>
<section id="events" class="relative bg-void py-24 sm:py-32">
  <div class="mx-auto mb-16 max-w-7xl px-5 sm:px-8">
    <p class="mb-3 text-xs uppercase tracking-[0.35em] text-magenta">Upcoming Events</p>
    <h2 class="font-display text-5xl leading-[0.95] text-white sm:text-6xl">Mark your universe calendar</h2>
  </div>

  <div class="mx-auto max-w-4xl px-5 sm:px-8">
    <div class="mb-6 flex flex-wrap items-center gap-2">
      <?= icon('map-pin', 14, 'mr-1 text-white/40') ?>
      <button data-event-city="" class="city-chip rounded-full border px-3.5 py-1.5 text-xs font-medium transition border-white bg-white text-black" data-cursor-hover>All cities</button>
      <?php foreach ($eventCities as $c): ?>
        <button data-event-city="<?= e($c['city']) ?>" class="city-chip rounded-full border px-3.5 py-1.5 text-xs font-medium transition border-white/15 text-white/60 hover:border-white/40" data-cursor-hover><?= e($c['city']) ?></button>
      <?php endforeach; ?>
      <span class="mx-2 hidden h-4 w-px bg-white/15 sm:block"></span>
      <button data-action="near-me" class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-medium text-cyan transition hover:text-white" data-cursor-hover>
        <?= icon('navigation', 12) ?> <span data-part="label">Near me</span>
      </button>
    </div>

    <div id="events-map" class="relative mb-12 h-72 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]" data-points='<?= e(json_encode($mapPoints)) ?>'>
      <div class="absolute inset-0 flex items-center justify-center text-xs uppercase tracking-[0.3em] text-white/30">Loading map…</div>
    </div>

    <div id="events-list">
      <?php include __DIR__ . '/events_list.php'; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      var nearMeBtn = document.querySelector('[data-action="near-me"]');
      if (!nearMeBtn) return;

      nearMeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (!navigator.geolocation) {
          alert('Geolocation is not supported by your browser.');
          return;
        }
        navigator.geolocation.getCurrentPosition(function(position) {
          var lat = position.coords.latitude;
          var lng = position.coords.longitude;
          // Call the calendar API with GPS coordinates
          fetch('<?= url('api/calendar.php') ?>?action=list&lat=' + lat + '&lng=' + lng)
            .then(function(response) { return response.json(); })
            .then(function(data) {
              // Update the events list with distance-sorted events
              var listContainer = document.getElementById('events-list');
              // The server-side include already rendered the list;
              // we need to re-fetch and replace, or we can trigger a filter
              // For now, just show an alert with the coordinates
              alert('Your location: ' + lat + ', ' + lng);
              // TODO: Implement full distance sorting via API
            })
            .catch(function(error) {
              console.error('Error fetching events:', error);
              alert('Could not load events for your location.');
            });
        }, function(error) {
          alert('Unable to get GPS location: ' + error.message);
        });
      });
    });
    </script>
  </div>
</section>
