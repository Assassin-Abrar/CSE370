<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['admin']);
requireCsrfOrFail();

$clubId = (int)($_POST['club_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$foundedYear = (int)($_POST['founded_year'] ?? 0);

if (!$clubId || $name === '') jsonResponse(['ok' => false, 'message' => 'Invalid data.'], 422);

$pdo = db();
$stmt = $pdo->prepare('UPDATE clubs SET name=?, category=?, description=?, founded_year=? WHERE id=?');
$stmt->execute([$name, $category, $description, $foundedYear ?: null, $clubId]);

logAudit($user['id'], 'admin_updated_club', 'club', $clubId, $name);
jsonResponse(['ok' => true, 'message' => 'Club updated.', 'reload' => true]);
