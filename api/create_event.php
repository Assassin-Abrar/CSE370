<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$date = $_POST['event_date'] ?? '';
$start = $_POST['start_time'] ?? '';
$end = $_POST['end_time'] ?? '';
$venueId = !empty($_POST['venue_id']) ? (int)$_POST['venue_id'] : null;
$attendance = (int)($_POST['expected_attendance'] ?? 0);
$equipment = trim($_POST['required_equipment'] ?? '');
$budget = (float)($_POST['budget_amount'] ?? 0);

$errors = [];
if ($title === '') $errors['title'] = 'Event title is required.';
if (!$date) $errors['event_date'] = 'Date is required.';
if (!$start || !$end) $errors['start_time'] = 'Start and end time are required.';
if ($start && $end && $start >= $end) $errors['end_time'] = 'End time must be after start time.';
if ($errors) jsonResponse(['ok' => false, 'message' => 'Please fix the errors below.', 'errors' => $errors], 422);

$pdo = db();
$stmt = $pdo->prepare('INSERT INTO events (club_id, title, description, category, event_date, start_time, end_time, venue_id, expected_attendance, required_equipment, budget_amount, status, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,"submitted",?)');
$stmt->execute([$club['id'], $title, $description, $category, $date, $start, $end, $venueId, $attendance, $equipment, $budget, $user['id']]);
$eventId = (int)$pdo->lastInsertId();

$venueConflicts = checkVenueConflict($venueId, $date, $start, $end, $eventId);
$majorConflicts = checkMajorEventConflict($date, $eventId);
$hasConflict = !empty($venueConflicts) || !empty($majorConflicts);

$admins = $pdo->query("SELECT id FROM users WHERE role = 'admin' AND status='active'")->fetchAll();
foreach ($admins as $a) {
    $msg = 'New event proposal "' . $title . '" from ' . $club['name'] . ' awaiting review.';
    notify((int)$a['id'], 'event', 'Event proposal submitted', $msg, '/admin/events.php');
    if ($hasConflict) {
        notify((int)$a['id'], 'venue', 'Venue conflict detected', $title . ' overlaps with another booking on ' . $date . '.', '/admin/venues.php');
    }
}
logAudit($user['id'], 'submitted_event', 'event', $eventId, $title);

$msg = $hasConflict
    ? 'Event submitted for review — a scheduling conflict was detected and flagged to OCA for resolution.'
    : 'Event proposal submitted for OCA review.';
jsonResponse(['ok' => true, 'message' => $msg, 'reload' => true, 'closeModal' => true, 'hasConflict' => $hasConflict]);
