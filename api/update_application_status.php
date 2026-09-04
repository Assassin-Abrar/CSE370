<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$appId = (int)($_POST['application_id'] ?? 0);
$status = $_POST['status'] ?? '';
$note = trim($_POST['note'] ?? '');
$valid = ['submitted', 'under_review', 'shortlisted', 'interview', 'accepted', 'rejected'];
if (!in_array($status, $valid, true)) jsonResponse(['ok' => false, 'message' => 'Invalid status.'], 422);

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM applications WHERE id = ? AND club_id = ?');
$stmt->execute([$appId, $club['id']]);
$app = $stmt->fetch();
if (!$app) jsonResponse(['ok' => false, 'message' => 'Application not found.'], 404);

$stmt = $pdo->prepare('UPDATE applications SET status = ? WHERE id = ?');
$stmt->execute([$status, $appId]);

if ($note !== '') {
    $stmt = $pdo->prepare('INSERT INTO application_notes (application_id, author_user_id, note) VALUES (?,?,?)');
    $stmt->execute([$appId, $user['id'], $note]);
}

if ($status === 'accepted') {
    $stmt = $pdo->prepare('SELECT id FROM club_members WHERE club_id = ? AND user_id = ?');
    $stmt->execute([$club['id'], $app['user_id']]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare('INSERT INTO club_members (club_id, user_id, position, member_role, status) VALUES (?,?,"General Member","member","active")');
        $stmt->execute([$club['id'], $app['user_id']]);
    }
}

notify((int)$app['user_id'], 'application', 'Application update', 'Your application to ' . $club['name'] . ' is now "' . statusLabel($status) . '".', '/student/my_applications.php');
logAudit($user['id'], 'updated_application_status', 'application', $appId, 'Status -> ' . $status);

jsonResponse(['ok' => true, 'message' => 'Application marked as ' . statusLabel($status) . '.', 'reload' => true]);
