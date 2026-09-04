<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['admin']);
$pdo = db();

$q = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$sql = "SELECT u.*, (SELECT c.name FROM club_members cm JOIN clubs c ON c.id=cm.club_id WHERE cm.user_id=u.id AND cm.member_role IN ('exec','president') AND cm.status='active' LIMIT 1) AS club_name FROM users u WHERE 1=1";
$params = [];
if ($q !== '') { $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($roleFilter) { $sql .= ' AND u.role = ?'; $params[] = $roleFilter; }
$sql .= ' ORDER BY u.role, u.name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Users';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Users</h1><div class="sub"><?= count($users) ?> accounts</div></div>
  <button class="btn btn-primary" data-open-modal="newUserModal"><?= icon('plus','icon') ?> Add User</button>
</div>

<form class="filter-bar" method="get">
  <input class="input" type="search" name="q" placeholder="Search name or email..." value="<?= e($q) ?>">
  <select class="input" name="role" onchange="this.form.submit()">
    <option value="">All Roles</option>
    <option value="student" <?= $roleFilter==='student'?'selected':'' ?>>Student</option>
    <option value="exec" <?= $roleFilter==='exec'?'selected':'' ?>>Club Executive</option>
    <option value="admin" <?= $roleFilter==='admin'?'selected':'' ?>>OCA Admin</option>
  </select>
  <button class="btn btn-sm" type="submit">Search</button>
</form>

<div class="card" style="padding:0;">
  <div class="overflow-x">
  <table class="table responsive-cards">
    <thead><tr><th>User</th><th>Role</th><th>Club</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td data-label="User"><div class="flex-center-gap"><span class="avatar" style="width:28px;height:28px;font-size:11px;background:<?= e($u['avatar_color']) ?>;"><?= e(initials($u['name'])) ?></span><div><?= e($u['name']) ?><br><span class="text-xs text-subtle"><?= e($u['email']) ?></span></div></div></td>
        <td data-label="Role"><span class="badge <?= $u['role']==='admin'?'badge-purple':($u['role']==='exec'?'badge-blue':'badge-gray') ?>"><?= statusLabel($u['role']) ?></span></td>
        <td data-label="Club"><?= e($u['club_name'] ?? '—') ?></td>
        <td data-label="Status"><span class="badge <?= $u['status']==='active'?'badge-green':'badge-red' ?>"><?= statusLabel($u['status']) ?></span></td>
        <td data-label="Actions">
          <?php if ((int)$u['id'] !== (int)$user['id']): ?>
            <?php if ($u['status']==='active'): ?>
              <button class="btn btn-sm btn-danger" data-confirm="Suspend <?= e($u['name']) ?>?" data-ajax-post="<?= BASE_URL ?>api/admin_user_action.php" data-ajax-body='{"user_id":<?= $u['id'] ?>,"action":"suspend"}'>Suspend</button>
            <?php else: ?>
              <button class="btn btn-sm btn-success" data-ajax-post="<?= BASE_URL ?>api/admin_user_action.php" data-ajax-body='{"user_id":<?= $u['id'] ?>,"action":"activate"}'>Activate</button>
            <?php endif; ?>
          <?php else: ?><span class="text-xs text-subtle">You</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="modal-backdrop" id="newUserModal">
  <div class="modal">
    <div class="modal-head"><h3>Add User</h3><button class="modal-close" data-close-modal>&times;</button></div>
    <form class="ajax-form" action="<?= BASE_URL ?>api/create_user.php" method="post">
      <div class="modal-body">
        <div class="field"><label>Full name *</label><input class="input" name="name" required placeholder="e.g. Dr. Farhana Rahman"></div>
        <div class="field"><label>Email *</label><input class="input" type="email" name="email" required placeholder="name@bracu.ac.bd"></div>
        <div class="field">
          <label>Role *</label>
          <select class="input" name="role" required>
            <option value="student">Student</option>
            <option value="exec">Club Executive</option>
            <option value="admin">OCA Administrator</option>
          </select>
        </div>
        <div class="field">
          <label>Password</label>
          <input class="input" type="password" name="password" placeholder="Leave blank to use default: password123">
          <p class="hint">Minimum 6 characters if set. The account owner can change it later.</p>
        </div>
        <?php if ($roleFilter === 'admin' || !$roleFilter): ?>
        <p class="text-xs text-subtle">Tip: to add another OCA staff member, set Role to <strong>OCA Administrator</strong> above.</p>
        <?php endif; ?>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Create Account</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
