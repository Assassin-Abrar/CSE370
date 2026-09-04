<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['admin']);
requireCsrfOrFail();

$budgetId = (int)($_POST['budget_id'] ?? 0);
$action = $_POST['action'] ?? '';
$comment = trim($_POST['comment'] ?? '');
$approvedAmount = $_POST['approved_amount'] ?? null;

if (!in_array($action, ['approve', 'reject', 'under_review'], true)) jsonResponse(['ok' => false, 'message' => 'Invalid action.'], 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM budget_requests WHERE id = ?');
$stmt->execute([$budgetId]);
$budget = $stmt->fetch();
if (!$budget) jsonResponse(['ok' => false, 'message' => 'Budget request not found.'], 404);

if ($action === 'under_review') {
    $stmt = $pdo->prepare("UPDATE budget_requests SET status = 'under_review' WHERE id = ?");
    $stmt->execute([$budgetId]);
    $note = 'Moved to under OCA review.';
    $newStatus = 'under_review';
} else {
    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
    $amt = $action === 'approve' ? (float)($approvedAmount ?: $budget['requested_amount']) : null;
    $stmt = $pdo->prepare('UPDATE budget_requests SET status = ?, approved_amount = ?, reviewed_by = ?, reviewed_at = NOW(), review_comment = ? WHERE id = ?');
    $stmt->execute([$newStatus, $amt, $user['id'], $comment, $budgetId]);
    $note = $comment ?: ($action === 'approve' ? 'Approved.' : 'Rejected.');
}

$stmt = $pdo->prepare('INSERT INTO budget_audit_log (budget_request_id, action, actor_user_id, notes) VALUES (?,?,?,?)');
$stmt->execute([$budgetId, $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : 'status_changed'), $user['id'], $note]);

$stmt = $pdo->prepare("SELECT user_id FROM club_members WHERE club_id = ? AND member_role IN ('exec','president')");
$stmt->execute([$budget['club_id']]);
foreach ($stmt->fetchAll() as $row) {
    $msg = $newStatus === 'approved'
        ? 'Your budget for "' . $budget['event_name'] . '" was approved' . ($amt ?? null ? ' at ' . moneyBDT($amt) : '') . '.'
        : 'Your budget for "' . $budget['event_name'] . '" is now ' . statusLabel($newStatus) . ($comment ? ': ' . $comment : '.');
    notify((int)$row['user_id'], 'budget', 'Budget ' . statusLabel($newStatus), $msg, '/exec/budget.php');
}
logAudit($user['id'], 'reviewed_budget', 'budget_request', $budgetId, $note);

jsonResponse(['ok' => true, 'message' => 'Budget request updated.', 'reload' => true, 'closeModal' => true]);
