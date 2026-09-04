<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['student']);
$pdo = db();

$stmt = $pdo->prepare("SELECT a.*, c.name AS club_name, c.slug, c.logo_color FROM applications a JOIN clubs c ON c.id=a.club_id WHERE a.user_id=? ORDER BY a.applied_at DESC");
$stmt->execute([$user['id']]);
$apps = $stmt->fetchAll();

$steps = ['submitted', 'under_review', 'shortlisted', 'interview', 'accepted'];

$pageTitle = 'My Applications';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>My Applications</h1><div class="sub">Track the status of every club application you've submitted.</div></div></div>

<?php if (!$apps): ?>
  <div class="empty-state"><div class="icon">📋</div><h4>No applications yet</h4><p>Apply to a club to see its status here.</p><a href="clubs.php" class="btn btn-primary" style="margin-top:12px;">Explore Clubs</a></div>
<?php else: foreach ($apps as $app): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="flex-between" style="margin-bottom:14px;">
      <div class="flex-center-gap">
        <span class="logo" style="width:40px;height:40px;border-radius:11px;background:<?= e($app['logo_color']) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;"><?= e(mb_substr($app['club_name'],0,1)) ?></span>
        <div><strong><?= e($app['club_name']) ?></strong><br><span class="text-xs text-subtle">Applied <?= timeAgo($app['applied_at']) ?></span></div>
      </div>
      <span class="badge <?= badgeClass($app['status']) ?>" style="font-size:13px;padding:6px 14px;"><?= statusLabel($app['status']) ?></span>
    </div>

    <?php if ($app['status'] === 'rejected'): ?>
      <div class="badge badge-red" style="padding:8px 12px;">This application was not successful this cycle.</div>
    <?php else: ?>
      <div style="display:flex;gap:4px;">
        <?php foreach ($steps as $i => $step):
          $curIdx = array_search($app['status'], $steps, true);
          $reached = $curIdx !== false && $i <= $curIdx;
        ?>
          <div style="flex:1;text-align:center;">
            <div style="height:6px;border-radius:4px;background:<?= $reached ? 'var(--green)' : 'var(--gray-light)' ?>;margin-bottom:6px;"></div>
            <span class="text-xs <?= $reached ? 'fw-600' : 'text-subtle' ?>"><?= statusLabel($step) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <details style="margin-top:14px;">
      <summary class="text-sm fw-600" style="cursor:pointer;color:var(--primary);">View application details</summary>
      <div class="text-sm text-muted" style="margin-top:10px;">
        <p><strong>Motivation:</strong> <?= e($app['motivation']) ?></p>
        <p><strong>Skills:</strong> <?= e($app['skills_text']) ?></p>
        <?php if ($app['experience']): ?><p><strong>Experience:</strong> <?= e($app['experience']) ?></p><?php endif; ?>
      </div>
    </details>
  </div>
<?php endforeach; endif; ?>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
