<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['student']);
$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch() ?: [];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM student_skills WHERE user_id = ?');
$stmt->execute([$user['id']]);
$skillCount = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM student_interests WHERE user_id = ?');
$stmt->execute([$user['id']]);
$interestCount = (int)$stmt->fetchColumn();

$fields = ['student_id', 'department', 'semester', 'bio', 'phone'];
$filled = 0;
foreach ($fields as $f) if (!empty($profile[$f])) $filled++;
$completionParts = $filled + ($skillCount > 0 ? 1 : 0) + ($interestCount > 0 ? 1 : 0);
$completion = (int)round(($completionParts / (count($fields) + 2)) * 100);

$matches = getAllClubMatches((int)$user['id']);
$topMatches = array_slice($matches, 0, 3);
$recruiting = array_slice(array_filter($matches, fn($c) => $c['recruitment_status'] === 'open'), 0, 4);

$stmt = $pdo->prepare("SELECT e.*, c.name AS club_name, v.name AS venue_name FROM events e LEFT JOIN clubs c ON c.id=e.club_id LEFT JOIN venues v ON v.id=e.venue_id WHERE e.status='approved' AND e.event_date >= CURDATE() ORDER BY e.event_date LIMIT 4");
$stmt->execute();
$upcomingEvents = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT e.title, e.event_date FROM event_registrations r JOIN events e ON e.id = r.event_id WHERE r.user_id = ? AND e.event_date >= CURDATE() ORDER BY e.event_date LIMIT 5");
$stmt->execute([$user['id']]);
$myRegisteredEvents = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->execute([$user['id']]);
$notifs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT c.name, c.logo_color, cm.position FROM club_members cm JOIN clubs c ON c.id=cm.club_id WHERE cm.user_id = ? AND cm.status='active'");
$stmt->execute([$user['id']]);
$myClubs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE user_id = ? AND status NOT IN ('accepted','rejected')");
$stmt->execute([$user['id']]);
$pendingApps = (int)$stmt->fetchColumn();

$pageTitle = 'Dashboard';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div>
    <h1>Welcome back, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h1>
    <div class="sub">Here's what's happening across your clubs today.</div>
  </div>
</div>

<?php if ($completion < 100): ?>
<div class="card" style="margin-bottom:22px;background:var(--primary-light);border-color:#c7d2fe;">
  <div class="flex-between">
    <div>
      <strong>Your profile is <?= $completion ?>% complete</strong>
      <p class="text-sm text-muted mt-0">Add more skills and interests to improve your club match accuracy.</p>
    </div>
    <a href="profile.php" class="btn btn-primary btn-sm">Complete Profile</a>
  </div>
  <div class="progress" style="margin-top:12px;"><span style="width:<?= $completion ?>%"></span></div>
</div>
<?php endif; ?>

<div class="grid grid-4" style="margin-bottom:24px;">
  <div class="card stat-tile">
    <div class="top"><div class="icon-wrap" style="background:var(--primary-light);color:var(--primary-dark);"><?= icon('briefcase') ?></div></div>
    <div class="value"><?= count($myClubs) ?></div><div class="label">My Memberships</div>
  </div>
  <div class="card stat-tile">
    <div class="top"><div class="icon-wrap" style="background:var(--blue-light);color:var(--blue-dark);"><?= icon('calendar') ?></div></div>
    <div class="value"><?= count($myRegisteredEvents) ?></div><div class="label">Registered Events</div>
  </div>
  <div class="card stat-tile">
    <div class="top"><div class="icon-wrap" style="background:var(--amber-light);color:var(--amber-dark);"><?= icon('clipboard') ?></div></div>
    <div class="value"><?= $pendingApps ?></div><div class="label">Pending Applications</div>
  </div>
  <div class="card stat-tile">
    <div class="top"><div class="icon-wrap" style="background:var(--green-light);color:var(--green-dark);"><?= icon('star') ?></div></div>
    <div class="value"><?= $topMatches[0]['match']['percent'] ?? 0 ?>%</div><div class="label">Top Club Match</div>
  </div>
</div>

