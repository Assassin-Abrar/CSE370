<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

// Only the club's own governing body (President, VP, GS, Treasurer) may
// promote/demote or remove members — those 4 seats themselves are managed
// by OCA admins on the club's admin page, not here.
const GB_TITLES = ['President', 'Vice President', 'General Secretary', 'Treasurer'];
const CLUB_POSITIONS = ['General Member', 'Junior Executive', 'Executive', 'Senior Executive', 'Assistant Director', 'Director'];

$pdo = db();

$stmt = $pdo->prepare('SELECT position FROM club_members WHERE club_id = ? AND user_id = ? AND status = "active"');
$stmt->execute([$club['id'], $user['id']]);
$actingPosition = $stmt->fetchColumn();
if (!in_array($actingPosition, GB_TITLES, true)) {
    jsonResponse(['ok' => false, 'message' => 'Only the President, Vice President, General Secretary or Treasurer can manage member positions.'], 403);
}

$memberId = (int)($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM club_members WHERE club_id = ? AND user_id = ?');
$stmt->execute([$club['id'], $memberId]);
$member = $stmt->fetch();
if (!$member) jsonResponse(['ok' => false, 'message' => 'Member not found.'], 404);
if (in_array($member['position'], GB_TITLES, true)) {
    jsonResponse(['ok' => false, 'message' => 'This member holds a governing body seat — that is managed by the OCA admin, not here.'], 422);
}

// Director is the one internal rank that also unlocks exec-dashboard access,
// same mechanism as the 4 GB seats. Every other rank stays a title only.
const EXEC_GRANTING_POSITIONS = ['Director'];

if ($action === 'remove') {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE club_members SET status='inactive' WHERE id = ?")->execute([$member['id']]);
        syncSystemRoleFromClubMembership($pdo, $memberId);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'message' => 'Could not remove this member.'], 500);
    }
    $msg = 'Member removed from the club.';
} elseif ($action === 'set_position') {
    $position = $_POST['position'] ?? '';
    if (!in_array($position, CLUB_POSITIONS, true)) {
        jsonResponse(['ok' => false, 'message' => 'Invalid position.'], 422);
    }
    $newMemberRole = in_array($position, EXEC_GRANTING_POSITIONS, true) ? 'exec' : 'member';

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE club_members SET position = ?, member_role = ? WHERE id = ?")->execute([$position, $newMemberRole, $member['id']]);
        syncSystemRoleFromClubMembership($pdo, $memberId);
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(['ok' => false, 'message' => 'Could not update this member\'s position.'], 500);
    }
    $msg = 'Position updated to ' . $position . '.';
    $notifyMsg = $newMemberRole === 'exec'
        ? "You are now $position at {$club['name']} — you now have executive dashboard access."
        : "You are now $position at {$club['name']}.";
    notify($memberId, 'club', 'Club position updated', $notifyMsg, '/student/my_clubs.php');
} else {
    jsonResponse(['ok' => false, 'message' => 'Invalid action.'], 422);
}

logAudit($user['id'], 'club_member_' . $action, 'club_member', (int)$member['id'], 'User ' . $memberId);
jsonResponse(['ok' => true, 'message' => $msg, 'reload' => true]);
