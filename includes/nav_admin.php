<?php
$navItems = [
    ['Dashboard', BASE_URL . 'admin/dashboard.php', 'home', 'dashboard.php'],
    ['Clubs', BASE_URL . 'admin/clubs.php', 'briefcase', 'clubs.php,club_edit.php'],
    ['Users', BASE_URL . 'admin/users.php', 'users', 'users.php'],
    ['Events', BASE_URL . 'admin/events.php', 'calendar', 'events.php'],
    ['Budgets', BASE_URL . 'admin/budgets.php', 'dollar', 'budgets.php'],
    ['Venues', BASE_URL . 'admin/venues.php', 'map-pin', 'venues.php'],
    ['Collaborations', BASE_URL . 'admin/collaborations.php', 'share', 'collaborations.php'],
    ['Analytics', BASE_URL . 'admin/analytics.php', 'bar-chart', 'analytics.php'],
    ['Announcements', BASE_URL . 'admin/announcements.php', 'megaphone', 'announcements.php'],
];
foreach ($navItems as [$label, $href, $ic, $matches]) {
    $active = in_array($currentPage, explode(',', $matches), true);
    echo '<a href="' . e($href) . '" class="' . ($active ? 'active' : '') . '">' . icon($ic) . '<span>' . e($label) . '</span></a>';
}