<div class="grid grid-2" style="align-items:start;">
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Recommended for you</span><a href="clubs.php" class="text-sm fw-600" style="color:var(--primary);">See all &rarr;</a></div>
    <?php if (!$topMatches): ?>
      <div class="empty-state"><div class="icon">🎯</div><h4>No matches yet</h4><p>Add skills and interests to your profile to see recommendations.</p></div>
    <?php else: foreach ($topMatches as $c): ?>
      <a href="club.php?slug=<?= e($c['slug']) ?>" class="list-item-hover" style="display:flex;gap:12px;align-items:center;padding:10px;text-decoration:none;color:inherit;">
        <div class="match-ring">
          <svg width="56" height="56"><circle class="track" cx="28" cy="28" r="24" fill="none" stroke-width="5"/><circle cx="28" cy="28" r="24" fill="none" stroke="<?= $c['match']['percent']>=70?'#16a34a':($c['match']['percent']>=40?'#d97706':'#94a3b8') ?>" stroke-width="5" stroke-dasharray="<?= round(2*3.1416*24) ?>" stroke-dashoffset="<?= round(2*3.1416*24*(1-$c['match']['percent']/100)) ?>" stroke-linecap="round"/></svg>
          <span class="pct"><?= $c['match']['percent'] ?>%</span>
        </div>
        <div style="flex:1;min-width:0;">
          <strong style="display:block;"><?= e($c['name']) ?></strong>
          <span class="text-sm text-muted"><?= e($c['category']) ?> <?= $c['recruitment_status']==='open' ? '· <span style="color:var(--green-dark);font-weight:600;">Recruiting</span>' : '' ?></span>
        </div>
      </a>
    <?php endforeach; endif; ?>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Recent notifications</span><a href="notifications.php" class="text-sm fw-600" style="color:var(--primary);">View all &rarr;</a></div>
    <?php if (!$notifs): ?>
      <div class="empty-state"><div class="icon">🔔</div><h4>Nothing yet</h4><p>You're all caught up.</p></div>
    <?php else: foreach ($notifs as $n): ?>
      <div class="list-item-hover" style="display:flex;gap:10px;padding:10px;">
        <span class="dot-mark" style="margin-top:6px;background:<?= $n['is_read']?'transparent':'var(--primary)' ?>;"></span>
        <div style="flex:1;">
          <strong class="text-sm"><?= e($n['title']) ?></strong>
          <p class="text-sm text-muted mt-0" style="margin-bottom:2px;"><?= e($n['message']) ?></p>
          <span class="text-xs text-subtle"><?= timeAgo($n['created_at']) ?></span>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="grid grid-2" style="align-items:start;">
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Clubs currently recruiting</span></div>
    <?php if (!$recruiting): ?>
      <div class="empty-state"><div class="icon">📭</div><h4>No open recruitment right now</h4></div>
    <?php else: foreach ($recruiting as $c): ?>
      <a href="club.php?slug=<?= e($c['slug']) ?>" class="list-item-hover" style="display:flex;gap:12px;align-items:center;padding:10px;text-decoration:none;color:inherit;">
        <span class="logo" style="width:40px;height:40px;border-radius:11px;background:<?= e($c['logo_color']) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;"><?= e(mb_substr($c['name'],0,1)) ?></span>
        <div style="flex:1;"><strong class="text-sm"><?= e($c['name']) ?></strong><br><span class="text-xs text-muted"><?= e($c['category']) ?></span></div>
        <span class="badge badge-green"><?= $c['match']['percent'] ?>% match</span>
      </a>
    <?php endforeach; endif; ?>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Upcoming events</span><a href="events.php" class="text-sm fw-600" style="color:var(--primary);">See all &rarr;</a></div>
    <?php if (!$upcomingEvents): ?>
      <div class="empty-state"><div class="icon">📅</div><h4>No upcoming events</h4></div>
    <?php else: foreach ($upcomingEvents as $ev): ?>
      <div class="list-item-hover" style="display:flex;gap:12px;padding:10px;">
        <div style="width:46px;text-align:center;background:var(--gray-light);border-radius:8px;padding:4px 0;flex-shrink:0;">
          <div class="text-xs text-subtle fw-700"><?= date('M', strtotime($ev['event_date'])) ?></div>
          <div class="fw-700"><?= date('d', strtotime($ev['event_date'])) ?></div>
        </div>
        <div style="flex:1;min-width:0;">
          <strong class="text-sm"><?= e($ev['title']) ?></strong><br>
          <span class="text-xs text-muted"><?= e($ev['club_name'] ?? 'OCA') ?> · <?= e($ev['venue_name'] ?? 'TBA') ?></span>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><span class="card-title">Quick actions</span></div>
  <div class="grid grid-4">
    <a href="clubs.php" class="btn btn-outline btn-block"><?= icon('compass') ?> Find Clubs</a>
    <a href="events.php" class="btn btn-outline btn-block"><?= icon('calendar') ?> Explore Events</a>
    <a href="profile.php" class="btn btn-outline btn-block"><?= icon('user') ?> Update Skills</a>
    <a href="my_applications.php" class="btn btn-outline btn-block"><?= icon('clipboard') ?> View Applications</a>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
