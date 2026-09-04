<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec', 'student']);
requireCsrfOrFail();

$taskId = (int)($_POST['task_id'] ?? 0);
$status = $_POST['status'] ?? '';
$valid = ['todo', 'in_progress', 'review', 'completed'];
if (!in_array($status, $valid, true)) jsonResponse(['ok' => false, 'message' => 'Invalid status.'], 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
$stmt->execute([$taskId]);
$task = $stmt->fetch();
if (!$task) jsonResponse(['ok' => false, 'message' => 'Task not found.'], 404);

$isOwnerExec = $user['role'] === 'exec' && ($c = myClub($user)) && (int)$c['id'] === (int)$task['club_id'];
$isAssignee = (int)$task['assigned_to'] === (int)$user['id'];
if (!$isOwnerExec && !$isAssignee) jsonResponse(['ok' => false, 'message' => 'You do not have permission to update this task.'], 403);

$stmt = $pdo->prepare('UPDATE tasks SET status = ? WHERE id = ?');
$stmt->execute([$status, $taskId]);

if ($task['created_by'] && (int)$task['created_by'] !== (int)$user['id']) {
    $stmt = $pdo->prepare('SELECT name FROM clubs WHERE id = ?');
    $stmt->execute([$task['club_id']]);
    $clubName = $stmt->fetchColumn();
    notify((int)$task['created_by'], 'task', 'Task updated', '"' . $task['title'] . '" moved to ' . statusLabel($status) . ' at ' . $clubName . '.', '/exec/tasks.php');
}
logAudit($user['id'], 'updated_task_status', 'task', $taskId, 'Status -> ' . $status);

jsonResponse(['ok' => true, 'message' => 'Task updated.']);
