<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;">Not linked to a club.</p>'); }
$pdo = db();

$stmt = $pdo->prepare("SELECT e.*, v.name AS venue_name FROM events e LEFT JOIN venues v ON v.id=e.venue_id WHERE e.club_id = ? ORDER BY e.event_date DESC");
$stmt->execute([$club['id']]);
$events = $stmt->fetchAll();

$venues = $pdo->query('SELECT * FROM venues WHERE status="available" ORDER BY name')->fetchAll();

// Calendar month
$month = $_GET['month'] ?? date('Y-m');
$monthStart = new DateTime($month . '-01');
$monthEnd = (clone $monthStart)->modify('last day of this month');
$gridStart = (clone $monthStart)->modify('monday this week');
$gridEnd = (clone $monthEnd)->modify('sunday this week');
$stmt = $pdo->prepare("SELECT e.*, v.name AS venue_name FROM events e LEFT JOIN venues v ON v.id=e.venue_id WHERE e.event_date BETWEEN ? AND ? AND e.status IN ('approved','submitted') ORDER BY e.start_time");
$stmt->execute([$gridStart->format('Y-m-d'), $gridEnd->format('Y-m-d')]);
$calEvents = $stmt->fetchAll();
$eventsByDate = [];
foreach ($calEvents as $ev) $eventsByDate[$ev['event_date']][] = $ev;
$prevMonth = (clone $monthStart)->modify('-1 month')->format('Y-m');
$nextMonth = (clone $monthStart)->modify('+1 month')->format('Y-m');

$pageTitle = 'Events';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Events</h1><div class="sub">Propose events and monitor venue availability.</div></div>
  <button class="btn btn-primary" data-open-modal="newEventModal"><?= icon('plus','icon') ?> Propose Event</button>
</div>

<div class="tabs" data-tabs="#evTabs">
  <button class="active" data-tab="list">List</button>
  <button data-tab="calendar">Calendar</button>
</div>
<div id="evTabs">
  <div data-tab-panel="list">
    <?php if (!$events): ?><div class="empty-state"><div class="icon">📅</div><h4>No events yet</h4></div>
    <?php else: ?>
    <div class="card" style="padding:0;">
      <div class="overflow-x">
      <table class="table responsive-cards">
        <thead><tr><th>Event</th><th>Date</th><th>Venue</th><th>Budget</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($events as $ev): ?>
          <tr>
            <td data-label="Event"><strong><?= e($ev['title']) ?></strong></td>
            <td data-label="Date"><?= date('M j, Y', strtotime($ev['event_date'])) ?>, <?= date('g:i A', strtotime($ev['start_time'])) ?>–<?= date('g:i A', strtotime($ev['end_time'])) ?></td>
            <td data-label="Venue"><?= e($ev['venue_name'] ?? 'TBA') ?></td>
            <td data-label="Budget"><?= moneyBDT($ev['budget_amount']) ?></td>
            <td data-label="Status"><span class="badge <?= badgeClass($ev['status']) ?>"><?= statusLabel($ev['status']) ?></span>
              <?php if ($ev['review_comment']): ?><br><span class="text-xs text-subtle"><?= e($ev['review_comment']) ?></span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div data-tab-panel="calendar" style="display:none;">
    <div class="card">
      <div class="cal-head">
        <a href="?month=<?= $prevMonth ?>" class="btn btn-sm btn-outline">&larr; Prev</a>
        <strong><?= $monthStart->format('F Y') ?></strong>
        <a href="?month=<?= $nextMonth ?>" class="btn btn-sm btn-outline">Next &rarr;</a>
      </div>
      <div class="cal-grid">
        <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?><div class="cal-dow"><?= $d ?></div><?php endforeach; ?>
        <?php
        $cursor = clone $gridStart;
        while ($cursor <= $gridEnd):
          $inMonth = $cursor->format('m') === $monthStart->format('m');
          $isToday = $cursor->format('Y-m-d') === date('Y-m-d');
          $dayEvents = $eventsByDate[$cursor->format('Y-m-d')] ?? [];
        ?>
          <div class="cal-cell <?= !$inMonth?'other-month':'' ?> <?= $isToday?'today':'' ?>">
            <div class="daynum"><?= $cursor->format('j') ?></div>
            <?php foreach (array_slice($dayEvents,0,3) as $ev): $mine = (int)$ev['club_id'] === (int)$club['id']; ?>
              <div class="cal-evt" title="<?= e($ev['title'] . ' — ' . substr($ev['start_time'],0,5) . '-' . substr($ev['end_time'],0,5) . ' @ ' . ($ev['venue_name']??'TBA')) ?>" style="background:<?= $mine?'var(--primary-light)':'var(--gray-light)' ?>;color:<?= $mine?'var(--primary-dark)':'var(--text-muted)' ?>;"><?= e(mb_substr($ev['title'],0,16)) ?></div>
            <?php endforeach; ?>
          </div>
        <?php $cursor->modify('+1 day'); endwhile; ?>
      </div>
      <p class="text-xs text-subtle" style="margin-top:12px;"><span style="display:inline-block;width:10px;height:10px;background:var(--primary-light);border-radius:3px;"></span> Your club &nbsp; <span style="display:inline-block;width:10px;height:10px;background:var(--gray-light);border-radius:3px;"></span> Other clubs / university</p>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="newEventModal">
  <div class="modal modal-lg">
    <div class="modal-head"><h3>Propose New Event</h3><button class="modal-close" data-close-modal>&times;</button></div>
    <form class="ajax-form" id="eventForm" action="<?= BASE_URL ?>api/create_event.php" method="post">
      <div class="modal-body">
        <div class="field"><label>Event title *</label><input class="input" name="title" required></div>
        <div class="field"><label>Description</label><textarea class="input" name="description" rows="2"></textarea></div>
        <div class="form-row">
          <div class="field"><label>Category</label><input class="input" name="category" placeholder="Technology"></div>
          <div class="field"><label>Expected attendance</label><input class="input" type="number" name="expected_attendance" min="0"></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Date *</label><input class="input" type="date" id="ev_date" name="event_date" required min="<?= date('Y-m-d') ?>"></div>
          <div class="field"><label>Venue</label>
            <select class="input" id="ev_venue" name="venue_id">
              <option value="">Select venue</option>
              <?php foreach ($venues as $v): ?><option value="<?= $v['id'] ?>"><?= e($v['name']) ?> (cap. <?= $v['capacity'] ?>)</option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="field"><label>Start time *</label><input class="input" type="time" id="ev_start" name="start_time" required></div>
          <div class="field"><label>End time *</label><input class="input" type="time" id="ev_end" name="end_time" required></div>
        </div>
        <div class="field"><label>Required equipment</label><input class="input" name="required_equipment" placeholder="Projector, mics..."></div>
        <div class="field"><label>Estimated budget (৳)</label><input class="input" type="number" name="budget_amount" min="0"></div>
        <div id="conflictBox"></div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Submit Proposal</button></div>
    </form>
  </div>
