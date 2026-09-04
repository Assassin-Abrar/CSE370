<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$assignedTo = (int)($_POST['assigned_to'] ?? 0);
$priority = $_POST['priority'] ?? 'medium';
$deadline = $_POST['deadline'] ?? null;
$eventId = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;
$workload = max(1, min(10, (int)($_POST['estimated_workload'] ?? 3)));
$force = !empty($_POST['force']);

$errors = [];
if ($title === '') $errors['title'] = 'Task title is required.';
if (!in_array($priority, ['low', 'medium', 'high'], true)) $errors['priority'] = 'Invalid priority.';
$pdo = db();
if ($assignedTo) {
    $stmt = $pdo->prepare('SELECT id FROM club_members WHERE club_id = ? AND user_id = ? AND status="active"');
    $stmt->execute([$club['id'], $assignedTo]);
    if (!$stmt->fetch()) $errors['assigned_to'] = 'Select a valid club member.';
}
if ($errors) jsonResponse(['ok' => false, 'message' => 'Please fix the errors below.', 'errors' => $errors], 422);

if ($assignedTo && !$force) {
    $wl = computeMemberWorkload($assignedTo, $club['id']);
    if ($wl['status'] === 'Overloaded') {
        jsonResponse(['ok' => false, 'requiresConfirm' => true, 'message' => 'This member is already at ' . $wl['percent'] . '% workload (Overloaded). Assign anyway?'], 200);
    }
}

$stmt = $pdo->prepare('INSERT INTO tasks (club_id, event_id, title, description, assigned_to, created_by, priority, deadline, status, estimated_workload) VALUES (?,?,?,?,?,?,?,?,"todo",?)');
$stmt->execute([$club['id'], $eventId, $title, $description, $assignedTo ?: null, $user['id'], $priority, $deadline ?: null, $workload]);
$taskId = (int)$pdo->lastInsertId();

if ($assignedTo) {
    $msg = 'You were assigned "' . $title . '" for ' . $club['name'] . ($deadline ? ' — due ' . date('M j', strtotime($deadline)) : '') . '.';
    notify($assignedTo, 'task', 'New task assigned', $msg, taskPageLinkForUser($assignedTo));
}
logAudit($user['id'], 'created_task', 'task', $taskId, $title);

jsonResponse(['ok' => true, 'message' => 'Task created.', 'reload' => true, 'closeModal' => true]);
