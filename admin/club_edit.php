<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['admin']);
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM clubs WHERE id = ?');
$stmt->execute([$id]);
$club = $stmt->fetch();
if (!$club) { http_response_code(404); die('Club not found'); }

$members = clubMembersWithWorkload($id);
$stmt = $pdo->prepare("SELECT * FROM events WHERE club_id=? ORDER BY event_date DESC LIMIT 5");
$stmt->execute([$id]);
$events = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM budget_requests WHERE club_id=? ORDER BY submitted_at DESC LIMIT 5");
$stmt->execute([$id]);
$budgets = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE club_id=?");
$stmt->execute([$id]);
$appCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM collaboration_requests WHERE club_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$id]);
$collabs = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT a.*, u.name AS applicant_name, u.avatar_color FROM applications a JOIN users u ON u.id=a.user_id WHERE a.club_id=? ORDER BY a.applied_at DESC LIMIT 5");
$stmt->execute([$id]);
$recentApps = $stmt->fetchAll();

// Governing Body: 4 controlled positions, only assignable from existing active members
$gbTitles = ['President', 'Vice President', 'General Secretary', 'Treasurer'];
$gbHolders = [];
foreach ($members as $m) {
    if (in_array($m['position'], $gbTitles, true)) $gbHolders[$m['position']] = $m;
}

$stmt = $pdo->prepare("SELECT u.id, u.name, u.avatar_color, sp.student_id FROM club_members cm JOIN users u ON u.id=cm.user_id LEFT JOIN student_profiles sp ON sp.user_id=u.id WHERE cm.club_id=? AND cm.status='active' ORDER BY u.name");
$stmt->execute([$id]);
$searchableMembers = $stmt->fetchAll();

$pageTitle = $club['name'];
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><a href="clubs.php" class="text-sm text-muted">&larr; All Clubs</a><h1><?= e($club['name']) ?></h1></div>
  <span class="badge <?= $club['status']==='active'?'badge-green':'badge-red' ?>" style="padding:8px 16px;"><?= statusLabel($club['status']) ?></span>
</div>

