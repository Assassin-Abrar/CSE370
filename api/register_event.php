<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['student']);
requireCsrfOrFail();

$eventId = (int)($_POST['event_id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND status IN ('approved','completed')");
$stmt->execute([$eventId]);
$event = $stmt->fetch();
if (!$event) jsonResponse(['ok' => false, 'message' => 'This event is not open for registration.'], 404);

$stmt = $pdo->prepare('SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?');
$stmt->execute([$eventId, $user['id']]);
if ($stmt->fetch()) jsonResponse(['ok' => false, 'message' => 'You are already registered for this event.'], 422);

$stmt = $pdo->prepare('INSERT INTO event_registrations (event_id, user_id) VALUES (?,?)');
$stmt->execute([$eventId, $user['id']]);

notify($user['id'], 'event', 'Event registration confirmed', 'You are registered for "' . $event['title'] . '" on ' . date('M j, Y', strtotime($event['event_date'])) . '.', '/student/events.php');

jsonResponse(['ok' => true, 'message' => 'You are registered for ' . $event['title'] . '!', 'reload' => true]);
