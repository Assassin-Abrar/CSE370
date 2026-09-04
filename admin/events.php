<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['admin']);
$pdo = db();

$stmt = $pdo->query("SELECT e.*, c.name AS club_name, v.name AS venue_name FROM events e LEFT JOIN clubs c ON c.id=e.club_id LEFT JOIN venues v ON v.id=e.venue_id ORDER BY e.event_date DESC");
$events = $stmt->fetchAll();
$pending = array_filter($events, fn($e) => $e['status'] === 'submitted');

function hasConflictFor($ev, $all) {
    foreach ($all as $o) {
        if ($o['id'] == $ev['id']) continue;
        if ($o['status'] === 'rejected' || $o['status'] === 'cancelled') continue;
        if ($ev['venue_id'] && $o['venue_id'] == $ev['venue_id'] && $o['event_date'] === $ev['event_date']) {
            if (!($o['end_time'] <= $ev['start_time'] || $o['start_time'] >= $ev['end_time'])) return true;
        }
    }
    return false;
}

$pageTitle = 'Events';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>Event Approvals</h1><div class="sub">Review, approve, or reject event proposals.</div></div></div>

<div class="tabs" data-tabs="#evAdminTabs">
  <button class="active" data-tab="pending">Pending (<?= count($pending) ?>)</button>
  <button data-tab="all">All Events</button>
</div>
<div id="evAdminTabs">
  <div data-tab-panel="pending">
    <?php if (!$pending): ?><div class="empty-state"><div class="icon">✅</div><h4>No pending proposals</h4></div>
    <?php else: foreach ($pending as $ev): $conflict = hasConflictFor($ev, $events); ?>
      <div class="card" style="margin-bottom:14px;">
        <div class="flex-between">
          <div>
            <strong><?= e($ev['title']) ?></strong> <span class="badge badge-gray"><?= e($ev['club_name'] ?? 'OCA') ?></span>
            <?php if ($conflict): ?><span class="badge badge-red">⚠ Conflict</span><?php endif; ?>
            <p class="text-sm text-muted" style="margin:6px 0;"><?= e($ev['description']) ?></p>
            <span class="text-xs text-subtle"><?= icon('calendar','icon') ?> <?= date('M j, Y', strtotime($ev['event_date'])) ?>, <?= date('g:i A', strtotime($ev['start_time'])) ?>–<?= date('g:i A', strtotime($ev['end_time'])) ?> · <?= icon('map-pin','icon') ?> <?= e($ev['venue_name'] ?? 'TBA') ?> · Budget <?= moneyBDT($ev['budget_amount']) ?></span>
          </div>
          <div style="display:flex;gap:8px;flex-shrink:0;">
            <button class="btn btn-sm btn-success" data-ajax-post="<?= BASE_URL ?>api/event_status.php" data-ajax-body='{"event_id":<?= $ev['id'] ?>,"action":"approve"}' data-confirm="Approve &quot;<?= e($ev['title']) ?>&quot;?" data-confirm-class="btn-success">Approve</button>
            <button class="btn btn-sm btn-danger" data-open-modal="rejectModal-<?= $ev['id'] ?>">Reject</button>
          </div>
        </div>
      </div>
      <div class="modal-backdrop" id="rejectModal-<?= $ev['id'] ?>">
        <div class="modal">
          <div class="modal-head"><h3>Reject "<?= e($ev['title']) ?>"</h3><button class="modal-close" data-close-modal>&times;</button></div>
          <form class="ajax-form" action="<?= BASE_URL ?>api/event_status.php" method="post">
            <div class="modal-body">
              <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <div class="field"><label>Reason for rejection</label><textarea class="input" name="comment" rows="3" required></textarea></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-danger">Reject Event</button></div>
          </form>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <div data-tab-panel="all" style="display:none;">
    <div class="card" style="padding:0;">
      <div class="overflow-x">
      <table class="table responsive-cards">
        <thead><tr><th>Event</th><th>Club</th><th>Date</th><th>Venue</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($events as $ev): ?>
          <tr>
            <td data-label="Event"><?= e($ev['title']) ?></td>
            <td data-label="Club"><?= e($ev['club_name'] ?? 'OCA') ?></td>
            <td data-label="Date"><?= date('M j, Y', strtotime($ev['event_date'])) ?></td>
            <td data-label="Venue"><?= e($ev['venue_name'] ?? 'TBA') ?></td>
            <td data-label="Status">
              <span class="badge <?= badgeClass($ev['status']) ?>"><?= statusLabel($ev['status']) ?></span>
              <?php if ($ev['status']==='approved' && $ev['event_date'] < date('Y-m-d')): ?>
                <button class="btn-link" style="margin-left:6px;" data-ajax-post="<?= BASE_URL ?>api/event_status.php" data-ajax-body='{"event_id":<?= $ev['id'] ?>,"action":"complete"}'>Mark completed</button>
              <?php endif; ?>
              <?php if (in_array($ev['status'], ['submitted','approved'], true)): ?>
                <button class="btn-link" style="margin-left:6px;color:var(--red);" data-confirm="Cancel this event?" data-ajax-post="<?= BASE_URL ?>api/event_status.php" data-ajax-body='{"event_id":<?= $ev['id'] ?>,"action":"cancel"}'>Cancel</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
