<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$positions = max(1, (int)($_POST['positions_needed'] ?? 1));
$deadline = $_POST['deadline'] ?? '';

$errors = [];
if ($title === '') $errors['title'] = 'Title is required.';
if ($deadline === '') $errors['deadline'] = 'Deadline is required.';
if ($errors) jsonResponse(['ok' => false, 'message' => 'Please fix the errors below.', 'errors' => $errors], 422);

$pdo = db();
$stmt = $pdo->prepare('INSERT INTO recruitment_campaigns (club_id, title, description, positions_needed, deadline, status) VALUES (?,?,?,?,?,"open")');
$stmt->execute([$club['id'], $title, $description, $positions, $deadline]);

$stmt = $pdo->prepare("UPDATE clubs SET recruitment_status = 'open' WHERE id = ?");
$stmt->execute([$club['id']]);

logAudit($user['id'], 'created_campaign', 'recruitment_campaign', (int)$pdo->lastInsertId(), $title);
jsonResponse(['ok' => true, 'message' => 'Recruitment campaign published. Your club now shows as recruiting.', 'reload' => true, 'closeModal' => true]);
