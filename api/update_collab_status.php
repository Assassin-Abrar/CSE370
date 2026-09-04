<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$collabId = (int)($_POST['collaboration_id'] ?? 0);
$status = $_POST['status'] ?? '';
$responseId = !empty($_POST['response_id']) ? (int)$_POST['response_id'] : null;
$valid = ['open', 'responses_received', 'in_discussion', 'accepted', 'completed'];
if (!in_array($status, $valid, true)) jsonResponse(['ok' => false, 'message' => 'Invalid status.'], 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM collaboration_requests WHERE id = ? AND club_id = ?');
$stmt->execute([$collabId, $club['id']]);
$collab = $stmt->fetch();
if (!$collab) jsonResponse(['ok' => false, 'message' => 'Request not found.'], 404);

$stmt = $pdo->prepare('UPDATE collaboration_requests SET status = ? WHERE id = ?');
$stmt->execute([$status, $collabId]);

if ($responseId) {
    $stmt = $pdo->prepare('UPDATE collaboration_responses SET status = "accepted" WHERE id = ? AND collaboration_id = ?');
    $stmt->execute([$responseId, $collabId]);
    $stmt = $pdo->prepare('SELECT responder_user_id FROM collaboration_responses WHERE id = ?');
    $stmt->execute([$responseId]);
    if ($r = $stmt->fetch()) {
        notify((int)$r['responder_user_id'], 'collaboration', 'Collaboration accepted', $club['name'] . ' accepted your offer on "' . $collab['title'] . '".', '/exec/collaboration.php');
    }
}
logAudit($user['id'], 'updated_collaboration_status', 'collaboration_request', $collabId, 'Status -> ' . $status);

jsonResponse(['ok' => true, 'message' => 'Collaboration request updated.', 'reload' => true]);
