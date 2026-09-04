<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['admin']);
$pdo = db();

$q = trim($_GET['q'] ?? '');
$sql = "SELECT c.*, (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id=c.id AND cm.status='active') AS member_count FROM clubs c";
$params = [];
if ($q !== '') { $sql .= ' WHERE c.name LIKE ?'; $params[] = "%$q%"; }
$sql .= ' ORDER BY c.name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clubs = $stmt->fetchAll();

$pageTitle = 'Clubs';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Clubs</h1><div class="sub"><?= count($clubs) ?> clubs registered</div></div>
  <button class="btn btn-primary" data-open-modal="newClubModal"><?= icon('plus','icon') ?> Add Club</button>
</div>

<form class="filter-bar" method="get"><input class="input" type="search" name="q" placeholder="Search clubs..." value="<?= e($q) ?>"><button class="btn btn-sm" type="submit">Search</button></form>

<div class="card" style="padding:0;">
  <div class="overflow-x">
  <table class="table responsive-cards">
    <thead><tr><th>Club</th><th>Category</th><th>Members</th><th>Recruitment</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($clubs as $c): ?>
      <tr>
        <td data-label="Club"><div class="flex-center-gap"><span class="logo" style="width:32px;height:32px;border-radius:9px;background:<?= e($c['logo_color']) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;"><?= e(mb_substr($c['name'],0,1)) ?></span><a href="club_edit.php?id=<?= $c['id'] ?>" style="color:var(--primary);font-weight:600;"><?= e($c['name']) ?></a></div></td>
        <td data-label="Category"><?= e($c['category']) ?></td>
        <td data-label="Members"><?= $c['member_count'] ?></td>
        <td data-label="Recruitment"><span class="badge <?= $c['recruitment_status']==='open'?'badge-green':'badge-gray' ?>"><?= statusLabel($c['recruitment_status']) ?></span></td>
        <td data-label="Status"><span class="badge <?= $c['status']==='active'?'badge-green':'badge-red' ?>"><?= statusLabel($c['status']) ?></span></td>
        <td data-label="Actions">
          <div style="display:flex;gap:6px;">
            <a href="club_edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline">View</a>
            <?php if ($c['status']==='active'): ?>
              <button class="btn btn-sm btn-danger" data-confirm="Suspend <?= e($c['name']) ?>?" data-ajax-post="<?= BASE_URL ?>api/admin_club_action.php" data-ajax-body='{"club_id":<?= $c['id'] ?>,"action":"suspend"}'>Suspend</button>
            <?php else: ?>
              <button class="btn btn-sm btn-success" data-ajax-post="<?= BASE_URL ?>api/admin_club_action.php" data-ajax-body='{"club_id":<?= $c['id'] ?>,"action":"activate"}'>Activate</button>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="modal-backdrop" id="newClubModal">
  <div class="modal">
    <div class="modal-head"><h3>Add New Club</h3><button class="modal-close" data-close-modal>&times;</button></div>
    <form class="ajax-form" action="<?= BASE_URL ?>api/create_club.php" method="post">
      <div class="modal-body">
        <div class="field"><label>Club name *</label><input class="input" name="name" required></div>
        <div class="form-row">
          <div class="field"><label>Category *</label>
            <select class="input" name="category" required>
              <?php foreach (['Academic','Technology','Cultural','Sports','Social Service','Debate','Media','Entrepreneurship','Arts','Professional','Extra Curricular','Co-Curricular'] as $cat): ?><option value="<?= $cat ?>"><?= $cat ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label>Founded year</label><input class="input" type="number" name="founded_year" value="<?= date('Y') ?>"></div>
        </div>
        <div class="field"><label>Description</label><textarea class="input" name="description" rows="2"></textarea></div>
        <div class="divider"></div>
        <p class="text-sm fw-600">President account (optional)</p>
        <div class="form-row">
          <div class="field"><label>Name</label><input class="input" name="president_name" placeholder="Full name"></div>
          <div class="field"><label>Email</label><input class="input" type="email" name="president_email" placeholder="name@bracu.ac.bd"></div>
        </div>
        <p class="hint">A new account is created with the default password <code>password123</code> if the email doesn't already exist.</p>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Create Club</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
