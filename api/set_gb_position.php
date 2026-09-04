<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['admin']);
requireCsrfOrFail();

$clubId = (int)($_POST['club_id'] ?? 0);
$targetUserId = (int)($_POST['user_id'] ?? 0);
$position = trim($_POST['position'] ?? '');

$positionRoles = [
    'President' => 'president',
    'Vice President' => 'exec',
    'General Secretary' => 'exec',
    'Treasurer' => 'exec',
];
if (!isset($positionRoles[$position])) {
    jsonResponse(['ok' => false, 'message' => 'Invalid governing body position.'], 422);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM clubs WHERE id = ?');
$stmt->execute([$clubId]);
$club = $stmt->fetch();
if (!$club) jsonResponse(['ok' => false, 'message' => 'Club not found.'], 404);

$stmt = $pdo->prepare("SELECT cm.*, u.name AS user_name FROM club_members cm JOIN users u ON u.id = cm.user_id WHERE cm.club_id = ? AND cm.user_id = ? AND cm.status = 'active'");
$stmt->execute([$clubId, $targetUserId]);
$targetMember = $stmt->fetch();
if (!$targetMember) jsonResponse(['ok' => false, 'message' => 'Choose an existing active member of this club.'], 422);

$newMemberRole = $positionRoles[$position];

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT * FROM club_members WHERE club_id = ? AND position = ? AND status = 'active' AND user_id != ?");
    $stmt->execute([$clubId, $position, $targetUserId]);
    $previousHolder = $stmt->fetch();

    if ($previousHolder) {
        $pdo->prepare("UPDATE club_members SET position = 'General Member', member_role = 'member' WHERE id = ?")->execute([$previousHolder['id']]);
    }

    $pdo->prepare('UPDATE club_members SET position = ?, member_role = ? WHERE id = ?')->execute([$position, $newMemberRole, $targetMember['id']]);

    syncSystemRoleFromClubMembership($pdo, $targetUserId);
    if ($previousHolder) syncSystemRoleFromClubMembership($pdo, (int)$previousHolder['user_id']);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['ok' => false, 'message' => 'Could not update the governing body assignment.'], 500);
}

notify($targetUserId, 'club', 'Governing body position updated', "You've been assigned as $position of {$club['name']}.", '/exec/club_manage.php');
if ($previousHolder) {
    notify((int)$previousHolder['user_id'], 'club', 'Governing body position updated', "You are no longer $position of {$club['name']}.", '/student/my_clubs.php');
}
logAudit($user['id'], 'assigned_gb_position', 'club_member', (int)$targetMember['id'], "{$targetMember['user_name']} -> $position ({$club['name']})");

jsonResponse(['ok' => true, 'message' => "{$targetMember['user_name']} is now $position of {$club['name']}.", 'reload' => true]);
