<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['admin']);
requireCsrfOrFail();

$targetId = (int)($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';
if ($targetId === (int)$user['id']) jsonResponse(['ok' => false, 'message' => 'You cannot modify your own account here.'], 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$targetId]);
$target = $stmt->fetch();
if (!$target) jsonResponse(['ok' => false, 'message' => 'User not found.'], 404);

if ($action === 'suspend') {
    $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?")->execute([$targetId]);
    $msg = 'User suspended.';
} elseif ($action === 'activate') {
    $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$targetId]);
    $msg = 'User activated.';
} else {
    jsonResponse(['ok' => false, 'message' => 'Invalid action.'], 422);
}

logAudit($user['id'], 'admin_user_' . $action, 'user', $targetId, $target['name']);
jsonResponse(['ok' => true, 'message' => $msg, 'reload' => true]);
