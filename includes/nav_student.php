<?php
$navItems = [
    ['Dashboard', BASE_URL . 'student/dashboard.php', 'home', 'dashboard.php'],
    ['Explore Clubs', BASE_URL . 'student/clubs.php', 'compass', 'clubs.php'],
    ['My Clubs', BASE_URL . 'student/my_clubs.php', 'briefcase', 'my_clubs.php'],
    ['Tasks', BASE_URL . 'student/tasks.php', 'check-square', 'tasks.php'],
    ['Events', BASE_URL . 'student/events.php', 'calendar', 'events.php,event.php'],
    ['Collaboration', BASE_URL . 'student/collaboration.php', 'share', 'collaboration.php'],
    ['My Applications', BASE_URL . 'student/my_applications.php', 'clipboard', 'my_applications.php'],
    ['Notifications', BASE_URL . 'student/notifications.php', 'bell', 'notifications.php'],
    ['Profile', BASE_URL . 'student/profile.php', 'user', 'profile.php'],
];
foreach ($navItems as [$label, $href, $ic, $matches]) {
    $active = in_array($currentPage, explode(',', $matches), true);
    echo '<a href="' . e($href) . '" class="' . ($active ? 'active' : '') . '">' . icon($ic) . '<span>' . e($label) . '</span></a>';
}
