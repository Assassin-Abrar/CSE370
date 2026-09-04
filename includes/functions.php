<?php
require_once __DIR__ . '/../config/db.php';

function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Cache-busted asset URL — appends the file's last-modified time so browsers
// always fetch the latest CSS/JS after a deploy instead of serving a stale cache.
function assetUrl(string $relativePath): string {
    $fsPath = __DIR__ . '/../' . $relativePath;
    $v = file_exists($fsPath) ? filemtime($fsPath) : time();
    return BASE_URL . $relativePath . '?v=' . $v;
}

function moneyBDT($amount): string {
    if ($amount === null) return '—';
    return '৳' . number_format((float)$amount, 0);
}

function timeAgo(string $datetime): string {
    $ts = strtotime($datetime);
    $diff = time() - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p !== '') $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out ?: '?';
}

// ---------- Flash messages / toasts ----------
function flash(string $type, string $message): void {
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array {
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

// ---------- Notifications ----------
function notify(int $userId, string $category, string $title, string $message, ?string $link = null): void {
    $stmt = db()->prepare('INSERT INTO notifications (user_id, category, title, message, link) VALUES (?,?,?,?,?)');
    $stmt->execute([$userId, $category, $title, $message, $link]);
}

function unreadNotificationCount(int $userId): int {
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// The tasks board lives at a different URL per role (exec sees the full club
// board, everyone else sees their own personal task list) — use this instead
// of hardcoding /exec/tasks.php so notification links don't 403 for students.
function taskPageLinkForUser(int $userId): string {
    $stmt = db()->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() === 'exec' ? '/exec/tasks.php' : '/student/tasks.php';
}

function logAudit(?int $actorId, string $action, ?string $entityType = null, ?int $entityId = null, ?string $details = null): void {
    $stmt = db()->prepare('INSERT INTO audit_log (actor_user_id, action, entity_type, entity_id, details) VALUES (?,?,?,?,?)');
    $stmt->execute([$actorId, $action, $entityType, $entityId, $details]);
}

// Keeps a user's system-wide role in sync with whether they hold any
// exec/president-level seat in any club (GB position, or Director rank).
// Never touches admin accounts. Called after any promotion/demotion.
function syncSystemRoleFromClubMembership(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $currentRole = $stmt->fetchColumn();
    if ($currentRole === 'admin') return;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE user_id = ? AND status = 'active' AND member_role IN ('exec','president')");
    $stmt->execute([$userId]);
    $holdsExecSomewhere = (int)$stmt->fetchColumn() > 0;

    $desiredRole = $holdsExecSomewhere ? 'exec' : 'student';
    if ($currentRole !== $desiredRole) {
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$desiredRole, $userId]);
    }
}

// ---------- Smart Recruitment Matcher ----------
function computeClubMatch(int $userId, array $club): array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT s.id, s.name FROM student_skills ss JOIN skills s ON s.id = ss.skill_id WHERE ss.user_id = ?');
    $stmt->execute([$userId]);
    $studentSkills = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT i.id, i.name FROM student_interests si JOIN interests i ON i.id = si.interest_id WHERE si.user_id = ?');
    $stmt->execute([$userId]);
    $studentInterests = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT s.id, s.name FROM club_skills cs JOIN skills s ON s.id = cs.skill_id WHERE cs.club_id = ?');
    $stmt->execute([$club['id']]);
    $clubSkills = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT i.id, i.name FROM club_interests ci JOIN interests i ON i.id = ci.interest_id WHERE ci.club_id = ?');
    $stmt->execute([$club['id']]);
    $clubInterests = $stmt->fetchAll();

    $studentSkillIds = array_column($studentSkills, 'id');
    $studentInterestIds = array_column($studentInterests, 'id');

    $matchedSkills = [];
    foreach ($clubSkills as $cs) {
        if (in_array($cs['id'], $studentSkillIds, true)) $matchedSkills[] = $cs['name'];
    }
    $matchedInterests = [];
    foreach ($clubInterests as $ci) {
        if (in_array($ci['id'], $studentInterestIds, true)) $matchedInterests[] = $ci['name'];
    }

    $skillScore = count($clubSkills) > 0 ? count($matchedSkills) / count($clubSkills) : 0;
    $interestScore = count($clubInterests) > 0 ? count($matchedInterests) / count($clubInterests) : 0;
    $percent = (int)round($skillScore * 60 + $interestScore * 40);
    $percent = max(0, min(100, $percent));

    $reasons = [];
    foreach ($matchedSkills as $s) $reasons[] = "$s matches club recruitment requirements";
    foreach ($matchedInterests as $i) $reasons[] = "$i matches your interests";
    if ($club['recruitment_status'] === 'open') $reasons[] = 'Club is currently recruiting members';
    if (empty($reasons)) $reasons[] = 'Add skills and interests to your profile to see personalized match reasons';

    return [
        'percent' => $percent,
        'reasons' => $reasons,
        'matched_skills' => $matchedSkills,
        'matched_interests' => $matchedInterests,
        'skill_score' => round($skillScore * 100),
        'interest_score' => round($interestScore * 100),
    ];
}

function getAllClubMatches(int $userId): array {
    $stmt = db()->query('SELECT * FROM clubs WHERE status = "active" ORDER BY name');
    $clubs = $stmt->fetchAll();
    $out = [];
    foreach ($clubs as $club) {
        $m = computeClubMatch($userId, $club);
        $out[] = array_merge($club, ['match' => $m]);
    }
    usort($out, fn($a, $b) => $b['match']['percent'] <=> $a['match']['percent']);
    return $out;
}

