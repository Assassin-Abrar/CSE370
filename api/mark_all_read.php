<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['student', 'exec', 'admin']);
requireCsrfOrFail();

$stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
$stmt->execute([$user['id']]);

jsonResponse(['ok' => true, 'message' => 'All notifications marked as read.', 'reload' => true]);
