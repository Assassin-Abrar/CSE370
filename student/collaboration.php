<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['student']);
$pdo = db();

$typeFilter = $_GET['type'] ?? '';
$sql = "SELECT cr.*, c.name AS club_name, c.logo_color FROM collaboration_requests cr JOIN clubs c ON c.id=cr.club_id";
$params = [];
if ($typeFilter) { $sql .= " WHERE cr.request_type = ?"; $params[] = $typeFilter; }
$sql .= " ORDER BY cr.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$typeIcons = ['equipment' => 'briefcase', 'human_resource' => 'users', 'co_host' => 'share', 'venue' => 'map-pin', 'media' => 'file-text', 'other' => 'grid'];
$typeLabels = ['equipment' => 'Equipment', 'human_resource' => 'Human Resource', 'co_host' => 'Co-host', 'venue' => 'Venue/Resource', 'media' => 'Media', 'other' => 'Other'];

$pageTitle = 'Collaboration Board';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>Inter-Club Collaboration</h1><div class="sub">See what clubs across campus are requesting and offering.</div></div></div>

<div class="filter-bar">
  <a href="collaboration.php" class="chip <?= !$typeFilter?'selected':'' ?>">All</a>
  <?php foreach ($typeLabels as $k=>$label): ?>
    <a href="?type=<?= $k ?>" class="chip <?= $typeFilter===$k?'selected':'' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$requests): ?>
  <div class="empty-state"><div class="icon">🤝</div><h4>No collaboration requests</h4></div>
<?php else: ?>
<div class="grid grid-3">
  <?php foreach ($requests as $r): ?>
    <div class="card collab-card">
      <div class="flex-between" style="margin-bottom:10px;">
        <div class="type-icon" style="background:var(--primary-light);color:var(--primary-dark);"><?= icon($typeIcons[$r['request_type']] ?? 'grid') ?></div>
        <span class="badge <?= badgeClass($r['status']) ?>"><?= statusLabel($r['status']) ?></span>
      </div>
      <h3 class="text-lg"><?= e($r['title']) ?></h3>
      <p class="text-sm text-muted"><?= e($r['description']) ?></p>
      <div class="flex-between" style="margin-top:12px;">
        <span class="text-xs text-muted"><?= e($r['club_name']) ?></span>
        <span class="badge <?= badgeClass($r['priority']) ?>"><?= statusLabel($r['priority']) ?> priority</span>
      </div>
      <?php if ($r['required_date']): ?><p class="text-xs text-subtle" style="margin-top:6px;">Needed by <?= date('M j, Y', strtotime($r['required_date'])) ?></p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
