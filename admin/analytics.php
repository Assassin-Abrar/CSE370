<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['admin']);
$pdo = db();

$stmt = $pdo->query("SELECT c.name, COUNT(DISTINCT cm.user_id) mc FROM clubs c LEFT JOIN club_members cm ON cm.club_id=c.id AND cm.status='active' GROUP BY c.id ORDER BY mc DESC LIMIT 8");
$activeClubs = $stmt->fetchAll();

$stmt = $pdo->query("SELECT e.title, COUNT(r.id) rc FROM events e LEFT JOIN event_registrations r ON r.event_id=e.id GROUP BY e.id ORDER BY rc DESC LIMIT 6");
$popularEvents = $stmt->fetchAll();

$stmt = $pdo->query("SELECT DATE_FORMAT(applied_at,'%b') m, COUNT(*) c FROM applications GROUP BY DATE_FORMAT(applied_at,'%Y-%m') ORDER BY MIN(applied_at)");
$recruitmentTrend = $stmt->fetchAll();

$stmt = $pdo->query("SELECT v.name, COUNT(e.id) c FROM venues v LEFT JOIN events e ON e.venue_id=v.id AND e.status IN ('approved','completed') GROUP BY v.id ORDER BY c DESC");
$venueUsage = $stmt->fetchAll();

$stmt = $pdo->query("SELECT c.name, COALESCE(SUM(b.approved_amount),0) total FROM clubs c LEFT JOIN budget_requests b ON b.club_id=c.id AND b.status='approved' GROUP BY c.id ORDER BY total DESC LIMIT 8");
$budgetByClub = $stmt->fetchAll();

$stmt = $pdo->query("SELECT c.name, COUNT(cr.id) c FROM clubs c LEFT JOIN collaboration_requests cr ON cr.club_id=c.id GROUP BY c.id ORDER BY c DESC LIMIT 8");
$collabByClub = $stmt->fetchAll();

$totalEvents = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalCollab = (int)$pdo->query("SELECT COUNT(*) FROM collaboration_requests")->fetchColumn();
$totalBudgetApproved = (float)$pdo->query("SELECT COALESCE(SUM(approved_amount),0) FROM budget_requests WHERE status='approved'")->fetchColumn();
$avgConversion = 0;
$totalApps = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$acceptedApps = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='accepted'")->fetchColumn();
if ($totalApps > 0) $avgConversion = round(($acceptedApps / $totalApps) * 100);

$pageTitle = 'Analytics';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>Campus Analytics</h1><div class="sub">Insights across all clubs and activity.</div></div></div>

<div class="grid grid-4" style="margin-bottom:22px;">
  <div class="card stat-tile"><div class="value"><?= $totalEvents ?></div><div class="label">Total Events</div></div>
  <div class="card stat-tile"><div class="value"><?= moneyBDT($totalBudgetApproved) ?></div><div class="label">Budget Allocated</div></div>
  <div class="card stat-tile"><div class="value"><?= $totalCollab ?></div><div class="label">Collaboration Requests</div></div>
  <div class="card stat-tile"><div class="value"><?= $avgConversion ?>%</div><div class="label">Recruitment Conversion</div></div>
</div>

<div class="grid grid-2">
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Most active clubs (members)</h4>
    <canvas id="activeClubsChart"></canvas>
  </div>
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Most popular events (registrations)</h4>
    <canvas id="popularEventsChart"></canvas>
  </div>
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Recruitment trend</h4>
    <canvas id="recruitmentChart"></canvas>
  </div>
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Venue utilization</h4>
    <canvas id="venueChart"></canvas>
  </div>
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Budget allocation by club</h4>
    <canvas id="budgetClubChart"></canvas>
  </div>
  <div class="card chart-card h-260" style="margin-bottom:20px;">
    <h4 style="margin-bottom:14px;">Collaboration activity by club</h4>
    <canvas id="collabClubChart"></canvas>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  mkBarChart('activeClubsChart', <?= json_encode(array_column($activeClubs,'name')) ?>, [{ label:'Members', data: <?= json_encode(array_map('intval',array_column($activeClubs,'mc'))) ?> }]);
  mkDoughnutChart('popularEventsChart', <?= json_encode(array_column($popularEvents,'title')) ?>, <?= json_encode(array_map('intval',array_column($popularEvents,'rc'))) ?>);
  mkLineChart('recruitmentChart', <?= json_encode(array_column($recruitmentTrend,'m')) ?>, [{ label:'Applications', data: <?= json_encode(array_map('intval',array_column($recruitmentTrend,'c'))) ?> }]);
  mkBarChart('venueChart', <?= json_encode(array_column($venueUsage,'name')) ?>, [{ label:'Bookings', data: <?= json_encode(array_map('intval',array_column($venueUsage,'c'))) ?> }]);
  mkBarChart('budgetClubChart', <?= json_encode(array_column($budgetByClub,'name')) ?>, [{ label:'Allocated (৳)', data: <?= json_encode(array_map('floatval',array_column($budgetByClub,'total'))) ?> }]);
  mkDoughnutChart('collabClubChart', <?= json_encode(array_column($collabByClub,'name')) ?>, <?= json_encode(array_map('intval',array_column($collabByClub,'c'))) ?>);
});
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
