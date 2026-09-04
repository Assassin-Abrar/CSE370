<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['exec']);
requireCsrfOrFail();

$club = myClub($user);
if (!$club) jsonResponse(['ok' => false, 'message' => 'You are not linked to a club.'], 403);

$eventName = trim($_POST['event_name'] ?? '');
$eventDate = $_POST['event_date'] ?? null;
$venueId = !empty($_POST['venue_id']) ? (int)$_POST['venue_id'] : null;
$attendance = !empty($_POST['expected_attendance']) ? (int)$_POST['expected_attendance'] : null;
$justification = trim($_POST['justification'] ?? '');
$eventId = !empty($_POST['event_id']) ? (int)$_POST['event_id'] : null;
$intent = $_POST['intent'] ?? 'submitted';
$categories = $_POST['category'] ?? [];
$amounts = $_POST['amount'] ?? [];

$errors = [];
if ($eventName === '') $errors['event_name'] = 'Event name is required.';
if (empty($categories)) $errors['category'] = 'Add at least one budget category.';
if ($errors) jsonResponse(['ok' => false, 'message' => 'Please fix the errors below.', 'errors' => $errors], 422);

$total = 0;
$items = [];
foreach ($categories as $i => $cat) {
    $cat = trim($cat);
    $amt = (float)($amounts[$i] ?? 0);
    if ($cat === '' || $amt <= 0) continue;
    $items[] = [$cat, $amt];
    $total += $amt;
}
if (!$items) jsonResponse(['ok' => false, 'message' => 'Add at least one valid budget category with an amount.'], 422);

$pdo = db();
$status = $intent === 'draft' ? 'draft' : 'submitted';
$stmt = $pdo->prepare('INSERT INTO budget_requests (event_id, club_id, event_name, event_date, venue_id, expected_attendance, requested_amount, justification, status, submitted_by) VALUES (?,?,?,?,?,?,?,?,?,?)');
$stmt->execute([$eventId, $club['id'], $eventName, $eventDate ?: null, $venueId, $attendance, $total, $justification, $status, $user['id']]);
$budgetId = (int)$pdo->lastInsertId();

$itemStmt = $pdo->prepare('INSERT INTO budget_items (budget_request_id, category, amount) VALUES (?,?,?)');
foreach ($items as [$cat, $amt]) $itemStmt->execute([$budgetId, $cat, $amt]);

$logStmt = $pdo->prepare('INSERT INTO budget_audit_log (budget_request_id, action, actor_user_id, notes) VALUES (?,?,?,?)');
$logStmt->execute([$budgetId, $status === 'draft' ? 'saved_draft' : 'submitted', $user['id'], $status === 'draft' ? 'Saved as draft.' : 'Initial submission.']);

if ($status === 'submitted') {
    $admins = $pdo->query("SELECT id FROM users WHERE role='admin' AND status='active'")->fetchAll();
    foreach ($admins as $a) {
        notify((int)$a['id'], 'budget', 'Budget request pending', $club['name'] . ' submitted a ' . moneyBDT($total) . ' request for "' . $eventName . '".', '/admin/budgets.php');
    }
}
logAudit($user['id'], 'submitted_budget', 'budget_request', $budgetId, $eventName);

jsonResponse(['ok' => true, 'message' => $status === 'draft' ? 'Budget saved as draft.' : 'Budget request submitted for OCA review.', 'reload' => true, 'closeModal' => true]);
