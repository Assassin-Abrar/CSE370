<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;">Not linked to a club.</p>'); }
$pdo = db();

$q = trim($_GET['q'] ?? '');
$sql = "SELECT a.*, u.name AS applicant_name, u.email, u.avatar_color FROM applications a JOIN users u ON u.id=a.user_id WHERE a.club_id = ?";
$params = [$club['id']];
if ($q !== '') { $sql .= " AND u.name LIKE ?"; $params[] = "%$q%"; }
$sql .= " ORDER BY a.applied_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apps = $stmt->fetchAll();

$stages = ['submitted' => 'Submitted', 'under_review' => 'Under Review', 'shortlisted' => 'Shortlisted', 'interview' => 'Interview', 'accepted' => 'Accepted', 'rejected' => 'Rejected'];
$byStage = array_fill_keys(array_keys($stages), []);
foreach ($apps as $a) $byStage[$a['status']][] = $a;

$notesStmt = $pdo->prepare('SELECT n.*, u.name AS author FROM application_notes n JOIN users u ON u.id=n.author_user_id WHERE n.application_id = ? ORDER BY n.created_at DESC');

$pageTitle = 'Recruitment';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Recruitment Pipeline</h1><div class="sub">Drag applicants between stages or use the buttons on each card.</div></div>
  <form method="get"><input class="input" type="search" name="q" placeholder="Search applicants..." value="<?= e($q) ?>"></form>
</div>

<div class="kanban">
  <?php foreach ($stages as $key => $label): ?>
    <div class="kanban-col" data-status="<?= $key ?>">
      <div class="kanban-col-head"><span><?= e($label) ?></span><span class="badge badge-gray"><?= count($byStage[$key]) ?></span></div>
      <?php foreach ($byStage[$key] as $a): ?>
        <div class="kanban-card applicant-card" draggable="true" data-app-id="<?= $a['id'] ?>" data-open-modal="appModal-<?= $a['id'] ?>">
          <div class="name-row">
            <span class="avatar" style="background:<?= e($a['avatar_color']) ?>;"><?= e(initials($a['applicant_name'])) ?></span>
            <strong class="text-sm"><?= e($a['applicant_name']) ?></strong>
          </div>
          <p class="text-xs text-muted" style="margin:0 0 6px;"><?= e(mb_substr($a['skills_text'],0,50)) ?></p>
          <span class="text-xs text-subtle"><?= timeAgo($a['applied_at']) ?></span>
        </div>
      <?php endforeach; ?>
      <?php if (!$byStage[$key]): ?><p class="text-xs text-subtle" style="text-align:center;padding:10px;">No applicants</p><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<?php foreach ($apps as $a):
  $notesStmt->execute([$a['id']]);
  $notes = $notesStmt->fetchAll();
?>
<div class="modal-backdrop" id="appModal-<?= $a['id'] ?>">
  <div class="modal modal-lg">
    <div class="modal-head"><h3><?= e($a['applicant_name']) ?></h3><button class="modal-close" data-close-modal>&times;</button></div>
    <div class="modal-body">
      <div class="flex-between" style="margin-bottom:14px;">
        <span class="text-sm text-muted"><?= e($a['email']) ?></span>
        <span class="badge <?= badgeClass($a['status']) ?>"><?= statusLabel($a['status']) ?></span>
      </div>
      <p class="text-sm"><strong>Motivation:</strong> <?= e($a['motivation']) ?></p>
      <p class="text-sm"><strong>Skills:</strong> <?= e($a['skills_text']) ?></p>
      <?php if ($a['experience']): ?><p class="text-sm"><strong>Experience:</strong> <?= e($a['experience']) ?></p><?php endif; ?>
      <?php if ($a['portfolio_link']): ?><p class="text-sm"><strong>Portfolio:</strong> <a href="<?= e($a['portfolio_link']) ?>" target="_blank" style="color:var(--primary);"><?= e($a['portfolio_link']) ?></a></p><?php endif; ?>
      <?php if ($a['availability']): ?><p class="text-sm"><strong>Availability:</strong> <?= e($a['availability']) ?></p><?php endif; ?>
      <?php if ($a['cv_path']): ?><p class="text-sm"><strong>CV:</strong> <a href="<?= BASE_URL . e($a['cv_path']) ?>" target="_blank" style="color:var(--primary);">View file</a></p><?php endif; ?>

      <div class="divider"></div>
      <form class="ajax-form" action="<?= BASE_URL ?>api/update_application_status.php" method="post">
        <input type="hidden" name="application_id" value="<?= $a['id'] ?>">
        <div class="form-row">
          <div class="field">
            <label>Update status</label>
            <select class="input" name="status">
              <?php foreach ($stages as $k=>$l): ?><option value="<?= $k ?>" <?= $a['status']===$k?'selected':'' ?>><?= e($l) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label>Add internal note (optional)</label><input class="input" name="note" placeholder="e.g. Strong portfolio"></div>
        </div>
        <button class="btn btn-primary btn-sm" type="submit">Save</button>
      </form>

      <?php if ($notes): ?>
        <div class="divider"></div>
        <strong class="text-sm">Internal notes</strong>
        <?php foreach ($notes as $n): ?>
          <div style="padding:8px 0;border-bottom:1px solid var(--border);">
            <p class="text-sm mt-0" style="margin-bottom:2px;"><?= e($n['note']) ?></p>
            <span class="text-xs text-subtle"><?= e($n['author']) ?> · <?= timeAgo($n['created_at']) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script>
document.querySelectorAll('.kanban-card').forEach(card => {
  card.addEventListener('dragstart', e => { card.classList.add('dragging'); e.dataTransfer.setData('text/plain', card.dataset.appId); });
  card.addEventListener('dragend', () => card.classList.remove('dragging'));
});
document.querySelectorAll('.kanban-col').forEach(col => {
  col.addEventListener('dragover', e => { e.preventDefault(); col.classList.add('drag-over'); });
  col.addEventListener('dragleave', () => col.classList.remove('drag-over'));
  col.addEventListener('drop', async e => {
    e.preventDefault();
    col.classList.remove('drag-over');
    const appId = e.dataTransfer.getData('text/plain');
    const status = col.dataset.status;
    const fd = new FormData();
    fd.append('application_id', appId);
    fd.append('status', status);
    const json = await postJSON('<?= BASE_URL ?>api/update_application_status.php', fd);
    if (json.ok) { showToast('success', json.message); setTimeout(()=>location.reload(), 500); }
    else showToast('error', json.message);
  });
});
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
