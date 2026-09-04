<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;">Not linked to a club.</p>'); }
$pdo = db();

$stmt = $pdo->prepare("SELECT t.*, u.name AS assignee_name, u.avatar_color FROM tasks t LEFT JOIN users u ON u.id=t.assigned_to WHERE t.club_id = ? ORDER BY FIELD(t.priority,'high','medium','low'), t.deadline");
$stmt->execute([$club['id']]);
$tasks = $stmt->fetchAll();

$members = clubMembersWithWorkload($club['id']);
$stmt = $pdo->prepare('SELECT id, title FROM events WHERE club_id = ? ORDER BY event_date DESC LIMIT 20');
$stmt->execute([$club['id']]);
$clubEvents = $stmt->fetchAll();

$stages = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed'];
$byStage = array_fill_keys(array_keys($stages), []);
foreach ($tasks as $t) $byStage[$t['status']][] = $t;
$today = date('Y-m-d');

$pageTitle = 'Tasks';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Task Delegation</h1><div class="sub">Assign, track, and rebalance event-day tasks.</div></div>
  <button class="btn btn-primary" data-open-modal="newTaskModal"><?= icon('plus','icon') ?> New Task</button>
</div>

<div class="kanban">
  <?php foreach ($stages as $key => $label): ?>
    <div class="kanban-col" data-status="<?= $key ?>">
      <div class="kanban-col-head"><span><?= e($label) ?></span><span class="badge badge-gray"><?= count($byStage[$key]) ?></span></div>
      <?php foreach ($byStage[$key] as $t): $overdue = $t['deadline'] && $t['deadline'] < $today && $t['status'] !== 'completed'; ?>
        <div class="kanban-card task-card" draggable="true" data-task-id="<?= $t['id'] ?>">
          <div class="flex-between"><strong class="text-sm"><?= e($t['title']) ?></strong><span class="badge <?= badgeClass($t['priority']) ?>"><?= statusLabel($t['priority']) ?></span></div>
          <div class="task-meta">
            <?php if ($t['assignee_name']): ?><span class="avatar" style="width:22px;height:22px;font-size:10px;background:<?= e($t['avatar_color']) ?>;"><?= e(initials($t['assignee_name'])) ?></span><span class="text-xs text-muted"><?= e($t['assignee_name']) ?></span><?php else: ?><span class="text-xs text-subtle">Unassigned</span><?php endif; ?>
          </div>
          <?php if ($t['deadline']): ?><div class="text-xs <?= $overdue ? 'overdue-flag' : 'text-subtle' ?>" style="margin-top:6px;"><?= icon('clock','icon') ?> <?= $overdue?'Overdue: ':'Due ' ?><?= date('M j', strtotime($t['deadline'])) ?></div><?php endif; ?>
          <div style="display:flex;gap:6px;margin-top:8px;">
            <button class="btn btn-sm btn-outline" data-open-modal="reassignModal-<?= $t['id'] ?>">Reassign</button>
          </div>
        </div>
        <div class="modal-backdrop" id="reassignModal-<?= $t['id'] ?>">
          <div class="modal">
            <div class="modal-head"><h3>Reassign "<?= e($t['title']) ?>"</h3><button class="modal-close" data-close-modal>&times;</button></div>
            <div class="modal-body">
              <p class="text-sm text-muted" style="margin-bottom:10px;">Current workload per member:</p>
              <?php foreach ($members as $m): $wl=$m['workload']; ?>
                <div class="workload-row" style="padding:8px 0;">
                  <span class="avatar" style="width:28px;height:28px;font-size:11px;background:<?= e($m['avatar_color']) ?>;"><?= e(initials($m['name'])) ?></span>
                  <div class="info"><strong class="text-sm"><?= e($m['name']) ?></strong><div class="progress <?= $wl['status']==='Overloaded'?'red':($wl['status']==='Busy'?'amber':'green') ?>" style="margin-top:4px;"><span style="width:<?= $wl['percent'] ?>%"></span></div></div>
                  <span class="pct" style="width:auto;font-size:12px;"><?= $wl['percent'] ?>%</span>
                  <button class="btn btn-sm btn-primary" onclick="reassignTask(<?= $t['id'] ?>, <?= $m['id'] ?>)">Assign</button>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$byStage[$key]): ?><p class="text-xs text-subtle" style="text-align:center;padding:10px;">No tasks</p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<div class="modal-backdrop" id="newTaskModal">
  <div class="modal">
    <div class="modal-head"><h3>New Task</h3><button class="modal-close" data-close-modal>&times;</button></div>
    <form class="ajax-form" action="<?= BASE_URL ?>api/create_task.php" method="post">
      <div class="modal-body">
        <div class="field"><label>Title *</label><input class="input" name="title" required placeholder="Design event poster"></div>
        <div class="field"><label>Description</label><textarea class="input" name="description" rows="2"></textarea></div>
        <div class="field"><label>Assign to</label>
          <select class="input" name="assigned_to">
            <option value="">Unassigned</option>
            <?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['name']) ?> (<?= $m['workload']['percent'] ?>% workload<?= $m['workload']['status']==='Overloaded' ? ' — Overloaded' : '' ?>)</option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="field"><label>Priority</label><select class="input" name="priority"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
          <div class="field"><label>Deadline</label><input class="input" type="date" name="deadline"></div>
        </div>
        <div class="form-row">
          <div class="field"><label>Related event</label><select class="input" name="event_id"><option value="">— None —</option><?php foreach ($clubEvents as $ce): ?><option value="<?= $ce['id'] ?>"><?= e($ce['title']) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label>Estimated workload (1-10)</label><input class="input" type="number" name="estimated_workload" min="1" max="10" value="3"></div>
        </div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Create Task</button></div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.kanban-card').forEach(card => {
  card.addEventListener('dragstart', e => { card.classList.add('dragging'); e.dataTransfer.setData('text/plain', card.dataset.taskId); });
  card.addEventListener('dragend', () => card.classList.remove('dragging'));
});
document.querySelectorAll('.kanban-col').forEach(col => {
  col.addEventListener('dragover', e => { e.preventDefault(); col.classList.add('drag-over'); });
  col.addEventListener('dragleave', () => col.classList.remove('drag-over'));
  col.addEventListener('drop', async e => {
    e.preventDefault();
    col.classList.remove('drag-over');
    const taskId = e.dataTransfer.getData('text/plain');
    const status = col.dataset.status;
    const fd = new FormData(); fd.append('task_id', taskId); fd.append('status', status);
    const json = await postJSON('<?= BASE_URL ?>api/update_task_status.php', fd);
    if (json.ok) { showToast('success', json.message); setTimeout(()=>location.reload(), 500); }
    else showToast('error', json.message);
  });
});
async function reassignTask(taskId, memberId, force) {
  const fd = new FormData();
  fd.append('task_id', taskId); fd.append('assigned_to', memberId);
  if (force) fd.append('force', '1');
  const json = await postJSON('<?= BASE_URL ?>api/reassign_task.php', fd);
  if (json.ok) { showToast('success', json.message); setTimeout(()=>location.reload(), 500); }
  else if (json.requiresConfirm) {
    const ok = await confirmAction(json.message, 'Assign Anyway');
    if (ok) reassignTask(taskId, memberId, true);
  } else showToast('error', json.message);
}
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
