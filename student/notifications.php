<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['student']);
$pdo = db();

$filter = $_GET['filter'] ?? 'all';
$sql = 'SELECT * FROM notifications WHERE user_id = ?';
if ($filter === 'unread') $sql .= ' AND is_read = 0';
$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute([$user['id']]);
$notifs = $stmt->fetchAll();
$unread = unreadNotificationCount((int)$user['id']);

$catIcons = ['application'=>'clipboard','event'=>'calendar','deadline'=>'clock','task'=>'check-square','budget'=>'dollar','collaboration'=>'share','venue'=>'alert-triangle','welcome'=>'star','announcement'=>'megaphone'];

$pageTitle = 'Notifications';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Notifications</h1><div class="sub"><span id="unreadCount"><?= $unread ?></span> unread</div></div>
  <?php if ($unread > 0): ?><button class="btn btn-outline" id="markAllBtn" data-ajax-post="<?= BASE_URL ?>api/mark_all_read.php" data-ajax-body="{}">Mark all read</button><?php endif; ?>
</div>
<div class="tabs">
  <a href="?filter=all" class="<?= $filter==='all'?'active':'' ?>">All</a>
  <a href="?filter=unread" class="<?= $filter==='unread'?'active':'' ?>">Unread</a>
</div>

<?php if (!$notifs): ?>
  <div class="empty-state"><div class="icon">🔔</div><h4>Nothing here</h4><p>You're all caught up.</p></div>
<?php else: ?>
<div class="card" style="padding:0;">
  <?php foreach ($notifs as $n): ?>
    <div class="list-item-hover <?= $n['is_read']?'':'unread-row' ?>" id="notifRow<?= $n['id'] ?>" style="display:flex;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border);">
      <div class="icon-wrap" style="width:36px;height:36px;border-radius:10px;background:#fff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= icon($catIcons[$n['category']] ?? 'bell') ?></div>
      <div style="flex:1;">
        <div class="flex-between">
          <strong class="text-sm"><?= e($n['title']) ?></strong>
          <span class="text-xs text-subtle"><?= timeAgo($n['created_at']) ?></span>
        </div>
        <p class="text-sm text-muted" style="margin:4px 0 0;"><?= e($n['message']) ?></p>
        <?php if (!$n['is_read']): ?>
          <button class="btn-link mark-read-btn" style="margin-top:6px;" data-notif-id="<?= $n['id'] ?>">Mark as read</button>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.mark-read-btn');
  if (!btn) return;
  const id = btn.dataset.notifId;
  const filterIsUnread = <?= $filter === 'unread' ? 'true' : 'false' ?>;
  btn.disabled = true;
  const fd = new FormData();
  fd.append('id', id);
  const json = await postJSON('<?= BASE_URL ?>api/mark_notification_read.php', fd);
  if (!json.ok) { showToast('error', json.message || 'Something went wrong.'); btn.disabled = false; return; }

  const row = document.getElementById('notifRow' + id);
  if (row) {
    if (filterIsUnread) { row.remove(); } else { row.classList.remove('unread-row'); btn.remove(); }
  }
  const countEl = document.getElementById('unreadCount');
  if (countEl) {
    const remaining = Math.max(0, parseInt(countEl.textContent, 10) - 1);
    countEl.textContent = remaining;
    if (remaining === 0) {
      const markAllBtn = document.getElementById('markAllBtn');
      if (markAllBtn) markAllBtn.remove();
      const bellDot = document.querySelector('[data-dropdown-toggle="notifPanel"] .dot');
      if (bellDot) bellDot.remove();
    }
  }
  if (!document.querySelector('.card [id^="notifRow"]')) {
    const card = document.querySelector('.card');
    if (card) card.outerHTML = '<div class="empty-state"><div class="icon">🔔</div><h4>Nothing here</h4><p>You\'re all caught up.</p></div>';
  }
});
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
