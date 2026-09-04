<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/icons.php';
$user = requireLogin();
$pdo = db();

$q = trim($_GET['q'] ?? '');
$clubs = $events = $collabs = $announcements = [];

if ($q !== '') {
    $like = "%$q%";
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE status='active' AND (name LIKE ? OR description LIKE ? OR category LIKE ?) LIMIT 10");
    $stmt->execute([$like, $like, $like]);
    $clubs = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT e.*, c.name AS club_name FROM events e LEFT JOIN clubs c ON c.id=e.club_id WHERE e.title LIKE ? OR e.description LIKE ? LIMIT 10");
    $stmt->execute([$like, $like]);
    $events = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT cr.*, c.name AS club_name FROM collaboration_requests cr JOIN clubs c ON c.id=cr.club_id WHERE cr.title LIKE ? OR cr.description LIKE ? LIMIT 10");
    $stmt->execute([$like, $like]);
    $collabs = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE title LIKE ? OR body LIKE ? LIMIT 10");
    $stmt->execute([$like, $like]);
    $announcements = $stmt->fetchAll();

    if ($user['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? LIMIT 10");
        $stmt->execute([$like, $like]);
        $studentsFound = $stmt->fetchAll();
    }
}
$totalResults = count($clubs) + count($events) + count($collabs) + count($announcements) + count($studentsFound ?? []);

$pageTitle = 'Search';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>Search results for "<?= e($q) ?>"</h1><div class="sub"><?= $totalResults ?> results found</div></div></div>

<?php if ($q === ''): ?>
  <div class="empty-state"><div class="icon">🔍</div><h4>Type something to search</h4><p>Search across clubs, events, collaboration requests and announcements.</p></div>
<?php elseif ($totalResults === 0): ?>
  <div class="empty-state"><div class="icon">🔍</div><h4>No results found</h4><p>Try a different search term.</p></div>
<?php else: ?>

<?php if ($clubs): ?>
<div class="card" style="margin-bottom:18px;">
  <div class="card-header"><span class="card-title">Clubs (<?= count($clubs) ?>)</span></div>
  <?php foreach ($clubs as $c): $link = $user['role']==='student' ? BASE_URL.'student/club.php?slug='.$c['slug'] : BASE_URL.'admin/club_edit.php?id='.$c['id']; ?>
    <a href="<?= e($link) ?>" class="list-item-hover flex-between" style="padding:10px;text-decoration:none;color:inherit;">
      <div class="flex-center-gap"><span class="logo" style="width:32px;height:32px;border-radius:9px;background:<?= e($c['logo_color']) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;"><?= e(mb_substr($c['name'],0,1)) ?></span><?= e($c['name']) ?></div>
      <span class="badge badge-gray"><?= e($c['category']) ?></span>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($events): ?>
<div class="card" style="margin-bottom:18px;">
  <div class="card-header"><span class="card-title">Events (<?= count($events) ?>)</span></div>
  <?php foreach ($events as $ev): ?>
    <div class="list-item-hover flex-between" style="padding:10px;">
      <div><strong class="text-sm"><?= e($ev['title']) ?></strong><br><span class="text-xs text-muted"><?= e($ev['club_name'] ?? 'OCA') ?> · <?= date('M j, Y', strtotime($ev['event_date'])) ?></span></div>
      <span class="badge <?= badgeClass($ev['status']) ?>"><?= statusLabel($ev['status']) ?></span>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($collabs): ?>
<div class="card" style="margin-bottom:18px;">
  <div class="card-header"><span class="card-title">Collaboration requests (<?= count($collabs) ?>)</span></div>
  <?php foreach ($collabs as $c): ?>
    <div class="list-item-hover flex-between" style="padding:10px;">
      <div><strong class="text-sm"><?= e($c['title']) ?></strong><br><span class="text-xs text-muted"><?= e($c['club_name']) ?></span></div>
      <span class="badge <?= badgeClass($c['status']) ?>"><?= statusLabel($c['status']) ?></span>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($announcements): ?>
<div class="card" style="margin-bottom:18px;">
  <div class="card-header"><span class="card-title">Announcements (<?= count($announcements) ?>)</span></div>
  <?php foreach ($announcements as $a): ?>
    <div class="list-item-hover" style="padding:10px;"><strong class="text-sm"><?= e($a['title']) ?></strong><p class="text-sm text-muted mt-0"><?= e(mb_substr($a['body'],0,100)) ?></p></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($studentsFound)): ?>
<div class="card" style="margin-bottom:18px;">
  <div class="card-header"><span class="card-title">Users (<?= count($studentsFound) ?>)</span></div>
  <?php foreach ($studentsFound as $s): ?>
    <div class="list-item-hover flex-between" style="padding:10px;">
      <div class="flex-center-gap"><span class="avatar" style="width:28px;height:28px;font-size:11px;background:<?= e($s['avatar_color']) ?>;"><?= e(initials($s['name'])) ?></span><?= e($s['name']) ?></div>
      <span class="badge badge-gray"><?= statusLabel($s['role']) ?></span>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require __DIR__ . '/includes/app_shell_end.php'; ?>
