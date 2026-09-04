<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['admin']);
$pdo = db();

$totalClubs = (int)$pdo->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
$activeClubs = (int)$pdo->query("SELECT COUNT(*) FROM clubs WHERE status='active'")->fetchColumn();
$upcomingEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status='approved' AND event_date >= CURDATE()")->fetchColumn();
$pendingEvents = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status='submitted'")->fetchColumn();
$pendingBudgets = (int)$pdo->query("SELECT COUNT(*) FROM budget_requests WHERE status IN ('submitted','under_review')")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalVenues = (int)$pdo->query("SELECT COUNT(*) FROM venues")->fetchColumn();
$recruitingClubs = (int)$pdo->query("SELECT COUNT(*) FROM clubs WHERE recruitment_status='open'")->fetchColumn();
$pendingApprovals = $pendingEvents + $pendingBudgets;

$stmt = $pdo->query("SELECT e.*, c.name AS club_name FROM events e LEFT JOIN clubs c ON c.id=e.club_id WHERE e.status='submitted' ORDER BY e.created_at DESC LIMIT 5");
$pendingEventList = $stmt->fetchAll();

$stmt = $pdo->query("SELECT b.*, c.name AS club_name FROM budget_requests b JOIN clubs c ON c.id=b.club_id WHERE b.status IN ('submitted','under_review') ORDER BY b.submitted_at DESC LIMIT 5");
$pendingBudgetList = $stmt->fetchAll();

$stmt = $pdo->query("SELECT e1.id a_id, e1.title a_title, e1.event_date, v.name venue, c1.name club1, e2.id b_id, e2.title b_title, c2.name club2
  FROM events e1 JOIN events e2 ON e1.venue_id = e2.venue_id AND e1.event_date = e2.event_date AND e1.id < e2.id
    AND NOT (e1.end_time <= e2.start_time OR e1.start_time >= e2.end_time)
  LEFT JOIN venues v ON v.id = e1.venue_id
  LEFT JOIN clubs c1 ON c1.id = e1.club_id LEFT JOIN clubs c2 ON c2.id = e2.club_id
  WHERE e1.status IN ('submitted','approved') AND e2.status IN ('submitted','approved')");
$conflicts = $stmt->fetchAll();

$stmt = $pdo->query("SELECT al.*, u.name AS actor FROM audit_log al LEFT JOIN users u ON u.id=al.actor_user_id ORDER BY al.created_at DESC LIMIT 6");
$recentActivity = $stmt->fetchAll();

$stmt = $pdo->query("SELECT c.name, COUNT(DISTINCT cm.user_id) mc FROM clubs c LEFT JOIN club_members cm ON cm.club_id=c.id AND cm.status='active' GROUP BY c.id ORDER BY mc DESC LIMIT 6");
$clubActivity = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>OCA Admin Dashboard</h1><div class="sub">Campus-wide oversight of clubs, events, and budgets.</div></div></div>

<div class="grid grid-4" style="margin-bottom:22px;">
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--primary-light);color:var(--primary-dark);"><?= icon('briefcase') ?></div></div><div class="value"><?= $totalClubs ?></div><div class="label">Total Clubs</div></div>
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--green-light);color:var(--green-dark);"><?= icon('check-circle') ?></div></div><div class="value"><?= $activeClubs ?></div><div class="label">Active Clubs</div></div>
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--blue-light);color:var(--blue-dark);"><?= icon('calendar') ?></div></div><div class="value"><?= $upcomingEvents ?></div><div class="label">Upcoming Events</div></div>
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--amber-light);color:var(--amber-dark);"><?= icon('alert-triangle') ?></div></div><div class="value"><?= $pendingApprovals ?></div><div class="label">Pending Approvals</div></div>
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--purple-light);color:var(--purple-dark);"><?= icon('users') ?></div></div><div class="value"><?= $totalStudents ?></div><div class="label">Total Students</div></div>
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--red-light);color:var(--red-dark);"><?= icon('dollar') ?></div></div><div class="value"><?= $pendingBudgets ?></div><div class="label">Budget Requests</div></div>
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--gray-light);color:var(--gray);"><?= icon('map-pin') ?></div></div><div class="value"><?= $totalVenues ?></div><div class="label">Venues</div></div>
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--green-light);color:var(--green-dark);"><?= icon('zap') ?></div></div><div class="value"><?= $recruitingClubs ?></div><div class="label">Clubs Recruiting</div></div>
</div>

<?php if ($conflicts): ?>
<div class="card" style="margin-bottom:22px;background:var(--red-light);border-color:#fecaca;">
  <div class="flex-center-gap" style="margin-bottom:8px;"><?= icon('alert-triangle','icon') ?><strong style="color:var(--red-dark);">Venue conflicts detected</strong></div>
  <?php foreach ($conflicts as $c): ?>
    <p class="text-sm" style="margin:4px 0;">"<?= e($c['a_title']) ?>" (<?= e($c['club1']) ?>) overlaps with "<?= e($c['b_title']) ?>" (<?= e($c['club2']) ?>) at <?= e($c['venue']) ?> on <?= date('M j, Y', strtotime($c['event_date'])) ?>.</p>
  <?php endforeach; ?>
  <a href="venues.php" class="btn btn-sm btn-danger" style="margin-top:8px;">Resolve in Venues</a>
</div>
<?php endif; ?>

<div class="grid grid-2" style="align-items:start;">
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Pending event proposals</span><a href="events.php" class="text-sm fw-600" style="color:var(--primary);">Review all &rarr;</a></div>
    <?php if (!$pendingEventList): ?><div class="empty-state"><div class="icon">✅</div><h4>Nothing pending</h4></div>
    <?php else: foreach ($pendingEventList as $ev): ?>
      <div class="list-item-hover flex-between" style="padding:10px;">
        <div><strong class="text-sm"><?= e($ev['title']) ?></strong><br><span class="text-xs text-muted"><?= e($ev['club_name'] ?? 'OCA') ?> · <?= date('M j', strtotime($ev['event_date'])) ?></span></div>
        <span class="badge badge-amber">Pending</span>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Pending budget requests</span><a href="budgets.php" class="text-sm fw-600" style="color:var(--primary);">Review all &rarr;</a></div>
    <?php if (!$pendingBudgetList): ?><div class="empty-state"><div class="icon">✅</div><h4>Nothing pending</h4></div>
    <?php else: foreach ($pendingBudgetList as $b): ?>
      <div class="list-item-hover flex-between" style="padding:10px;">
        <div><strong class="text-sm"><?= e($b['event_name']) ?></strong><br><span class="text-xs text-muted"><?= e($b['club_name']) ?> · <?= moneyBDT($b['requested_amount']) ?></span></div>
        <span class="badge badge-amber"><?= statusLabel($b['status']) ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="grid grid-2" style="align-items:start;">
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Club activity (members)</h4>
    <canvas id="clubActivityChart"></canvas>
  </div>
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Recent activity</span></div>
    <?php foreach ($recentActivity as $a): ?>
      <div class="list-item-hover" style="padding:10px;">
        <strong class="text-sm"><?= e(statusLabel($a['action'])) ?></strong>
        <p class="text-sm text-muted mt-0"><?= e($a['details']) ?></p>
        <span class="text-xs text-subtle"><?= e($a['actor'] ?? 'System') ?> · <?= timeAgo($a['created_at']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  mkBarChart('clubActivityChart', <?= json_encode(array_column($clubActivity,'name')) ?>, [{ label: 'Members', data: <?= json_encode(array_map('intval', array_column($clubActivity,'mc'))) ?> }]);
});
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
