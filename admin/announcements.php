<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['admin']);
$pdo = db();

$stmt = $pdo->query("SELECT a.*, u.name AS author FROM announcements a LEFT JOIN users u ON u.id=a.posted_by ORDER BY a.created_at DESC");
$announcements = $stmt->fetchAll();

$pageTitle = 'Announcements';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Announcements</h1><div class="sub">Campus-wide notices from the OCA.</div></div>
  <button class="btn btn-primary" data-open-modal="newAnnModal"><?= icon('plus','icon') ?> New Announcement</button>
</div>

<?php if (!$announcements): ?>
  <div class="empty-state"><div class="icon">📣</div><h4>No announcements yet</h4></div>
<?php else: foreach ($announcements as $a): ?>
  <div class="card" style="margin-bottom:14px;">
    <div class="flex-between">
      <strong><?= e($a['title']) ?></strong>
      <span class="badge badge-blue"><?= statusLabel($a['audience']) ?></span>
    </div>
    <p class="text-sm text-muted" style="margin:8px 0;"><?= nl2br(e($a['body'])) ?></p>
    <span class="text-xs text-subtle">By <?= e($a['author'] ?? 'OCA') ?> · <?= timeAgo($a['created_at']) ?></span>
  </div>
<?php endforeach; endif; ?>

<div class="modal-backdrop" id="newAnnModal">
  <div class="modal">
    <div class="modal-head"><h3>New Announcement</h3><button class="modal-close" data-close-modal>&times;</button></div>
    <form class="ajax-form" action="<?= BASE_URL ?>api/create_announcement.php" method="post">
      <div class="modal-body">
        <div class="field"><label>Title *</label><input class="input" name="title" required></div>
        <div class="field"><label>Message *</label><textarea class="input" name="body" rows="4" required></textarea></div>
        <div class="field"><label>Audience</label>
          <select class="input" name="audience">
            <option value="all">Everyone</option>
            <option value="students">Students only</option>
            <option value="execs">Club Executives only</option>
          </select>
        </div>
      </div>
      <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-primary">Post Announcement</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
