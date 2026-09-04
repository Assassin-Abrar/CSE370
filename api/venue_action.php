<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['admin']);
requireCsrfOrFail();

$action = $_POST['action'] ?? '';
$pdo = db();

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 50);
    $location = trim($_POST['location'] ?? '');
    if ($name === '') jsonResponse(['ok' => false, 'message' => 'Venue name is required.'], 422);
    $stmt = $pdo->prepare('INSERT INTO venues (name, capacity, location, status) VALUES (?,?,?,"available")');
    $stmt->execute([$name, $capacity, $location]);
    jsonResponse(['ok' => true, 'message' => 'Venue added.', 'reload' => true, 'closeModal' => true]);
}

$venueId = (int)($_POST['venue_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM venues WHERE id = ?');
$stmt->execute([$venueId]);
$venue = $stmt->fetch();
if (!$venue) jsonResponse(['ok' => false, 'message' => 'Venue not found.'], 404);

if ($action === 'toggle') {
    $newStatus = $venue['status'] === 'available' ? 'maintenance' : 'available';
    $pdo->prepare('UPDATE venues SET status = ? WHERE id = ?')->execute([$newStatus, $venueId]);
    logAudit($user['id'], 'venue_status_changed', 'venue', $venueId, $venue['name'] . ' -> ' . $newStatus);
    jsonResponse(['ok' => true, 'message' => 'Venue marked as ' . $newStatus . '.', 'reload' => true]);
}

jsonResponse(['ok' => false, 'message' => 'Invalid action.'], 422);
