<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['student', 'exec', 'admin']);
requireCsrfOrFail();

$id = (int)($_POST['id'] ?? 0);
$stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);

jsonResponse(['ok' => true, 'message' => 'Marked as read.']);
