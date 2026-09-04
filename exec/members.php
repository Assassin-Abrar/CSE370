<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;">Not linked to a club.</p>'); }

$members = clubMembersWithWorkload($club['id']);
$gbTitles = ['President', 'Vice President', 'General Secretary', 'Treasurer'];
$clubPositions = ['General Member', 'Junior Executive', 'Executive', 'Senior Executive', 'Assistant Director', 'Director'];

$pageTitle = 'Members';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>Club Members</h1><div class="sub"><?= count($members) ?> active members in <?= e($club['name']) ?></div></div></div>

<div class="card" style="padding:0;">
  <div class="overflow-x">
  <table class="table responsive-cards">
    <thead><tr><th>Member</th><th>Position</th><th>Workload</th><th>Joined</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($members as $m): $wl = $m['workload']; ?>
      <tr>
        <td data-label="Member"><div class="flex-center-gap"><span class="avatar" style="width:30px;height:30px;font-size:11px;background:<?= e($m['avatar_color']) ?>;"><?= e(initials($m['name'])) ?></span><?= e($m['name']) ?></div></td>
        <td data-label="Position">
          <?php if (in_array($m['position'], $gbTitles, true)): ?>
            <span class="badge <?= $m['member_role']==='president'?'badge-purple':'badge-blue' ?>"><?= e($m['position']) ?></span>
          <?php elseif ($m['position'] === 'Director'): ?>
            <span class="badge badge-blue" title="Director has exec dashboard access"><?= e($m['position']) ?></span>
          <?php else: ?>
            <span class="badge badge-gray"><?= e($m['position']) ?></span>
          <?php endif; ?>
        </td>
        <td data-label="Workload">
          <div style="display:flex;align-items:center;gap:8px;min-width:120px;">
            <div class="progress <?= $wl['status']==='Overloaded'?'red':($wl['status']==='Busy'?'amber':'green') ?>" style="flex:1;"><span style="width:<?= $wl['percent'] ?>%"></span></div>
            <span class="text-xs fw-600"><?= $wl['percent'] ?>%</span>
          </div>
        </td>
        <td data-label="Joined"><?= date('M Y', strtotime($m['joined_at'])) ?></td>
        <td data-label="Actions">
          <?php if (!in_array($m['position'], $gbTitles, true)): ?>
          <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
            <select class="input" style="width:auto;padding:6px 10px;font-size:13px;" onchange="setMemberPosition(<?= $m['id'] ?>, this.value)">
              <?php foreach ($clubPositions as $pos): ?>
                <option value="<?= e($pos) ?>" <?= $m['position']===$pos?'selected':'' ?>><?= e($pos) ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-danger" data-confirm="Remove <?= e($m['name']) ?> from the club?" data-ajax-post="<?= BASE_URL ?>api/manage_member.php" data-ajax-body='{"user_id":<?= $m['id'] ?>,"action":"remove"}'>Remove</button>
          </div>
          <?php else: ?><span class="text-xs text-subtle">Managed by OCA admin</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<script>
async function setMemberPosition(userId, position) {
  const fd = new FormData();
  fd.append('user_id', userId);
  fd.append('action', 'set_position');
  fd.append('position', position);
  const json = await postJSON('<?= BASE_URL ?>api/manage_member.php', fd);
  if (json.ok) { showToast('success', json.message); setTimeout(() => location.reload(), 500); }
  else showToast('error', json.message);
}
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
