<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;font-family:sans-serif;">Your account is not linked to a club yet. Contact OCA.</p>'); }
$pdo = db();
$cid = $club['id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE club_id=? AND status='active'"); $stmt->execute([$cid]); $activeMembers = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE club_id=? AND status='approved' AND event_date >= CURDATE()"); $stmt->execute([$cid]); $upcomingEventsCount = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE club_id=? AND status IN ('submitted','under_review')"); $stmt->execute([$cid]); $pendingApps = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(approved_amount),0) FROM budget_requests WHERE club_id=? AND status='approved'"); $stmt->execute([$cid]); $budgetAllocated = (float)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE club_id=? AND status != 'completed'"); $stmt->execute([$cid]); $pendingTasks = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT a.*, u.name AS applicant_name, u.avatar_color FROM applications a JOIN users u ON u.id=a.user_id WHERE a.club_id=? AND a.status IN ('submitted','under_review') ORDER BY a.applied_at DESC LIMIT 5");
$stmt->execute([$cid]);
$recentApps = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT e.*, v.name AS venue_name FROM events e LEFT JOIN venues v ON v.id=e.venue_id WHERE e.club_id=? AND e.event_date >= CURDATE() ORDER BY e.event_date LIMIT 5");
$stmt->execute([$cid]);
$upcomingEvents = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM budget_requests WHERE club_id=? ORDER BY submitted_at DESC LIMIT 4");
$stmt->execute([$cid]);
$budgets = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM collaboration_requests WHERE club_id=? ORDER BY created_at DESC LIMIT 4");
$stmt->execute([$cid]);
$collabs = $stmt->fetchAll();

$members = clubMembersWithWorkload($cid);
usort($members, fn($a, $b) => $b['workload']['percent'] <=> $a['workload']['percent']);
$topWorkload = array_slice($members, 0, 4);

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$user['id']]);
$notifs = $stmt->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1><?= e($club['name']) ?></h1><div class="sub">Executive dashboard · <?= statusLabel($user['role']) ?> overview</div></div>
  <div class="flex-center-gap">
    <a href="events.php" class="btn btn-outline"><?= icon('plus','icon') ?> New Event</a>
    <a href="tasks.php" class="btn btn-primary"><?= icon('plus','icon') ?> New Task</a>
  </div>
</div>

<div class="grid grid-4" style="margin-bottom:22px;">
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--primary-light);color:var(--primary-dark);"><?= icon('users') ?></div></div><div class="value"><?= $activeMembers ?></div><div class="label">Active Members</div></div>
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--blue-light);color:var(--blue-dark);"><?= icon('calendar') ?></div></div><div class="value"><?= $upcomingEventsCount ?></div><div class="label">Upcoming Events</div></div>
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--amber-light);color:var(--amber-dark);"><?= icon('clipboard') ?></div></div><div class="value"><?= $pendingApps ?></div><div class="label">Pending Applications</div></div>
  <div class="card stat-tile"><div class="top"><div class="icon-wrap" style="background:var(--green-light);color:var(--green-dark);"><?= icon('dollar') ?></div></div><div class="value"><?= moneyBDT($budgetAllocated) ?></div><div class="label">Budget Allocated</div></div>
</div>

<div class="grid grid-2" style="align-items:start;">
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Pending recruitment applications</span><a href="recruitment.php" class="text-sm fw-600" style="color:var(--primary);">View pipeline &rarr;</a></div>
    <?php if (!$recentApps): ?><div class="empty-state"><div class="icon">📭</div><h4>No pending applications</h4></div>
    <?php else: foreach ($recentApps as $a): ?>
      <div class="list-item-hover flex-between" style="padding:10px;">
        <div class="flex-center-gap"><span class="avatar" style="width:32px;height:32px;font-size:12px;background:<?= e($a['avatar_color']) ?>;"><?= e(initials($a['applicant_name'])) ?></span><strong class="text-sm"><?= e($a['applicant_name']) ?></strong></div>
        <span class="badge <?= badgeClass($a['status']) ?>"><?= statusLabel($a['status']) ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Team workload overview</span><a href="workload.php" class="text-sm fw-600" style="color:var(--primary);">Full analysis &rarr;</a></div>
    <?php foreach ($topWorkload as $m): $wl = $m['workload']; ?>
      <div class="workload-row">
        <span class="avatar" style="width:32px;height:32px;font-size:12px;background:<?= e($m['avatar_color']) ?>;"><?= e(initials($m['name'])) ?></span>
        <div class="info"><strong class="text-sm"><?= e($m['name']) ?></strong><div class="progress <?= $wl['status']==='Overloaded'?'red':($wl['status']==='Busy'?'amber':'green') ?>" style="margin-top:6px;"><span style="width:<?= $wl['percent'] ?>%"></span></div></div>
        <span class="pct"><?= $wl['percent'] ?>%</span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid grid-2" style="align-items:start;">
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Upcoming events</span><a href="events.php" class="text-sm fw-600" style="color:var(--primary);">See all &rarr;</a></div>
    <?php if (!$upcomingEvents): ?><div class="empty-state"><div class="icon">📅</div><h4>No upcoming events</h4></div>
    <?php else: foreach ($upcomingEvents as $ev): ?>
      <div class="list-item-hover flex-between" style="padding:10px;">
        <div><strong class="text-sm"><?= e($ev['title']) ?></strong><br><span class="text-xs text-muted"><?= date('M j', strtotime($ev['event_date'])) ?> · <?= e($ev['venue_name'] ?? 'TBA') ?></span></div>
        <span class="badge <?= badgeClass($ev['status']) ?>"><?= statusLabel($ev['status']) ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Budget status</span><a href="budget.php" class="text-sm fw-600" style="color:var(--primary);">View all &rarr;</a></div>
    <?php if (!$budgets): ?><div class="empty-state"><div class="icon">💰</div><h4>No budget requests yet</h4></div>
    <?php else: foreach ($budgets as $b): ?>
      <div class="list-item-hover flex-between" style="padding:10px;">
        <div><strong class="text-sm"><?= e($b['event_name']) ?></strong><br><span class="text-xs text-muted"><?= moneyBDT($b['requested_amount']) ?> requested</span></div>
        <span class="badge <?= badgeClass($b['status']) ?>"><?= statusLabel($b['status']) ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="grid grid-2" style="align-items:start;">
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Collaboration requests</span><a href="collaboration.php" class="text-sm fw-600" style="color:var(--primary);">Open board &rarr;</a></div>
    <?php if (!$collabs): ?><div class="empty-state"><div class="icon">🤝</div><h4>No collaboration requests</h4></div>
    <?php else: foreach ($collabs as $c): ?>
      <div class="list-item-hover flex-between" style="padding:10px;">
        <strong class="text-sm"><?= e($c['title']) ?></strong>
        <span class="badge <?= badgeClass($c['status']) ?>"><?= statusLabel($c['status']) ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Notifications</span><a href="notifications.php" class="text-sm fw-600" style="color:var(--primary);">View all &rarr;</a></div>
    <?php if (!$notifs): ?><div class="empty-state"><div class="icon">🔔</div><h4>Nothing new</h4></div>
    <?php else: foreach ($notifs as $n): ?>
      <div class="list-item-hover" style="padding:10px;">
        <strong class="text-sm"><?= e($n['title']) ?></strong>
        <p class="text-sm text-muted mt-0"><?= e($n['message']) ?></p>
        <span class="text-xs text-subtle"><?= timeAgo($n['created_at']) ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
