<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['admin']);
$pdo = db();

$stmt = $pdo->query("SELECT v.*, (SELECT COUNT(*) FROM events e WHERE e.venue_id=v.id AND e.status IN ('approved','submitted') AND e.event_date >= CURDATE()) AS upcoming_bookings FROM venues v ORDER BY v.name");
$venues = $stmt->fetchAll();

$stmt = $pdo->query("SELECT e1.id a_id, e1.title a_title, e1.event_date, e1.start_time, e1.end_time, v.name venue, c1.name club1, e2.title b_title, c2.name club2
  FROM events e1 JOIN events e2 ON e1.venue_id = e2.venue_id AND e1.event_date = e2.event_date AND e1.id < e2.id
    AND NOT (e1.end_time <= e2.start_time OR e1.start_time >= e2.end_time)
  LEFT JOIN venues v ON v.id = e1.venue_id
  LEFT JOIN clubs c1 ON c1.id = e1.club_id LEFT JOIN clubs c2 ON c2.id = e2.club_id
  WHERE e1.status IN ('submitted','approved') AND e2.status IN ('submitted','approved')");
$conflicts = $stmt->fetchAll();

$pageTitle = 'Venues';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Venues</h1><div class="sub">Monitor campus venue availability and utilization.</div></div>
  <button class="btn btn-primary" data-open-modal="newVenueModal"><?= icon('plus','icon') ?> Add Venue</button>
</div>

<?php if ($conflicts): ?>
<div class="card" style="margin-bottom:22px;background:var(--red-light);border-color:#fecaca;">
  <div class="flex-center-gap" style="margin-bottom:8px;"><?= icon('alert-triangle','icon') ?><strong style="color:var(--red-dark);">Active venue conflicts</strong></div>
  <?php foreach ($conflicts as $c): ?>
    <p class="text-sm" style="margin:4px 0;">⚠ <strong><?= e($c['venue']) ?></strong> is double-booked on <?= date('M j, Y', strtotime($c['event_date'])) ?>: "<?= e($c['a_title']) ?>" (<?= e($c['club1']) ?>) from <?= date('g:i A', strtotime($c['start_time'])) ?>–<?= date('g:i A', strtotime($c['end_time'])) ?> overlaps "<?= e($c['b_title']) ?>" (<?= e($c['club2']) ?>).</p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grid grid-3">
  <?php foreach ($venues as $v): ?>
    <div class="card">
      <div class="flex-between" style="margin-bottom:8px;">
        <h3 class="text-lg"><?= e($v['name']) ?></h3>
        <span class="badge <?= $v['status']==='available'?'badge-green':'badge-amber' ?>"><?= statusLabel($v['status']) ?></span>
      </div>
      <p class="text-sm text-muted"><?= icon('map-pin','icon') ?> <?= e($v['location']) ?></p>
      <p class="text-sm text-muted">Capacity: <?= $v['capacity'] ?></p>
      <p class="text-sm text-muted"><?= $v['upcoming_bookings'] ?> upcoming bookings</p>
      <button class="btn btn-sm btn-outline btn-block" style="margin-top:10px;" data-ajax-post="<?= BASE_URL ?>api/venue_action.php" data-ajax-body='{"venue_id":<?= $v['id'] ?>,"action":"toggle"}'>
        Mark as <?= $v['status']==='available'?'Under Maintenance':'Available' ?>
      </button>
    </div>
  <?php endforeach; ?>
</div>

<div class="modal-backdrop" id="newVenueModal">
  <div class="modal">
    <div class="modal-head"><h3>Add Venue</h3><button class="modal-close" data-close-modal>&times;</button></div>
    <form class="ajax-form" action="<?= BASE_URL ?>api/venue_action.php" method="post">
      <div class="modal-body">
        <input type="hidden" name="action" value="create">
        <div class="field"><label>Name *</label><input class="input" name="name" required></div>
        <div class="form-row">
          <div class="field"><label>Capacity</label><input class="input" type="number" name="capacity" value="100"></div>
          <div class="field"><label>Location</label><input class="input" name="location"></div>
        </div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Add Venue</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
