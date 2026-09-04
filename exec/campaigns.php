<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;">Not linked to a club.</p>'); }
$pdo = db();

$stmt = $pdo->prepare("SELECT rc.*, (SELECT COUNT(*) FROM applications a WHERE a.campaign_id = rc.id) AS app_count FROM recruitment_campaigns rc WHERE rc.club_id = ? ORDER BY rc.created_at DESC");
$stmt->execute([$club['id']]);
$campaigns = $stmt->fetchAll();

$pageTitle = 'Recruitment Campaigns';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Recruitment Campaigns</h1><div class="sub">Publish a campaign to open recruitment for specific positions.</div></div>
  <button class="btn btn-primary" data-open-modal="newCampaignModal"><?= icon('plus','icon') ?> New Campaign</button>
</div>

<?php if (!$campaigns): ?>
  <div class="empty-state"><div class="icon">📣</div><h4>No campaigns yet</h4><p>Create one to start recruiting.</p></div>
<?php else: ?>
<div class="grid grid-3">
  <?php foreach ($campaigns as $c): ?>
    <div class="card">
      <div class="flex-between" style="margin-bottom:8px;"><span class="badge <?= $c['status']==='open'?'badge-green':'badge-gray' ?>"><?= statusLabel($c['status']) ?></span><span class="text-xs text-subtle">Deadline <?= date('M j', strtotime($c['deadline'])) ?></span></div>
      <h3 class="text-lg"><?= e($c['title']) ?></h3>
      <p class="text-sm text-muted"><?= e($c['description']) ?></p>
      <div class="flex-between" style="margin-top:12px;">
        <span class="text-xs text-muted"><?= $c['positions_needed'] ?> positions · <?= $c['app_count'] ?> applied</span>
        <button class="btn btn-sm btn-outline" data-ajax-post="<?= BASE_URL ?>api/toggle_campaign.php" data-ajax-body='{"campaign_id":<?= $c['id'] ?>}'><?= $c['status']==='open'?'Close':'Reopen' ?></button>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="modal-backdrop" id="newCampaignModal">
  <div class="modal">
    <div class="modal-head"><h3>New Recruitment Campaign</h3><button class="modal-close" data-close-modal>&times;</button></div>
    <form class="ajax-form" action="<?= BASE_URL ?>api/create_campaign.php" method="post">
      <div class="modal-body">
        <div class="field"><label>Title *</label><input class="input" name="title" required placeholder="Fall 2026 Developer Recruitment"></div>
        <div class="field"><label>Description</label><textarea class="input" name="description" rows="3"></textarea></div>
        <div class="form-row">
          <div class="field"><label>Positions needed</label><input class="input" type="number" name="positions_needed" min="1" value="3"></div>
          <div class="field"><label>Deadline *</label><input class="input" type="date" name="deadline" required min="<?= date('Y-m-d') ?>"></div>
        </div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Publish Campaign</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
