<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$campaignId = (int)($_POST['campaign_id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM recruitment_campaigns WHERE id = ? AND club_id = ?');
$stmt->execute([$campaignId, $club['id']]);
$camp = $stmt->fetch();
if (!$camp) jsonResponse(['ok' => false, 'message' => 'Campaign not found.'], 404);

$newStatus = $camp['status'] === 'open' ? 'closed' : 'open';
$pdo->prepare('UPDATE recruitment_campaigns SET status = ? WHERE id = ?')->execute([$newStatus, $campaignId]);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM recruitment_campaigns WHERE club_id = ? AND status = 'open'");
$stmt->execute([$club['id']]);
if ((int)$stmt->fetchColumn() === 0) {
    $pdo->prepare("UPDATE clubs SET recruitment_status = 'closed' WHERE id = ?")->execute([$club['id']]);
}

jsonResponse(['ok' => true, 'message' => 'Campaign ' . ($newStatus === 'open' ? 'reopened' : 'closed') . '.', 'reload' => true]);
