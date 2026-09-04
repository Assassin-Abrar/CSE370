<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$type = $_POST['request_type'] ?? '';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$requiredDate = $_POST['required_date'] ?? null;
$priority = $_POST['priority'] ?? 'medium';

$validTypes = ['equipment', 'human_resource', 'co_host', 'venue', 'media', 'other'];
$errors = [];
if (!in_array($type, $validTypes, true)) $errors['request_type'] = 'Choose a request type.';
if ($title === '') $errors['title'] = 'Title is required.';
if ($errors) jsonResponse(['ok' => false, 'message' => 'Please fix the errors below.', 'errors' => $errors], 422);

$pdo = db();
$stmt = $pdo->prepare('INSERT INTO collaboration_requests (club_id, request_type, title, description, required_date, priority, status) VALUES (?,?,?,?,?,?,"open")');
$stmt->execute([$club['id'], $type, $title, $description, $requiredDate ?: null, $priority]);
$id = (int)$pdo->lastInsertId();

logAudit($user['id'], 'created_collaboration', 'collaboration_request', $id, $title);
jsonResponse(['ok' => true, 'message' => 'Collaboration request posted to the board.', 'reload' => true, 'closeModal' => true]);
