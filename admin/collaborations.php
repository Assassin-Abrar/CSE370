<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['admin']);
$pdo = db();

$stmt = $pdo->query("SELECT cr.*, c.name AS club_name, (SELECT COUNT(*) FROM collaboration_responses r WHERE r.collaboration_id=cr.id) AS response_count FROM collaboration_requests cr JOIN clubs c ON c.id=cr.club_id ORDER BY cr.created_at DESC");
$requests = $stmt->fetchAll();

$statusCounts = ['open'=>0,'responses_received'=>0,'in_discussion'=>0,'accepted'=>0,'completed'=>0];
foreach ($requests as $r) $statusCounts[$r['status']] = ($statusCounts[$r['status']] ?? 0) + 1;

$pageTitle = 'Collaborations';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>Inter-Club Collaborations</h1><div class="sub">Campus-wide view of collaboration activity.</div></div></div>

<div class="grid grid-4" style="margin-bottom:22px;">
  <?php foreach ($statusCounts as $s=>$c): ?>
    <div class="card stat-tile"><div class="value"><?= $c ?></div><div class="label"><?= statusLabel($s) ?></div></div>
  <?php endforeach; ?>
</div>

<div class="card" style="padding:0;">
  <div class="overflow-x">
  <table class="table responsive-cards">
    <thead><tr><th>Request</th><th>Club</th><th>Type</th><th>Responses</th><th>Priority</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($requests as $r): ?>
      <tr>
        <td data-label="Request"><?= e($r['title']) ?></td>
        <td data-label="Club"><?= e($r['club_name']) ?></td>
        <td data-label="Type"><?= statusLabel($r['request_type']) ?></td>
        <td data-label="Responses"><?= $r['response_count'] ?></td>
        <td data-label="Priority"><span class="badge <?= badgeClass($r['priority']) ?>"><?= statusLabel($r['priority']) ?></span></td>
        <td data-label="Status"><span class="badge <?= badgeClass($r['status']) ?>"><?= statusLabel($r['status']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
