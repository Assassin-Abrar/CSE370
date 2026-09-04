<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;">Not linked to a club.</p>'); }
$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM collaboration_requests WHERE club_id = ? ORDER BY created_at DESC");
$stmt->execute([$club['id']]);
$myRequests = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT cr.*, c.name AS club_name, c.logo_color FROM collaboration_requests cr JOIN clubs c ON c.id=cr.club_id WHERE cr.club_id != ? ORDER BY cr.created_at DESC");
$stmt->execute([$club['id']]);
$otherRequests = $stmt->fetchAll();

$respStmt = $pdo->prepare("SELECT r.*, c.name AS club_name FROM collaboration_responses r JOIN clubs c ON c.id=r.responding_club_id WHERE r.collaboration_id = ? ORDER BY r.created_at DESC");
$typeIcons = ['equipment' => 'briefcase', 'human_resource' => 'users', 'co_host' => 'share', 'venue' => 'map-pin', 'media' => 'file-text', 'other' => 'grid'];

$pageTitle = 'Collaboration';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Inter-Club Collaboration</h1><div class="sub">Post requests and coordinate with other clubs.</div></div>
  <button class="btn btn-primary" data-open-modal="newCollabModal"><?= icon('plus','icon') ?> Post Request</button>
</div>

<div class="tabs" data-tabs="#collabTabs">
  <button class="active" data-tab="mine">My Requests (<?= count($myRequests) ?>)</button>
  <button data-tab="others">Browse &amp; Respond</button>
</div>
<div id="collabTabs">
  <div data-tab-panel="mine">
    <?php if (!$myRequests): ?><div class="empty-state"><div class="icon">🤝</div><h4>You haven't posted any requests</h4></div>
    <?php else: foreach ($myRequests as $r):
      $respStmt->execute([$r['id']]); $responses = $respStmt->fetchAll();
    ?>
      <div class="card" style="margin-bottom:16px;">
        <div class="flex-between">
          <div class="flex-center-gap"><div class="type-icon" style="background:var(--primary-light);color:var(--primary-dark);"><?= icon($typeIcons[$r['request_type']] ?? 'grid') ?></div><div><strong><?= e($r['title']) ?></strong><br><span class="text-xs text-muted"><?= statusLabel($r['request_type']) ?></span></div></div>
          <span class="badge <?= badgeClass($r['status']) ?>"><?= statusLabel($r['status']) ?></span>
        </div>
        <p class="text-sm text-muted" style="margin:10px 0;"><?= e($r['description']) ?></p>
        <?php if ($responses): ?>
          <strong class="text-sm">Responses (<?= count($responses) ?>)</strong>
          <?php foreach ($responses as $resp): ?>
            <div class="response-item">
              <div style="flex:1;"><strong class="text-sm"><?= e($resp['club_name']) ?></strong><p class="text-sm text-muted mt-0"><?= e($resp['message']) ?></p></div>
              <?php if ($resp['status'] === 'accepted'): ?>
                <span class="badge badge-green">Accepted</span>
              <?php elseif (!in_array($r['status'], ['accepted','completed'], true)): ?>
                <button class="btn btn-sm btn-success" data-ajax-post="<?= BASE_URL ?>api/update_collab_status.php" data-ajax-body='{"collaboration_id":<?= $r['id'] ?>,"status":"accepted","response_id":<?= $resp['id'] ?>}'>Accept</button>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-xs text-subtle">No responses yet.</p>
        <?php endif; ?>
        <?php if ($r['status'] === 'accepted'): ?>
          <button class="btn btn-sm btn-outline" style="margin-top:10px;" data-ajax-post="<?= BASE_URL ?>api/update_collab_status.php" data-ajax-body='{"collaboration_id":<?= $r['id'] ?>,"status":"completed"}'>Mark Completed</button>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <div data-tab-panel="others" style="display:none;">
    <div class="grid grid-3">
      <?php foreach ($otherRequests as $r): ?>
        <div class="card collab-card">
          <div class="flex-between" style="margin-bottom:10px;">
            <div class="type-icon" style="background:var(--primary-light);color:var(--primary-dark);"><?= icon($typeIcons[$r['request_type']] ?? 'grid') ?></div>
            <span class="badge <?= badgeClass($r['status']) ?>"><?= statusLabel($r['status']) ?></span>
          </div>
          <h3 class="text-lg"><?= e($r['title']) ?></h3>
          <p class="text-sm text-muted"><?= e($r['description']) ?></p>
          <p class="text-xs text-subtle" style="margin:8px 0;"><?= e($r['club_name']) ?> <?php if($r['required_date']): ?>· Needed by <?= date('M j', strtotime($r['required_date'])) ?><?php endif; ?></p>
          <?php if (!in_array($r['status'], ['accepted','completed'], true)): ?>
            <button class="btn btn-outline btn-block btn-sm" data-open-modal="respondModal-<?= $r['id'] ?>">Respond</button>
          <?php else: ?>
            <span class="badge badge-gray" style="width:100%;justify-content:center;padding:6px;">Closed</span>
          <?php endif; ?>
        </div>
        <div class="modal-backdrop" id="respondModal-<?= $r['id'] ?>">
          <div class="modal">
            <div class="modal-head"><h3>Respond to "<?= e($r['title']) ?>"</h3><button class="modal-close" data-close-modal>&times;</button></div>
            <form class="ajax-form" action="<?= BASE_URL ?>api/respond_collab.php" method="post">
              <div class="modal-body">
                <input type="hidden" name="collaboration_id" value="<?= $r['id'] ?>">
                <div class="field"><label>Your offer / message *</label><textarea class="input" name="message" rows="3" required placeholder="e.g. We can lend our DSLR camera for the event."></textarea></div>
              </div>
              <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Send Response</button></div>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (!$otherRequests): ?><div class="empty-state"><div class="icon">📭</div><h4>No requests from other clubs</h4></div><?php endif; ?>
  </div>
</div>

<div class="modal-backdrop" id="newCollabModal">
  <div class="modal">
    <div class="modal-head"><h3>Post Collaboration Request</h3><button class="modal-close" data-close-modal>&times;</button></div>
    <form class="ajax-form" action="<?= BASE_URL ?>api/create_collab.php" method="post">
      <div class="modal-body">
        <div class="field"><label>Request type *</label>
          <select class="input" name="request_type" required>
            <option value="equipment">Equipment</option>
            <option value="human_resource">Human Resource</option>
            <option value="co_host">Co-host</option>
            <option value="venue">Venue/Resource</option>
            <option value="media">Media</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="field"><label>Title *</label><input class="input" name="title" required placeholder="Need DSLR camera for coverage"></div>
        <div class="field"><label>Description</label><textarea class="input" name="description" rows="3"></textarea></div>
        <div class="form-row">
          <div class="field"><label>Required by</label><input class="input" type="date" name="required_date"></div>
          <div class="field"><label>Priority</label><select class="input" name="priority"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
        </div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Post to Board</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