<div class="grid grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">
  <div>
    <form class="card ajax-form" action="<?= BASE_URL ?>api/admin_update_club.php" method="post" style="margin-bottom:16px;">
      <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
      <div class="card-header"><span class="card-title">Club details</span></div>
      <div class="form-row">
        <div class="field"><label>Name</label><input class="input" name="name" value="<?= e($club['name']) ?>" required></div>
        <div class="field"><label>Category</label>
          <select class="input" name="category">
            <?php foreach (['Academic','Technology','Cultural','Sports','Social Service','Debate','Media','Entrepreneurship','Arts','Professional','Extra Curricular','Co-Curricular'] as $cat): ?><option value="<?= $cat ?>" <?= $club['category']===$cat?'selected':'' ?>><?= $cat ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field"><label>Description</label><textarea class="input" name="description" rows="3"><?= e($club['description']) ?></textarea></div>
      <div class="field"><label>Founded year</label><input class="input" type="number" name="founded_year" value="<?= e($club['founded_year']) ?>" style="max-width:140px;"></div>
      <button class="btn btn-primary btn-sm" type="submit">Save</button>
    </form>

    <div class="card" style="margin-bottom:16px;">
      <div class="card-header"><span class="card-title">Governing Body</span></div>
      <p class="text-sm text-muted" style="margin-top:-6px;margin-bottom:14px;">Assign the four GB positions from this club's existing members only.</p>
      <div class="grid grid-2" style="gap:12px;">
        <?php foreach ($gbTitles as $pos): $holder = $gbHolders[$pos] ?? null; ?>
          <div style="border:1px solid var(--border);border-radius:12px;padding:14px;display:flex;align-items:center;gap:12px;">
            <span class="avatar" style="width:38px;height:38px;flex-shrink:0;background:<?= $holder ? e($holder['avatar_color']) : 'var(--gray-light)' ?>;color:<?= $holder ? '#fff' : 'var(--text-subtle)' ?>;"><?= $holder ? e(initials($holder['name'])) : '?' ?></span>
            <div style="flex:1;min-width:0;">
              <div class="text-xs text-subtle fw-700" style="text-transform:uppercase;letter-spacing:.03em;"><?= e($pos) ?></div>
              <div class="text-sm fw-600" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= $holder ? e($holder['name']) : 'Vacant' ?></div>
            </div>
            <button type="button" class="btn btn-sm btn-outline" onclick="openGbModal('<?= e($pos, ENT_QUOTES) ?>')"><?= $holder ? 'Change' : 'Assign' ?></button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
      <div class="card-header"><span class="card-title">Executives &amp; Members (<?= count($members) ?>)</span></div>
      <div class="overflow-x">
      <table class="table responsive-cards">
        <thead><tr><th>Name</th><th>Position</th><th>Workload</th></tr></thead>
        <tbody>
        <?php foreach ($members as $m): ?>
          <tr>
            <td data-label="Name"><div class="flex-center-gap"><span class="avatar" style="width:26px;height:26px;font-size:10px;background:<?= e($m['avatar_color']) ?>;"><?= e(initials($m['name'])) ?></span><?= e($m['name']) ?></div></td>
            <td data-label="Position"><span class="badge <?= $m['member_role']==='president'?'badge-purple':($m['member_role']==='exec'?'badge-blue':'badge-gray') ?>"><?= e($m['position']) ?></span></td>
            <td data-label="Workload"><?= $m['workload']['percent'] ?>%</td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">Recent events</span></div>
      <?php foreach ($events as $ev): ?>
        <div class="list-item-hover flex-between" style="padding:8px;"><span class="text-sm"><?= e($ev['title']) ?></span><span class="badge <?= badgeClass($ev['status']) ?>"><?= statusLabel($ev['status']) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$events): ?><p class="text-sm text-muted">No events yet.</p><?php endif; ?>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:16px;">
      <h4 style="margin-bottom:10px;">Quick stats</h4>
      <div class="text-sm" style="display:flex;flex-direction:column;gap:8px;">
        <div class="flex-between"><span class="text-muted">Members</span><strong><?= count($members) ?></strong></div>
        <div class="flex-between"><span class="text-muted">Applications received</span><strong><?= $appCount ?></strong></div>
        <div class="flex-between"><span class="text-muted">Recruitment</span><strong><?= statusLabel($club['recruitment_status']) ?></strong></div>
      </div>
    </div>
    <div class="card" style="margin-bottom:16px;">
      <h4 style="margin-bottom:10px;">Recent budgets</h4>
      <?php foreach ($budgets as $b): ?>
        <div class="list-item-hover flex-between" style="padding:8px;"><span class="text-sm"><?= e($b['event_name']) ?></span><span class="badge <?= badgeClass($b['status']) ?>"><?= statusLabel($b['status']) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$budgets): ?><p class="text-sm text-muted">No budget requests yet.</p><?php endif; ?>
    </div>

    <div class="card" style="margin-bottom:16px;">
      <h4 style="margin-bottom:10px;">Recent collaboration requests</h4>
      <?php foreach ($collabs as $c): ?>
        <div class="list-item-hover flex-between" style="padding:8px;"><span class="text-sm"><?= e($c['title']) ?></span><span class="badge <?= badgeClass($c['status']) ?>"><?= statusLabel($c['status']) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$collabs): ?><p class="text-sm text-muted">No collaboration requests yet.</p><?php endif; ?>
    </div>

    <div class="card">
      <h4 style="margin-bottom:10px;">Recent applicants</h4>
      <?php foreach ($recentApps as $a): ?>
        <div class="list-item-hover flex-between" style="padding:8px;">
          <div class="flex-center-gap"><span class="avatar" style="width:24px;height:24px;font-size:10px;background:<?= e($a['avatar_color']) ?>;"><?= e(initials($a['applicant_name'])) ?></span><span class="text-sm"><?= e($a['applicant_name']) ?></span></div>
          <span class="badge <?= badgeClass($a['status']) ?>"><?= statusLabel($a['status']) ?></span>
        </div>
      <?php endforeach; ?>
      <?php if (!$recentApps): ?><p class="text-sm text-muted">No applications yet.</p><?php endif; ?>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="gbModal">
  <div class="modal">
    <div class="modal-head"><h3 id="gbModalTitle">Assign Position</h3><button class="modal-close" data-close-modal>&times;</button></div>
    <div class="modal-body">
      <div style="position:relative;margin-bottom:14px;">
        <span style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-subtle);"><?= icon('search') ?></span>
        <input class="input" type="search" id="gbSearchInput" placeholder="Search by name or student ID..." oninput="filterGbList()" autocomplete="off" style="padding-left:40px;">
      </div>
      <div id="gbMemberList" style="max-height:340px;overflow-y:auto;display:flex;flex-direction:column;gap:6px;">
        <?php foreach ($searchableMembers as $m): ?>
          <button type="button" class="gb-member-row" data-name="<?= e(mb_strtolower($m['name'])) ?>" data-sid="<?= e(mb_strtolower($m['student_id'] ?? '')) ?>" onclick="assignGb(<?= (int)$m['id'] ?>)">
            <span class="avatar" style="width:30px;height:30px;font-size:11px;background:<?= e($m['avatar_color']) ?>;"><?= e(initials($m['name'])) ?></span>
            <span style="flex:1;min-width:0;">
              <span class="text-sm fw-600" style="display:block;"><?= e($m['name']) ?></span>
              <?php if ($m['student_id']): ?><span class="text-xs text-subtle"><?= e($m['student_id']) ?></span><?php endif; ?>
            </span>
          </button>
        <?php endforeach; ?>
        <?php if (!$searchableMembers): ?><p class="text-sm text-muted" style="padding:10px;">No club members to assign yet.</p><?php endif; ?>
      </div>
      <p class="gb-no-results text-sm text-muted" style="display:none;padding:14px 4px 0;text-align:center;">No members match your search.</p>
    </div>
  </div>
</div>

<script>
let gbCurrentPosition = '';
function openGbModal(position) {
  gbCurrentPosition = position;
  document.getElementById('gbModalTitle').textContent = 'Assign ' + position;
  document.getElementById('gbSearchInput').value = '';
  filterGbList();
  openModal('gbModal');
  setTimeout(() => document.getElementById('gbSearchInput').focus(), 50);
}
function filterGbList() {
  const q = document.getElementById('gbSearchInput').value.trim().toLowerCase();
  let anyVisible = false;
  document.querySelectorAll('.gb-member-row').forEach(row => {
    const match = !q || row.dataset.name.includes(q) || row.dataset.sid.includes(q);
    row.style.display = match ? 'flex' : 'none';
    if (match) anyVisible = true;
  });
  const noResults = document.querySelector('.gb-no-results');
  if (noResults) noResults.style.display = anyVisible ? 'none' : 'block';
}
async function assignGb(userId) {
  const fd = new FormData();
  fd.append('club_id', <?= (int)$club['id'] ?>);
  fd.append('user_id', userId);
  fd.append('position', gbCurrentPosition);
  const json = await postJSON('<?= BASE_URL ?>api/set_gb_position.php', fd);
  if (json.ok) {
    showToast('success', json.message);
    document.getElementById('gbModal').classList.remove('open');
    setTimeout(() => location.reload(), 500);
  } else {
    showToast('error', json.message);
  }
}
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
