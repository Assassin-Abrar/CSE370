<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['student']);
$pdo = db();

$stmt = $pdo->prepare("SELECT c.*, cm.position, cm.joined_at FROM club_members cm JOIN clubs c ON c.id=cm.club_id WHERE cm.user_id=? AND cm.status='active' ORDER BY cm.joined_at DESC");
$stmt->execute([$user['id']]);
$clubs = $stmt->fetchAll();

$pageTitle = 'My Clubs';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>My Clubs</h1><div class="sub">Clubs you're currently a member of.</div></div></div>

<?php if (!$clubs): ?>
  <div class="empty-state"><div class="icon">🏫</div><h4>You haven't joined a club yet</h4><p>Explore clubs and find your best match.</p><a href="clubs.php" class="btn btn-primary" style="margin-top:12px;">Explore Clubs</a></div>
<?php else: ?>
<div class="grid grid-3">
  <?php foreach ($clubs as $c): ?>
    <a href="club.php?slug=<?= e($c['slug']) ?>" class="card card-hover club-card" style="text-decoration:none;color:inherit;">
      <div class="head-row">
        <div class="logo" style="background:<?= e($c['logo_color']) ?>"><?= e(mb_substr($c['name'],0,1)) ?></div>
        <div><h3 class="text-lg"><?= e($c['name']) ?></h3><span class="badge badge-gray"><?= e($c['category']) ?></span></div>
      </div>
      <p class="text-sm text-muted">Joined <?= date('M Y', strtotime($c['joined_at'])) ?> as <strong><?= e($c['position']) ?></strong></p>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
