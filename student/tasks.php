<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['student']);
$pdo = db();

$stmt = $pdo->prepare("SELECT t.*, c.name AS club_name, c.logo_color, e.title AS event_title
    FROM tasks t JOIN clubs c ON c.id = t.club_id LEFT JOIN events e ON e.id = t.event_id
    WHERE t.assigned_to = ? ORDER BY FIELD(t.priority,'high','medium','low'), t.deadline");
$stmt->execute([$user['id']]);
$tasks = $stmt->fetchAll();

$stages = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed'];
$byStage = array_fill_keys(array_keys($stages), []);
foreach ($tasks as $t) $byStage[$t['status']][] = $t;
$today = date('Y-m-d');

$pageTitle = 'My Tasks';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>My Tasks</h1><div class="sub">Tasks assigned to you across your clubs — drag a card or use the dropdown to update its status.</div></div>
</div>

<?php if (!$tasks): ?>
  <div class="empty-state"><div class="icon">✅</div><h4>No tasks assigned yet</h4><p>When a club executive assigns you a task, it will show up here.</p></div>
<?php else: ?>
<div class="kanban">
  <?php foreach ($stages as $key => $label): ?>
    <div class="kanban-col" data-status="<?= $key ?>">
      <div class="kanban-col-head"><span><?= e($label) ?></span><span class="badge badge-gray"><?= count($byStage[$key]) ?></span></div>
      <?php foreach ($byStage[$key] as $t): $overdue = $t['deadline'] && $t['deadline'] < $today && $t['status'] !== 'completed'; ?>
        <div class="kanban-card task-card" draggable="true" data-task-id="<?= $t['id'] ?>">
          <div class="flex-between"><strong class="text-sm"><?= e($t['title']) ?></strong><span class="badge <?= badgeClass($t['priority']) ?>"><?= statusLabel($t['priority']) ?></span></div>
          <div class="task-meta">
            <span class="badge badge-gray" style="background:<?= e($t['logo_color']) ?>22;color:<?= e($t['logo_color']) ?>;"><?= e($t['club_name']) ?></span>
          </div>
          <?php if ($t['event_title']): ?><p class="text-xs text-subtle" style="margin:6px 0 0;"><?= icon('calendar','icon') ?> <?= e($t['event_title']) ?></p><?php endif; ?>
          <?php if ($t['description']): ?><p class="text-xs text-muted" style="margin:6px 0 0;"><?= e($t['description']) ?></p><?php endif; ?>
          <?php if ($t['deadline']): ?><div class="text-xs <?= $overdue ? 'overdue-flag' : 'text-subtle' ?>" style="margin-top:6px;"><?= icon('clock','icon') ?> <?= $overdue?'Overdue: ':'Due ' ?><?= date('M j', strtotime($t['deadline'])) ?></div><?php endif; ?>
          <select class="input" style="margin-top:8px;padding:6px 8px;font-size:12.5px;" onchange="setTaskStatus(<?= $t['id'] ?>, this.value)">
            <?php foreach ($stages as $sk => $sl): ?>
              <option value="<?= $sk ?>" <?= $t['status']===$sk?'selected':'' ?>><?= e($sl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endforeach; ?>
      <?php if (!$byStage[$key]): ?><p class="text-xs text-subtle" style="text-align:center;padding:10px;">No tasks</p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
async function setTaskStatus(taskId, status) {
  const fd = new FormData();
  fd.append('task_id', taskId);
  fd.append('status', status);
  const json = await postJSON('<?= BASE_URL ?>api/update_task_status.php', fd);
  if (json.ok) { showToast('success', json.message); setTimeout(() => location.reload(), 500); }
  else showToast('error', json.message);
}
document.querySelectorAll('.kanban-card').forEach(card => {
  card.addEventListener('dragstart', e => { card.classList.add('dragging'); e.dataTransfer.setData('text/plain', card.dataset.taskId); });
  card.addEventListener('dragend', () => card.classList.remove('dragging'));
});
document.querySelectorAll('.kanban-col').forEach(col => {
  col.addEventListener('dragover', e => { e.preventDefault(); col.classList.add('drag-over'); });
  col.addEventListener('dragleave', () => col.classList.remove('drag-over'));
  col.addEventListener('drop', e => {
    e.preventDefault();
    col.classList.remove('drag-over');
    const taskId = e.dataTransfer.getData('text/plain');
    setTaskStatus(taskId, col.dataset.status);
  });
});
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
