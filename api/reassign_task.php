<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$taskId = (int)($_POST['task_id'] ?? 0);
$newAssignee = (int)($_POST['assigned_to'] ?? 0);
$force = !empty($_POST['force']);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ? AND club_id = ?');
$stmt->execute([$taskId, $club['id']]);
$task = $stmt->fetch();
if (!$task) jsonResponse(['ok' => false, 'message' => 'Task not found.'], 404);

$stmt = $pdo->prepare('SELECT id FROM club_members WHERE club_id = ? AND user_id = ? AND status="active"');
$stmt->execute([$club['id'], $newAssignee]);
if (!$stmt->fetch()) jsonResponse(['ok' => false, 'message' => 'Select a valid club member.'], 422);

if (!$force) {
    $wl = computeMemberWorkload($newAssignee, $club['id']);
    if ($wl['status'] === 'Overloaded') {
        jsonResponse(['ok' => false, 'requiresConfirm' => true, 'message' => 'This member is already at ' . $wl['percent'] . '% workload (Overloaded). Reassign anyway?'], 200);
    }
}

$stmt = $pdo->prepare('UPDATE tasks SET assigned_to = ? WHERE id = ?');
$stmt->execute([$newAssignee, $taskId]);

notify($newAssignee, 'task', 'Task reassigned to you', '"' . $task['title'] . '" was reassigned to you for ' . $club['name'] . '.', taskPageLinkForUser($newAssignee));
logAudit($user['id'], 'reassigned_task', 'task', $taskId, 'New assignee: ' . $newAssignee);

jsonResponse(['ok' => true, 'message' => 'Task reassigned.', 'reload' => true, 'closeModal' => true]);