</div>

<script>
(function(){
  const dateEl = document.getElementById('ev_date'), venueEl = document.getElementById('ev_venue'),
        startEl = document.getElementById('ev_start'), endEl = document.getElementById('ev_end'),
        box = document.getElementById('conflictBox');
  async function checkConflict() {
    if (!dateEl.value || !startEl.value || !endEl.value) { box.innerHTML=''; return; }
    const fd = new FormData();
    fd.append('venue_id', venueEl.value);
    fd.append('event_date', dateEl.value);
    fd.append('start_time', startEl.value);
    fd.append('end_time', endEl.value);
    const json = await postJSON('<?= BASE_URL ?>api/check_conflict.php', fd);
    if (!json.ok) { box.innerHTML=''; return; }
    if (!json.hasConflict) { box.innerHTML = '<div class="badge badge-green" style="padding:8px 12px;margin-top:10px;">✓ No scheduling conflicts detected</div>'; return; }
    let html = '<div style="margin-top:12px;padding:12px 14px;background:var(--red-light);border-radius:10px;border:1px solid #fecaca;">';
    json.venueConflicts.forEach(c => {
      html += `<div class="text-sm fw-600" style="color:var(--red-dark);">⚠ Venue Conflict Detected</div><p class="text-sm" style="margin:4px 0;">"${c.venue}" is already reserved by ${c.club} (${c.title}) from ${c.start}–${c.end}.</p>`;
    });
    json.majorConflicts.forEach(c => {
      html += `<div class="text-sm fw-600" style="color:var(--red-dark);">⚠ Major Event Conflict</div><p class="text-sm" style="margin:4px 0;">A university-wide event ("${c.title}") is scheduled on this date. Consider another time.</p>`;
    });
    if (json.suggestions && json.suggestions.length) {
      html += '<p class="text-sm fw-600" style="margin-top:6px;">Available venues at this time:</p><div class="skills-row">' + json.suggestions.map(s=>`<span class="skill-pill">${s.name}</span>`).join('') + '</div>';
    }
    html += '</div>';
    box.innerHTML = html;
  }
  [dateEl, venueEl, startEl, endEl].forEach(el => el.addEventListener('change', checkConflict));
})();
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
