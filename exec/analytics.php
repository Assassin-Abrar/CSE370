<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;">Not linked to a club.</p>'); }
$pdo = db();
$cid = $club['id'];

$stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM applications WHERE club_id=? GROUP BY status");
$stmt->execute([$cid]);
$appRows = $stmt->fetchAll();
$appStages = ['submitted','under_review','shortlisted','interview','accepted','rejected'];
$appCounts = array_fill_keys($appStages, 0);
foreach ($appRows as $r) $appCounts[$r['status']] = (int)$r['c'];

$stmt = $pdo->prepare("SELECT status, COUNT(*) c FROM tasks WHERE club_id=? GROUP BY status");
$stmt->execute([$cid]);
$taskRows = $stmt->fetchAll();
$taskStages = ['todo','in_progress','review','completed'];
$taskCounts = array_fill_keys($taskStages, 0);
foreach ($taskRows as $r) $taskCounts[$r['status']] = (int)$r['c'];

$stmt = $pdo->prepare("SELECT DATE_FORMAT(event_date,'%b') m, COUNT(*) c FROM events WHERE club_id=? GROUP BY DATE_FORMAT(event_date,'%Y-%m') ORDER BY event_date");
$stmt->execute([$cid]);
$eventsByMonth = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT status, COALESCE(SUM(CASE WHEN status='approved' THEN approved_amount ELSE requested_amount END),0) total FROM budget_requests WHERE club_id=? GROUP BY status");
$stmt->execute([$cid]);
$budgetRows = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE club_id=? AND status='active'");
$stmt->execute([$cid]);
$activeMembers = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE club_id=?");
$stmt->execute([$cid]);
$totalApps = (int)$stmt->fetchColumn();
$conversionRate = $totalApps > 0 ? round(($appCounts['accepted'] / $totalApps) * 100) : 0;

$pageTitle = 'Analytics';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>Club Analytics</h1><div class="sub">Performance across recruitment, events, tasks and budget.</div></div></div>

<div class="grid grid-4" style="margin-bottom:22px;">
  <div class="card stat-tile"><div class="value"><?= $activeMembers ?></div><div class="label">Active Members</div></div>
  <div class="card stat-tile"><div class="value"><?= $totalApps ?></div><div class="label">Total Applications</div></div>
  <div class="card stat-tile"><div class="value"><?= $conversionRate ?>%</div><div class="label">Recruitment Conversion</div></div>
  <div class="card stat-tile"><div class="value"><?= $taskCounts['completed'] ?>/<?= array_sum($taskCounts) ?></div><div class="label">Tasks Completed</div></div>
</div>

<div class="grid grid-2">
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Recruitment funnel</h4>
    <canvas id="funnelChart"></canvas>
  </div>
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Task completion</h4>
    <canvas id="taskChart"></canvas>
  </div>
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Event frequency</h4>
    <canvas id="eventChart"></canvas>
  </div>
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Budget usage</h4>
    <canvas id="budgetChart"></canvas>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  mkBarChart('funnelChart', <?= json_encode(array_map('statusLabel', $appStages)) ?>, [{ label: 'Applications', data: <?= json_encode(array_values($appCounts)) ?> }]);
  mkDoughnutChart('taskChart', <?= json_encode(array_map('statusLabel', $taskStages)) ?>, <?= json_encode(array_values($taskCounts)) ?>);
  mkLineChart('eventChart', <?= json_encode(array_column($eventsByMonth, 'm')) ?>, [{ label: 'Events', data: <?= json_encode(array_map('intval', array_column($eventsByMonth, 'c'))) ?> }]);
  mkBarChart('budgetChart', <?= json_encode(array_map('statusLabel', array_column($budgetRows, 'status'))) ?>, [{ label: 'Amount (৳)', data: <?= json_encode(array_map('floatval', array_column($budgetRows, 'total'))) ?> }]);
});
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
