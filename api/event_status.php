<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['admin']);
requireCsrfOrFail();

$eventId = (int)($_POST['event_id'] ?? 0);
$action = $_POST['action'] ?? '';
$comment = trim($_POST['comment'] ?? '');
$valid = ['approve', 'reject', 'cancel', 'complete'];
if (!in_array($action, $valid, true)) jsonResponse(['ok' => false, 'message' => 'Invalid action.'], 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT e.*, c.name AS club_name FROM events e LEFT JOIN clubs c ON c.id = e.club_id WHERE e.id = ?');
$stmt->execute([$eventId]);
$event = $stmt->fetch();
if (!$event) jsonResponse(['ok' => false, 'message' => 'Event not found.'], 404);

$statusMap = ['approve' => 'approved', 'reject' => 'rejected', 'cancel' => 'cancelled', 'complete' => 'completed'];
$newStatus = $statusMap[$action];

$stmt = $pdo->prepare('UPDATE events SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_comment = ? WHERE id = ?');
$stmt->execute([$newStatus, $user['id'], $comment, $eventId]);

if ($event['club_id']) {
    $stmt = $pdo->prepare("SELECT user_id FROM club_members WHERE club_id = ? AND member_role IN ('exec','president')");
    $stmt->execute([$event['club_id']]);
    foreach ($stmt->fetchAll() as $row) {
        notify((int)$row['user_id'], 'event', 'Event ' . statusLabel($newStatus), '"' . $event['title'] . '" was ' . $newStatus . ($comment ? ': ' . $comment : '.'), '/exec/events.php');
    }
}
logAudit($user['id'], $action . 'd_event', 'event', $eventId, $comment ?: $event['title']);

jsonResponse(['ok' => true, 'message' => 'Event marked as ' . statusLabel($newStatus) . '.', 'reload' => true, 'closeModal' => true]);
