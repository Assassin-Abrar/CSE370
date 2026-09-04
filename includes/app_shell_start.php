<?php
// Expects: $user (array), $pageTitle. layout_head.php must already be included.
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$navFile = ['student' => 'nav_student.php', 'exec' => 'nav_exec.php', 'admin' => 'nav_admin.php'][$user['role']] ?? 'nav_student.php';
$roleLabel = ['student' => 'Student', 'exec' => 'Club Executive', 'admin' => 'OCA Administrator'][$user['role']] ?? $user['role'];

$stmt = db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 6');
$stmt->execute([$user['id']]);
$recentNotifs = $stmt->fetchAll();
$unreadCount = unreadNotificationCount((int)$user['id']);

$myClubForNav = $user['role'] === 'exec' ? myClub($user) : null;
?>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <span class="mark">B</span>
      <span>BRACU CMS<?php if ($myClubForNav): ?><br><span class="text-xs text-subtle fw-600" style="font-weight:600;"><?= e($myClubForNav['name']) ?></span><?php endif; ?></span>
    </div>
    <nav class="sidebar-nav">
      <?php include __DIR__ . '/' . $navFile; ?>
    </nav>
    <div class="sidebar-foot">
      <a href="<?= BASE_URL ?>auth/logout.php" class="sidebar-user" style="text-decoration:none;color:inherit;">
        <span class="avatar" style="width:36px;height:36px;background:<?= e($user['avatar_color']) ?>"><?= e(initials($user['name'])) ?></span>
        <span style="flex:1;min-width:0;">
          <span class="name" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($user['name']) ?></span>
          <span class="role"><?= e($roleLabel) ?></span>
        </span>
        <?= icon('log-out', 'icon') ?>
      </a>
    </div>
  </aside>
  <div class="sidebar-scrim"></div>

  <div class="main">
    <header class="topbar">
      <button class="icon-btn menu-toggle" data-menu-toggle aria-label="Toggle menu"><?= icon('menu') ?></button>
      <form class="search-box" action="<?= BASE_URL ?>search.php" method="get">
        <span class="si"><?= icon('search', 'icon') ?></span>
        <input class="input" type="search" name="q" placeholder="Search clubs, events, collaboration..." value="<?= e($_GET['q'] ?? '') ?>">
      </form>
      <div class="topbar-right">
        <button class="icon-btn" data-theme-toggle aria-label="Toggle theme"><?= icon('moon') ?></button>
        <div style="position:relative;">
          <button class="icon-btn" data-dropdown-toggle="notifPanel" aria-label="Notifications">
            <?= icon('bell') ?>
            <?php if ($unreadCount > 0): ?><span class="dot"></span><?php endif; ?>
          </button>
          <div class="dropdown-panel" id="notifPanel">
            <div class="dropdown-head">
              <strong>Notifications</strong>
              <?php if ($unreadCount > 0): ?><button class="btn-link" data-ajax-post="<?= BASE_URL ?>api/mark_all_read.php" data-ajax-body="{}">Mark all read</button><?php endif; ?>
            </div>
            <div class="dropdown-list">
              <?php if (empty($recentNotifs)): ?>
                <div class="dropdown-empty">You're all caught up.</div>
              <?php else: foreach ($recentNotifs as $n): ?>
                <a href="<?= e($n['link'] ? BASE_URL . ltrim($n['link'], '/') : '#') ?>" class="dropdown-item <?= $n['is_read'] ? '' : 'unread' ?>">
                  <span class="dot-mark"></span>
                  <span>
                    <strong><?= e($n['title']) ?></strong>
                    <p><?= e($n['message']) ?></p>
                    <span class="text-subtle text-xs"><?= timeAgo($n['created_at']) ?></span>
                  </span>
                </a>
              <?php endforeach; endif; ?>
            </div>
            <a href="<?= BASE_URL . $user['role'] ?>/notifications.php" class="dropdown-foot">View all notifications</a>
          </div>
        </div>
      </div>
    </header>
    <div class="page">
