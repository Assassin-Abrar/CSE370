<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$collabId = (int)($_POST['collaboration_id'] ?? 0);
$message = trim($_POST['message'] ?? '');
if ($message === '') jsonResponse(['ok' => false, 'message' => 'Please add a short message with your offer.'], 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM collaboration_requests WHERE id = ?');
$stmt->execute([$collabId]);
$collab = $stmt->fetch();
if (!$collab) jsonResponse(['ok' => false, 'message' => 'Request not found.'], 404);
if ((int)$collab['club_id'] === (int)$club['id']) jsonResponse(['ok' => false, 'message' => 'You cannot respond to your own club\'s request.'], 422);

$stmt = $pdo->prepare('INSERT INTO collaboration_responses (collaboration_id, responding_club_id, responder_user_id, message, status) VALUES (?,?,?,?,"proposed")');
$stmt->execute([$collabId, $club['id'], $user['id'], $message]);

if ($collab['status'] === 'open') {
    $stmt = $pdo->prepare("UPDATE collaboration_requests SET status = 'responses_received' WHERE id = ?");
    $stmt->execute([$collabId]);
}

$stmt = $pdo->prepare("SELECT user_id FROM club_members WHERE club_id = ? AND member_role IN ('exec','president')");
$stmt->execute([$collab['club_id']]);
foreach ($stmt->fetchAll() as $row) {
    notify((int)$row['user_id'], 'collaboration', 'Collaboration response received', $club['name'] . ' responded to "' . $collab['title'] . '".', '/exec/collaboration.php');
}
logAudit($user['id'], 'responded_collaboration', 'collaboration_request', $collabId, $message);

jsonResponse(['ok' => true, 'message' => 'Your response was sent.', 'reload' => true, 'closeModal' => true]);
