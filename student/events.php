<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['student']);
$pdo = db();

$stmt = $pdo->prepare("SELECT e.*, c.name AS club_name, c.logo_color, v.name AS venue_name,
    EXISTS(SELECT 1 FROM event_registrations r WHERE r.event_id=e.id AND r.user_id=?) AS registered
    FROM events e LEFT JOIN clubs c ON c.id=e.club_id LEFT JOIN venues v ON v.id=e.venue_id
    WHERE e.status IN ('approved','completed') ORDER BY e.event_date, e.start_time");
$stmt->execute([$user['id']]);
$allEvents = $stmt->fetchAll();
$upcoming = array_filter($allEvents, fn($e) => $e['event_date'] >= date('Y-m-d'));
$past = array_filter($allEvents, fn($e) => $e['event_date'] < date('Y-m-d'));

$stmt = $pdo->prepare("SELECT e.*, c.name AS club_name, v.name AS venue_name, r.attended FROM event_registrations r JOIN events e ON e.id=r.event_id LEFT JOIN clubs c ON c.id=e.club_id LEFT JOIN venues v ON v.id=e.venue_id WHERE r.user_id=? ORDER BY e.event_date DESC");
$stmt->execute([$user['id']]);
$myEvents = $stmt->fetchAll();

$pageTitle = 'Events';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>Events</h1><div class="sub">Browse and register for upcoming campus events.</div></div></div>

<div class="tabs" data-tabs="#evTabs">
  <button class="active" data-tab="upcoming">Upcoming (<?= count($upcoming) ?>)</button>
  <button data-tab="registered">My Registered (<?= count($myEvents) ?>)</button>
  <button data-tab="past">Past Events</button>
</div>
<div id="evTabs">
  <div data-tab-panel="upcoming">
    <?php if (!$upcoming): ?><div class="empty-state"><div class="icon">📅</div><h4>No upcoming events</h4></div><?php endif; ?>
    <div class="grid grid-3">
      <?php foreach ($upcoming as $ev): ?>
        <div class="card card-hover">
          <div class="flex-between" style="margin-bottom:10px;">
            <span class="badge badge-blue"><?= e($ev['category'] ?: 'General') ?></span>
            <?= $ev['is_major_event'] ? '<span class="badge badge-purple">University-wide</span>' : '' ?>
          </div>
          <h3 class="text-lg"><?= e($ev['title']) ?></h3>
          <p class="text-sm text-muted"><?= e(mb_substr($ev['description'],0,90)) ?>…</p>
          <div class="text-sm" style="margin:10px 0;">
            <div><?= icon('calendar','icon') ?> <?= date('D, M j, Y', strtotime($ev['event_date'])) ?>, <?= date('g:i A', strtotime($ev['start_time'])) ?>–<?= date('g:i A', strtotime($ev['end_time'])) ?></div>
            <div><?= icon('map-pin','icon') ?> <?= e($ev['venue_name'] ?? 'TBA') ?></div>
            <div><?= icon('users','icon') ?> <?= e($ev['club_name'] ?? 'OCA / University-wide') ?></div>
          </div>
          <?php if ($ev['registered']): ?>
            <span class="badge badge-green" style="padding:8px 14px;width:100%;justify-content:center;"><?= icon('check-circle','icon') ?> Registered</span>
          <?php else: ?>
            <button class="btn btn-primary btn-block" data-ajax-post="<?= BASE_URL ?>api/register_event.php" data-ajax-body='{"event_id":<?= $ev['id'] ?>}'>Register</button>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div data-tab-panel="registered" style="display:none;">
    <?php if (!$myEvents): ?><div class="empty-state"><div class="icon">🎟️</div><h4>No registrations yet</h4></div><?php else: ?>
    <div class="overflow-x">
    <table class="table responsive-cards">
      <thead><tr><th>Event</th><th>Club</th><th>Date</th><th>Venue</th><th>Attendance</th></tr></thead>
      <tbody>
      <?php foreach ($myEvents as $ev): ?>
        <tr>
          <td data-label="Event"><?= e($ev['title']) ?></td>
          <td data-label="Club"><?= e($ev['club_name'] ?? 'OCA') ?></td>
          <td data-label="Date"><?= date('M j, Y', strtotime($ev['event_date'])) ?></td>
          <td data-label="Venue"><?= e($ev['venue_name'] ?? 'TBA') ?></td>
          <td data-label="Attendance"><?= $ev['attended'] ? '<span class="badge badge-green">Attended</span>' : '<span class="badge badge-gray">Pending</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </div>
  <div data-tab-panel="past" style="display:none;">
    <?php if (!$past): ?><div class="empty-state"><div class="icon">🗓️</div><h4>No past events</h4></div><?php endif; ?>
    <div class="grid grid-3">
      <?php foreach ($past as $ev): ?>
        <div class="card">
          <span class="badge badge-gray">Completed</span>
          <h3 class="text-lg" style="margin-top:8px;"><?= e($ev['title']) ?></h3>
          <p class="text-sm text-muted"><?= date('M j, Y', strtotime($ev['event_date'])) ?> · <?= e($ev['club_name'] ?? 'OCA') ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
