<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

function currentUser(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if (!$u) return null;
    $cache = $u;
    return $u;
}

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(): array {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login.php');
        exit;
    }
    $u = currentUser();
    if (!$u || $u['status'] !== 'active') {
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login.php?suspended=1');
        exit;
    }
    return $u;
}

function requireRole(array $roles): array {
    $u = requireLogin();
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        die('<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p><p><a href="' . BASE_URL . '">Go home</a></p>');
    }
    return $u;
}

function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(?string $token): bool {
    return !empty($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}

/** For exec users: the club they are an exec/president of. Returns null if none. */
function myClub(array $user): ?array {
    if ($user['role'] !== 'exec') return null;
    $stmt = db()->prepare("SELECT c.* FROM clubs c JOIN club_members cm ON cm.club_id = c.id WHERE cm.user_id = ? AND cm.member_role IN ('exec','president') ORDER BY (cm.member_role='president') DESC LIMIT 1");
    $stmt->execute([$user['id']]);
    $club = $stmt->fetch();
    return $club ?: null;
}
