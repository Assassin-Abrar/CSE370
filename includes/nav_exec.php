<?php
$navItems = [
    ['Dashboard', BASE_URL . 'exec/dashboard.php', 'home', 'dashboard.php'],
    ['My Club', BASE_URL . 'exec/club_manage.php', 'briefcase', 'club_manage.php'],
    ['Members', BASE_URL . 'exec/members.php', 'users', 'members.php'],
    ['Recruitment', BASE_URL . 'exec/recruitment.php', 'clipboard', 'recruitment.php,campaigns.php'],
    ['Events', BASE_URL . 'exec/events.php', 'calendar', 'events.php,event_new.php'],
    ['Budget', BASE_URL . 'exec/budget.php', 'dollar', 'budget.php,budget_new.php'],
    ['Collaboration', BASE_URL . 'exec/collaboration.php', 'share', 'collaboration.php,collaboration_new.php'],
    ['Tasks', BASE_URL . 'exec/tasks.php', 'check-square', 'tasks.php'],
    ['Workload', BASE_URL . 'exec/workload.php', 'bar-chart', 'workload.php'],
    ['Analytics', BASE_URL . 'exec/analytics.php', 'bar-chart', 'analytics.php'],
    ['Notifications', BASE_URL . 'exec/notifications.php', 'bell', 'notifications.php'],
];
foreach ($navItems as [$label, $href, $ic, $matches]) {
    $active = in_array($currentPage, explode(',', $matches), true);
    echo '<a href="' . e($href) . '" class="' . ($active ? 'active' : '') . '">' . icon($ic) . '<span>' . e($label) . '</span></a>';
}
