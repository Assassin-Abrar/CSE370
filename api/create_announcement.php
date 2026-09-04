<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['admin']);
requireCsrfOrFail();

$title = trim($_POST['title'] ?? '');
$body = trim($_POST['body'] ?? '');
$audience = $_POST['audience'] ?? 'all';
if (!in_array($audience, ['all', 'students', 'execs'], true)) $audience = 'all';
if ($title === '' || $body === '') jsonResponse(['ok' => false, 'message' => 'Title and message are required.'], 422);

$pdo = db();
$stmt = $pdo->prepare('INSERT INTO announcements (title, body, posted_by, audience) VALUES (?,?,?,?)');
$stmt->execute([$title, $body, $user['id'], $audience]);
$id = (int)$pdo->lastInsertId();

$roleFilter = $audience === 'students' ? "role='student'" : ($audience === 'execs' ? "role='exec'" : "role IN ('student','exec')");
$users = $pdo->query("SELECT id FROM users WHERE $roleFilter AND status='active'")->fetchAll();
foreach ($users as $u) {
    notify((int)$u['id'], 'announcement', $title, mb_substr($body, 0, 140), null);
}
logAudit($user['id'], 'posted_announcement', 'announcement', $id, $title);

jsonResponse(['ok' => true, 'message' => 'Announcement posted.', 'reload' => true, 'closeModal' => true]);
