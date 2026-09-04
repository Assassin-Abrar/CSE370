<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;">Not linked to a club.</p>'); }
$pdo = db();

$members = clubMembersWithWorkload($club['id']);
usort($members, fn($a, $b) => $b['workload']['percent'] <=> $a['workload']['percent']);
$overloaded = array_filter($members, fn($m) => $m['workload']['status'] === 'Overloaded');
$available = array_filter($members, fn($m) => $m['workload']['status'] !== 'Overloaded');
usort($available, fn($a, $b) => $a['workload']['percent'] <=> $b['workload']['percent']);

$pageTitle = 'Workload Analysis';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>Workload Analysis</h1><div class="sub">Automatic capacity tracking across your team.</div></div></div>

<?php if ($overloaded): ?>
<div class="card" style="margin-bottom:22px;background:var(--red-light);border-color:#fecaca;">
  <div class="flex-center-gap" style="margin-bottom:6px;"><?= icon('alert-triangle','icon') ?><strong style="color:var(--red-dark);"><?= count($overloaded) ?> member<?= count($overloaded)>1?'s':'' ?> overloaded</strong></div>
  <p class="text-sm text-muted">Consider delegating tasks from overloaded members to teammates with available capacity below.</p>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><span class="card-title">Member Workload</span></div>
  <?php foreach ($members as $m): $wl = $m['workload']; ?>
    <div class="workload-row">
      <span class="avatar" style="width:40px;height:40px;background:<?= e($m['avatar_color']) ?>;"><?= e(initials($m['name'])) ?></span>
      <div class="info">
        <div class="flex-between"><strong><?= e($m['name']) ?></strong><span class="badge <?= $wl['status']==='Overloaded'?'badge-red':($wl['status']==='Busy'?'badge-amber':'badge-green') ?>"><?= $wl['status']==='Overloaded' ? '⚠ Overloaded' : $wl['status'] ?></span></div>
        <div class="progress <?= $wl['status']==='Overloaded'?'red':($wl['status']==='Busy'?'amber':'green') ?>" style="margin-top:8px;"><span style="width:<?= $wl['percent'] ?>%"></span></div>
        <span class="text-xs text-muted"><?= $wl['active_tasks'] ?> active tasks · <?= $wl['high_priority'] ?> high priority <?= $wl['overdue'] ? '· <span style="color:var(--red-dark);font-weight:700;">' . $wl['overdue'] . ' overdue</span>' : '' ?></span>
      </div>
      <span class="pct"><?= $wl['percent'] ?>%</span>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($overloaded && $available): ?>
<div class="card" style="margin-top:22px;">
  <div class="card-header"><span class="card-title">Recommended reassignment targets</span></div>
  <p class="text-sm text-muted" style="margin-bottom:12px;">These members currently have the most available capacity:</p>
  <div class="grid grid-3">
    <?php foreach (array_slice($available, 0, 3) as $m): $wl = $m['workload']; ?>
      <div class="card" style="text-align:center;">
        <span class="avatar" style="width:44px;height:44px;background:<?= e($m['avatar_color']) ?>;margin:0 auto 8px;"><?= e(initials($m['name'])) ?></span>
        <strong><?= e($m['name']) ?></strong>
        <p class="text-sm text-muted mt-0"><?= $wl['percent'] ?>% workload</p>
        <a href="tasks.php" class="btn btn-sm btn-outline" style="margin-top:6px;">Assign a task</a>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
