<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec', 'admin']);
requireCsrfOrFail();

$venueId = !empty($_POST['venue_id']) ? (int)$_POST['venue_id'] : null;
$date = $_POST['event_date'] ?? '';
$start = $_POST['start_time'] ?? '';
$end = $_POST['end_time'] ?? '';
$excludeId = !empty($_POST['exclude_event_id']) ? (int)$_POST['exclude_event_id'] : null;

if (!$date || !$start || !$end) jsonResponse(['ok' => false, 'message' => 'Date and time are required.'], 422);

$venueConflicts = checkVenueConflict($venueId, $date, $start, $end, $excludeId);
$majorConflicts = checkMajorEventConflict($date, $excludeId);
$suggestions = [];
if ($venueConflicts) {
    $suggestions = array_map(fn($v) => ['id' => $v['id'], 'name' => $v['name'], 'capacity' => $v['capacity']], suggestAlternativeVenues($date, $start, $end, $excludeId));
}

jsonResponse([
    'ok' => true,
    'hasConflict' => !empty($venueConflicts) || !empty($majorConflicts),
    'venueConflicts' => array_map(fn($e) => [
        'title' => $e['title'], 'club' => $e['club_name'], 'venue' => $e['venue_name'],
        'start' => substr($e['start_time'], 0, 5), 'end' => substr($e['end_time'], 0, 5),
    ], $venueConflicts),
    'majorConflicts' => array_map(fn($e) => ['title' => $e['title'], 'venue' => $e['venue_name']], $majorConflicts),
    'suggestions' => $suggestions,
]);
