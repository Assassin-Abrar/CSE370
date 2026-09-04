<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$budgetId = (int)($_POST['budget_id'] ?? 0);
$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM budget_requests WHERE id = ? AND club_id = ? AND status = 'draft'");
$stmt->execute([$budgetId, $club['id']]);
$budget = $stmt->fetch();
if (!$budget) jsonResponse(['ok' => false, 'message' => 'Draft not found.'], 404);

$stmt = $pdo->prepare("UPDATE budget_requests SET status = 'submitted', submitted_at = NOW() WHERE id = ?");
$stmt->execute([$budgetId]);
$stmt = $pdo->prepare('INSERT INTO budget_audit_log (budget_request_id, action, actor_user_id, notes) VALUES (?,"submitted",?,"Draft submitted for review.")');
$stmt->execute([$budgetId, $user['id']]);

$admins = $pdo->query("SELECT id FROM users WHERE role='admin' AND status='active'")->fetchAll();
foreach ($admins as $a) {
    notify((int)$a['id'], 'budget', 'Budget request pending', $club['name'] . ' submitted a ' . moneyBDT($budget['requested_amount']) . ' request for "' . $budget['event_name'] . '".', '/admin/budgets.php');
}

jsonResponse(['ok' => true, 'message' => 'Draft submitted for OCA review.', 'reload' => true]);
