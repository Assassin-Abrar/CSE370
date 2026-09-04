<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$description = trim($_POST['description'] ?? '');
$mission = trim($_POST['mission'] ?? '');
$email = trim($_POST['email'] ?? '');
$socialLink = trim($_POST['social_link'] ?? '');
$recruitmentStatus = ($_POST['recruitment_status'] ?? '') === 'open' ? 'open' : 'closed';
$skillIds = array_map('intval', $_POST['skills'] ?? []);
$interestIds = array_map('intval', $_POST['interests'] ?? []);

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('UPDATE clubs SET description=?, mission=?, email=?, social_link=?, recruitment_status=? WHERE id=?');
    $stmt->execute([$description, $mission, $email, $socialLink, $recruitmentStatus, $club['id']]);

    $pdo->prepare('DELETE FROM club_skills WHERE club_id = ?')->execute([$club['id']]);
    if ($skillIds) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO club_skills (club_id, skill_id) VALUES (?,?)');
        foreach ($skillIds as $sid) $stmt->execute([$club['id'], $sid]);
    }
    $pdo->prepare('DELETE FROM club_interests WHERE club_id = ?')->execute([$club['id']]);
    if ($interestIds) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO club_interests (club_id, interest_id) VALUES (?,?)');
        foreach ($interestIds as $iid) $stmt->execute([$club['id'], $iid]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['ok' => false, 'message' => 'Could not save club profile.'], 500);
}

logAudit($user['id'], 'updated_club', 'club', (int)$club['id'], 'Profile updated');
jsonResponse(['ok' => true, 'message' => 'Club profile updated.', 'reload' => true]);
