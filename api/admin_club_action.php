<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['admin']);
requireCsrfOrFail();

$clubId = (int)($_POST['club_id'] ?? 0);
$action = $_POST['action'] ?? '';
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM clubs WHERE id = ?');
$stmt->execute([$clubId]);
$club = $stmt->fetch();
if (!$club) jsonResponse(['ok' => false, 'message' => 'Club not found.'], 404);

if ($action === 'suspend') {
    $pdo->prepare("UPDATE clubs SET status = 'suspended' WHERE id = ?")->execute([$clubId]);
    $msg = 'Club suspended.';
} elseif ($action === 'activate') {
    $pdo->prepare("UPDATE clubs SET status = 'active' WHERE id = ?")->execute([$clubId]);
    $msg = 'Club activated.';
} else {
    jsonResponse(['ok' => false, 'message' => 'Invalid action.'], 422);
}

logAudit($user['id'], 'admin_club_' . $action, 'club', $clubId, $club['name']);
jsonResponse(['ok' => true, 'message' => $msg, 'reload' => true]);