// ---------- Event Clash Detection ----------
function checkVenueConflict(?int $venueId, string $date, string $startTime, string $endTime, ?int $excludeEventId = null): array {
    if (!$venueId) return [];
    $sql = "SELECT e.*, c.name AS club_name, v.name AS venue_name FROM events e
            LEFT JOIN clubs c ON c.id = e.club_id
            LEFT JOIN venues v ON v.id = e.venue_id
            WHERE e.venue_id = ? AND e.event_date = ?
              AND e.status IN ('submitted','approved')
              AND NOT (e.end_time <= ? OR e.start_time >= ?)";
    $params = [$venueId, $date, $startTime, $endTime];
    if ($excludeEventId) {
        $sql .= ' AND e.id != ?';
        $params[] = $excludeEventId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function checkMajorEventConflict(string $date, ?int $excludeEventId = null): array {
    $sql = "SELECT e.*, v.name AS venue_name FROM events e LEFT JOIN venues v ON v.id = e.venue_id
            WHERE e.event_date = ? AND e.is_major_event = 1 AND e.status IN ('submitted','approved')";
    $params = [$date];
    if ($excludeEventId) {
        $sql .= ' AND e.id != ?';
        $params[] = $excludeEventId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function suggestAlternativeVenues(string $date, string $startTime, string $endTime, ?int $excludeEventId = null): array {
    $stmt = db()->query('SELECT * FROM venues WHERE status = "available" ORDER BY capacity DESC');
    $venues = $stmt->fetchAll();
    $free = [];
    foreach ($venues as $v) {
        if (empty(checkVenueConflict($v['id'], $date, $startTime, $endTime, $excludeEventId))) {
            $free[] = $v;
        }
    }
    return $free;
}

// ---------- Workload Analysis ----------
define('WORKLOAD_CAPACITY_POINTS', 20.0);

function computeMemberWorkload(int $userId, ?int $clubId = null): array {
    $sql = "SELECT * FROM tasks WHERE assigned_to = ? AND status != 'completed'";
    $params = [$userId];
    if ($clubId) { $sql .= ' AND club_id = ?'; $params[] = $clubId; }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();

    $today = new DateTime('today');
    $points = 0.0;
    $highCount = 0;
    $overdueCount = 0;
    foreach ($tasks as $t) {
        $priorityWeight = ['low' => 0.7, 'medium' => 1.0, 'high' => 1.5][$t['priority']] ?? 1.0;
        if ($t['priority'] === 'high') $highCount++;
        $deadlineWeight = 1.0;
        if (!empty($t['deadline'])) {
            $deadline = new DateTime($t['deadline']);
            $daysLeft = (int)$today->diff($deadline)->format('%r%a');
            if ($daysLeft < 0) { $deadlineWeight = 1.3; $overdueCount++; }
            elseif ($daysLeft <= 3) { $deadlineWeight = 1.15; }
        }
        $points += (float)$t['estimated_workload'] * $priorityWeight * $deadlineWeight;
    }

    $percent = (int)min(100, round(($points / WORKLOAD_CAPACITY_POINTS) * 100));
    if ($percent >= 75) $status = 'Overloaded';
    elseif ($percent >= 40) $status = 'Busy';
    else $status = 'Healthy';

    return [
        'percent' => $percent,
        'status' => $status,
        'active_tasks' => count($tasks),
        'high_priority' => $highCount,
        'overdue' => $overdueCount,
    ];
}

function clubMembersWithWorkload(int $clubId): array {
    $stmt = db()->prepare("SELECT u.* , cm.position, cm.member_role, cm.joined_at FROM club_members cm JOIN users u ON u.id = cm.user_id WHERE cm.club_id = ? AND cm.status='active' ORDER BY cm.member_role='president' DESC, cm.member_role='exec' DESC, u.name");
    $stmt->execute([$clubId]);
    $members = $stmt->fetchAll();
    foreach ($members as &$m) {
        $m['workload'] = computeMemberWorkload((int)$m['id'], $clubId);
    }
    return $members;
}

// ---------- Misc ----------
function badgeClass(string $status): string {
    $map = [
        'submitted' => 'badge-blue', 'under_review' => 'badge-amber', 'shortlisted' => 'badge-purple',
        'interview' => 'badge-purple', 'accepted' => 'badge-green', 'rejected' => 'badge-red',
        'approved' => 'badge-green', 'draft' => 'badge-gray', 'open' => 'badge-green', 'closed' => 'badge-gray',
        'todo' => 'badge-gray', 'in_progress' => 'badge-blue', 'review' => 'badge-amber', 'completed' => 'badge-green',
        'cancelled' => 'badge-red', 'responses_received' => 'badge-blue', 'in_discussion' => 'badge-amber',
        'active' => 'badge-green', 'suspended' => 'badge-red', 'proposed' => 'badge-blue', 'declined' => 'badge-red',
        'high' => 'badge-red', 'medium' => 'badge-amber', 'low' => 'badge-gray',
    ];
    return $map[$status] ?? 'badge-gray';
}

function statusLabel(string $status): string {
    return ucwords(str_replace('_', ' ', $status));
}

function redirectTo(string $path): void {
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    $body = json_encode($data);
    if ($body === false) {
        // json_encode() fails (returns false, not a JSON string) on malformed UTF-8
        // in any field — echoing that would silently send an empty body. Fall back
        // to a hand-written string so the client always gets valid JSON back.
        error_log('jsonResponse: json_encode failed — ' . json_last_error_msg());
        http_response_code(500);
        echo '{"ok":false,"message":"A server error occurred while preparing the response. Please try again."}';
        exit;
    }
    echo $body;
    exit;
}

function isPost(): bool {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function requireCsrfOrFail(): void {
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    if (!verifyCsrf($token)) {
        jsonResponse(['ok' => false, 'message' => 'Invalid or expired session token. Please refresh the page.'], 419);
    }
}
